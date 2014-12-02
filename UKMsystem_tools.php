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
}

function UKMST_menu() {
	$page = add_menu_page('UKM Norge Systemverktøy', 'System', 'superadmin', 'UKMsystemtools','UKMsystemtools', 'http://ico.ukm.no/system-16.png',22);
    add_action( 'admin_print_styles-' . $page, 'UKMsystemtools_scripts_and_styles' );
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