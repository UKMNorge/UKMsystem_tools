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
}

function UKMST_menu() {
	$page = add_menu_page('UKM Norge Systemverktøy', 'System', 'superadmin', 'UKMsystemtools','UKMsystemtools', 'http://ico.ukm.no/system-16.png',22);

	$subpage1 = add_submenu_page( 'UKMsystemtools', 'Kontakteksport', 'Kontakteksport', 'superadministrator', 'UKMkontakteksport', 'UKMkontakteksport' );

    add_action( 'admin_print_styles-' . $page, 'UKMsystemtools_scripts_and_styles' );
    add_action( 'admin_print_styles-' . $subpage1, 'UKMsystemtools_scripts_and_styles' );
}

function UKMsystemtools() {
	$TWIGdata = [];
	$action = isset($_GET['action']) ? $_GET['action'] : 'home';

	require_once('controller/'. $action .'.controller.php');
	$VIEW = $action;

	echo TWIG( $VIEW.'.twig.html', $TWIGdata, dirname(__FILE__), true);
}

function UKMsystemtools_scripts_and_styles() {
	wp_enqueue_style( 'UKMsupport_css', plugin_dir_url( __FILE__ ) . 'UKMsupport.css');
	wp_enqueue_script( 'UKMsupport_js', plugin_dir_url( __FILE__ ) . 'UKMsupport.js');
	
	wp_enqueue_style('UKMwp_dashboard_css');
	wp_enqueue_script('WPbootstrap3_js');
	wp_enqueue_style('WPbootstrap3_css');
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
				'link'		=> '?page=UKMsystemtools'
			);
		}

	} else if ( $last_postnumber_timestamp == false ) {
		$messages[] = array(
			'level' 	=> 'alert-error',
			'module'	=> __('System', 'UKM'),
			'header'	=> __('Postnummer må oppdateres','UKM'),
			'body' 		=> __('Rett problemet under system-verktøy', 'UKM'),
			'link'		=> '?page=UKMsystemtools'
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