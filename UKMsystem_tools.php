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
	add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_ssb_warning');
}

function UKMST_menu() {
	$page = add_menu_page(
		'UKM Norge Systemverktøy',
		'System',
		'superadmin',
		'UKMsystemtools',
		'UKMsystemtools',
		'dashicons-admin-generic',#//ico.ukm.no/system-16.png',
		22
	);

	$subpage1 = add_submenu_page( 'UKMsystemtools', 'TONO-rapport', 'TONO-rapport', 'superadministrator', 'UKMsystemtools_TONO', 'UKMsystemtools_TONO' );
	$subpage2 = add_submenu_page( 'UKMsystemtools', 'Kontakteksport', 'Kontakteksport', 'superadministrator', 'UKMkontakteksport', 'UKMkontakteksport' );
	$subpage3 = add_submenu_page( 'UKMsystemtools', 'Cloudflare-cache', 'Cloudflare-cache', 'superadministrator', 'UKMcloudflare_cache', 'UKMcloudflare_cache');
	$subpage4 = add_submenu_page( 'UKMsystemtools', 'Dropbox', 'Dropbox', 'superadministrator', 'UKMdropbox', 'UKMdropbox' );
	$subpage4 = add_submenu_page( 'UKMsystemtools', 'Flickr', 'Flickr', 'superadministrator', 'UKMflickr', 'UKMflickr' );
	$subpage5 = add_submenu_page( 'UKMsystemtools', 'Synkroniser passord', 'Synkroniser passord', 'superadministrator', 'UKMsystemtools_passwordsync', 'UKMsystemtools_passwordsync' );
	$subpage6 = add_submenu_page( 'UKMsystemtools', 'Oppdater kortadresser', 'Oppdater kortadresser', 'superadministrator', 'UKMsystemtools_modrewrite', 'UKMsystemtools_modrewrite' );
	$subpage7 = add_submenu_page( 'UKMsystemtools', 'Test påmelding', 'Test påmelding', 'superadministrator', 'UKMsystemtools_deltaTest', 'UKMsystemtools_deltaTest' );
	$subpage8 = add_submenu_page( 'UKMsystemtools', 'Importer SSB-data', 'Importer SSB-data', 'superadministrator', 'UKMsystemtools_ssb_import', 'UKMsystemtools_ssb_import' );


	add_action( 'admin_print_styles-' . $page, 'UKMsystemtools_scripts_and_styles' );
	for( $i=1; $i<=8; $i++ ) {
		$var = 'subpage'.$i;
		add_action( 'admin_print_styles-' . $$var, 'UKMsystemtools_scripts_and_styles' );
	}
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
function UKMflickr() {
	require_once('controller/flickr.controller.php');
}


function UKMsystemtools_scripts_and_styles() {
	wp_enqueue_style('UKMwp_dashboard_css');
	wp_enqueue_script('WPbootstrap3_js');
	wp_enqueue_style('WPbootstrap3_css');
}
function UKMsystemtools_newSeason( $messages ) {
	// Påmeldingssystemet må testes hver sesong!
	if( get_site_option('delta_is_tested') != get_site_option('season') ) {
		$messages[] = array(
			'level' 	=> 'alert-danger',
			'module'	=> 'System',
			'header'	=> 'Påmeldingssystemet må testes!',
			'link'		=> 'admin.php?page=UKMsystemtools_deltaTest'
		);
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

function UKMsystemtools_ssb_import() {
	$TWIGdata = array();
	require_once('controller/ssb_import.controller.php');
	echo TWIG('ssb_import.html.twig', $TWIGdata, dirname(__FILE__), true);
}

function UKMsystemtools_ssb_warning($MESSAGES) {
	// Kun gjør beregningen og vis advarselen i september
	// Prøver å gjøre minst mulig i disse funksjonene fordi de inkluderes hver page load.
	$m = date("m");
	if($m == 9) {
	#if(true) {
		require_once('controller/SSB/levendefodte.controller.php');
		$last = getlatestyearupdated();
		if ($last < date("Y")-1) {
		#if (true) {
			$MESSAGES[] = array( 'level' 	=> 'alert-warning',
								'module'	=> __('System', 'UKM'),
								'header' 	=> 'SSB-statistikk må importeres. Nyeste data er for '.$last,
								'body' 		=> 'Dette er ikke krise, da det går noen år fra barn er født til de deltar på UKM :)',
								'link'		=> 'admin.php?page=UKMsystemtools_ssb_import');
		}
	}
	return $MESSAGES;
}
