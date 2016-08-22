<?php  
/* 
Plugin Name: UKM System Tools
Plugin URI: http://www.ukm-norge.no
Description: Network admin system-verktøy for import av postnummer, ssb-tall osv
Author: UKM Norge / M Mandal 
Version: 0.1 
Author URI: http://mariusmandal.no
*/

if(is_admin()) {
	add_action('network_admin_menu', 'UKMST_menu');
	add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_check_postnumber_updates');
	add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_newSeason');

}

function UKMST_menu() {
	$page = add_menu_page('UKM Norge Systemverktøy', 'System', 'superadmin', 'UKMsystemtools','UKMsystemtools', 'http://ico.ukm.no/system-16.png',22);

	$subpage1 = add_submenu_page( 'UKMsystemtools', 'TONO-rapport', 'TONO-rapport', 'superadministrator', 'UKMsystemtools_TONO', 'UKMsystemtools_TONO' );
	$subpage2 = add_submenu_page( 'UKMsystemtools', 'Kontakteksport', 'Kontakteksport', 'superadministrator', 'UKMkontakteksport', 'UKMkontakteksport' );
	$subpage3 = add_submenu_page( 'UKMsystemtools', 'Cloudflare-cache', 'Cloudflare-cache', 'superadministrator', 'UKMcloudflare_cache', 'UKMcloudflare_cache');
	$subpage4 = add_submenu_page( 'UKMsystemtools', 'Dropbox', 'Dropbox', 'superadministrator', 'UKMdropbox', 'UKMdropbox' );
	$subpage5 = add_submenu_page( 'UKMsystemtools', 'Synkroniser passord', 'Synkroniser passord', 'superadministrator', 'UKMsystemtools_passwordsync', 'UKMsystemtools_passwordsync' );
	$subpage6 = add_submenu_page( 'UKMsystemtools', 'Oppdater kortadresser', 'Oppdater kortadresser', 'superadministrator', 'UKMsystemtools_modrewrite', 'UKMsystemtools_modrewrite' );
	$subpage7 = add_submenu_page( 'UKMsystemtools', 'Opprett sesong', 'Opprett sesong', 'superadministrator', 'UKMsystemtools_ny_sesong', 'UKMsystemtools_ny_sesong' );
	$subpage8 = add_submenu_page( 'UKMsystemtools', 'Test påmelding', 'Test påmelding', 'superadministrator', 'UKMsystemtools_deltaTest', 'UKMsystemtools_deltaTest' );


    add_action( 'admin_print_styles-' . $page, 'UKMsystemtools_scripts_and_styles' );
	for( $i=1; $i<9; $i++ ) {
		$var = 'subpage'.$i;
		add_action( 'admin_print_styles-' . $$var, 'UKMsystemtools_scripts_and_styles' );
	}
}
function UKMsystemtools_ny_sesong() {
	require_once('controller/ny_sesong/ny_sesong.controller.php');
}
function UKMsystemtools_TONO() {
	$TWIGdata = [];
	require_once('controller/tono.controller.php');

	echo TWIG('tono.twig.html', $TWIGdata, dirname(__FILE__), true);
}

function UKMcloudflare_cache() {
	$view_data = [];
	wp_enqueue_script('UKMsystem_tools_addMore_js', plugin_dir_url(__FILE__).'js/addMore.js');
	require_once('controller/cloudflare.controller.php');

	echo TWIG('cloudflare.twig.html', $view_data, dirname(__FILE__), true);
}

function UKMsystemtools() {
	$TWIGdata = [];
	$action = isset($_GET['action']) ? $_GET['action'] : 'home';

	require_once('controller/'. $action .'.controller.php');
	$VIEW = $action;

	echo TWIG( $VIEW.'.twig.html', $TWIGdata, dirname(__FILE__), true);
}

function UKMdropbox() {
	$TWIGdata = [];
	require_once('controller/dropbox.controller.php');
	$VIEW = 'dropbox';

	echo TWIG( $VIEW.'.html.twig', $TWIGdata, dirname(__FILE__), true);
}


function UKMsystemtools_scripts_and_styles() {
	wp_enqueue_style('UKMwp_dashboard_css');
	wp_enqueue_script('WPbootstrap3_js');
	wp_enqueue_style('WPbootstrap3_css');
}
function UKMsystemtools_newSeason( $messages ) {
	// Etter juli må ny sesong settes opp
	if( 7 < (int)date('m') && get_site_option('season') == date('Y') ) {
		$messages[] = array(
			'level' 	=> 'alert-danger',
			'module'	=> 'System',
			'header'	=> 'NY SESONG MÅ SETTES OPP!',
			'link'		=> 'admin.php?page=UKMsystemtools_ny_sesong'
		);
	}
	// Påmeldingssystemet må testes hver sesong!
	if( get_site_option('delta_is_tested') != get_site_option('season') ) {
		$messages[] = array(
			'level' 	=> 'alert-danger',
			'module'	=> 'System',
			'header'	=> 'Påmeldingssystemet må testes!',
			'link'		=> 'admin.php?page=UKMsystemtools_deltaTest'
		);		
	}
	
	// I sesong, sjekk antall uregistrerte mønstringer
	if( in_array( (int)date('m'), array(11,12,1,2) ) ) {
		require_once('UKM/monstringer.class.php');
		$monstringer = new monstringer( get_site_option('season') );
		if( 15 < $monstringer->antall_uregistrerte() ) {
			$messages[] = array(
				'level' 	=> 'alert-warning',
				'module'	=> 'System',
				'header'	=> 'Det er '.$monstringer->antall_uregistrerte() .' uregistrerte mønstringer ',
#				'link'		=> 'admin.php?page=UKMsystemtools'
			);
		}
	}
	return $messages;
}
function UKMsystemtools_check_postnumber_updates($messages) {
	$last_postnumber_timestamp = get_site_option('ukm_systemtools_last_postnumber_update', false);
	if ($last_postnumber_timestamp && is_numeric($last_postnumber_timestamp)) {
		$last_year = intval( date("Y", intval( $last_postnumber_timestamp, 10 ) ) );
		$current_year = intval( date("Y") );

		if ($last_year < $current_year) {
			$messages[] = array(
				'level' 	=> 'alert-warning',
				'module'	=> __('System', 'UKM'),
				'header'	=> sprintf( __('Postnummer må oppdateres, sist oppdatert %s','UKM'), date("d.m.Y", $last_postnumber_timestamp) ),
				'body' 		=> __('Rett problemet under system-verktøy', 'UKM'),
				'link'		=> 'admin.php?page=UKMsystemtools'
			);
		}

	} else if ( $last_postnumber_timestamp == false ) {
		$messages[] = array(
			'level' 	=> 'alert-error',
			'module'	=> __('System', 'UKM'),
			'header'	=> __('Postnummer må oppdateres','UKM'),
			'body' 		=> __('Rett problemet under system-verktøy', 'UKM'),
			'link'		=> 'admin.php?page=UKMsystemtools'
		);
	}

	return $messages;
}

function UKMkontakteksport() {
	$TWIGdata = array();
	$VIEW = 'kontakter';
	require_once('controller/kontakter.controller.php');
	echo TWIG( $VIEW.'.twig.html', $TWIGdata, dirname(__FILE__), true);
}

function UKMsystemtools_passwordsync() {
	$TWIGdata = [];
	require_once('controller/passwordsync.controller.php');
}
function UKMsystemtools_modrewrite() {
	require_once('controller/modrewrite.controller.php');
}
function UKMsystemtools_deltaTest() {
	$TWIGdata = array();
	require_once('controller/testdelta.controller.php');
	echo TWIG( 'testdelta.html.twig', $TWIGdata, dirname(__FILE__), true);
}