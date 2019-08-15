<?php
/* 
Plugin Name: UKM System Tools
Plugin URI: http://www.ukm-norge.no
Description: Network admin system-verktøy for import av postnummer, ssb-tall osv
Author: UKM Norge / M Mandal 
Version: 2.0
Author URI: http://mariusmandal.no
*/


require_once('UKM/wp_modul.class.php');

class UKMsystem_tools extends UKMWPmodul
{
    public static $action = 'dashboard';
    public static $path_plugin = null;

    /**
     * Register hooks
     */
    public static function hook()
    {
        add_action(
            'wp_ajax_UKMsystem_tools_ajax',
            ['UKMsystem_tools', 'ajax']
        );

        add_action(
            'network_admin_menu',
            ['UKMsystem_tools', 'meny'],
            200
        );
    }

    /**
     * Add menu
     */
    public static function meny()
    {
        /**
         * Menyvalget SYSTEM
         */
        $scripts = [
            add_menu_page(
                'UKM Norge Systemverktøy',
                'System',
                'superadmin',
                'UKMsystem_tools',
                ['UKMsystem_tools', 'renderAdmin'],
                'dashicons-admin-generic', #//ico.ukm.no/system-16.png',
                22
            )
        ];

        $scripts[] = add_submenu_page(
            'UKMsystem_tools',
            'Oppdater kortadresser',
            'Oppdater kortadresser',
            'superadministrator',
            'UKMsystemtools_modrewrite',
            'UKMsystemtools_modrewrite'
        );
        $scripts[] = add_submenu_page(
            'UKMsystem_tools',
            'Test påmelding',
            'Test påmelding',
            'superadministrator',
            'UKMsystemtools_deltaTest',
            'UKMsystemtools_deltaTest'
        );
        $scripts[] = add_submenu_page(
            'UKMsystem_tools',
            'Importer SSB-data',
            'Importer SSB-data',
            'superadministrator',
            'UKMsystemtools_ssb_import',
            'UKMsystemtools_ssb_import'
        );

        /**
         * Menyvalget NETTVERKET
         */
        $meny_administratorer = add_submenu_page(
            'index.php',
            'Administratorer',
            'Administratorer',
            'superadmin',
            'UKMsystem_tools_admins',
            ['UKMsystem_tools', 'renderAdministratorer']
        );
        add_action(
            'admin_print_styles-' . $meny_administratorer,
            ['UKMsystem_tools', 'administratorer_scripts_and_styles'],
            10000
        );
        $scripts[] = $meny_administratorer;


        foreach ($scripts as $page) {
            add_action(
                'admin_print_styles-' . $page,
                ['UKMsystem_tools', 'scripts_and_styles']
            );
        }
    }

    /**
     * Scripts og stylesheets som skal være med i alle
     * system tools-sider
     *
     * @return void
     */
    public static function scripts_and_styles()
    {
        wp_enqueue_style('UKMwp_dash_css');
        wp_enqueue_script('WPbootstrap3_js');
        wp_enqueue_style('WPbootstrap3_css');

        wp_enqueue_script(
            'UKMsystem_tools',
            plugin_dir_url(__FILE__) . 'js/UKMsys_tools.js'
        );
    }

    /**
     * Scripts og stylesheets som skal være med i alle
     * system tools-sider
     *
     * @return void
     */
    public static function administratorer_scripts_and_styles()
    {
        wp_enqueue_script(
            'UKMsystem_tools_admins',
            plugin_dir_url(__FILE__) . 'js/nettverket/administratorer.js'  
        );
    }

    public static function renderAdministratorer() {
        
        if (isset($_GET['action'])) {
            $_GET['action'] = 'administratorer-'. basename($_GET['action']);
        } else {
            $_GET['action'] = 'administratorer';
        }
    
        static::setAction('nettverk/'. $_GET['action']);
        static::renderAdmin();
    }
}

UKMsystem_tools::init(__DIR__);
UKMsystem_tools::hook();

/*
if (is_admin()) {
    add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_check_postnumber_updates');
    add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_newSeason');
    add_filter('UKMWPNETWDASH_messages', 'UKMsystemtools_ssb_warning');
   add_action('wp_ajax_UKMsystem_tools_ajax', 'UKMsystem_tools_ajax');
}

function ajax()
{
    header('Content-Type: application/json');

    if (!isset($_POST['controller'])) {
        echo json_encode(
            [
                'success' => false,
                'message' => 'Missing controller'
            ]
        );
        die();
    }
    if (!isset($_POST['module'])) {
        echo json_encode(
            [
                'success' => false,
                'message' => 'Missing module'
            ]
        );
        die();
    }

    $controller =  __DIR__ . '/ajax/' .  basename($_POST['module']) . '/' . basename($_POST['controller']) . '.ajax.php';

    if (!file_exists($controller)) {
        echo json_encode(
            [
                'success' => false,
                'message' => 'Controller does not exist'
            ]
        );
        die();
    }
    require_once($controller);
    die();
}


function UKMsystem_tools_admins()
{

    if (isset($_GET['action'])) {
        $_GET['action'] = basename($_GET['action']);
    } else {
        $_GET['action'] = 'list';
    }

    switch ($_GET['action']) {
        case 'add':
            $VIEW = 'admins-add';
            break;

        default:
            $VIEW = 'admins';
            break;
    }
    require_once('controller/nettverket/' . $VIEW . '.controller.php');
    echo TWIG('nettverket/' . $VIEW . '.html.twig', $TWIGdata, __DIR__);
}


function UKMcloudflare_cache()
{
    $view_data = [];
    wp_enqueue_script('UKMsystem_tools_addMore_js', plugin_dir_url(__FILE__) . 'js/addMore.js');
    require_once('controller/cloudflare.controller.php');

    echo TWIG('cloudflare.twig.html', $view_data, dirname(__FILE__), true);
}

function UKMsystemtools()
{
    $TWIGdata = [];
    $action = isset($_GET['action']) ? $_GET['action'] : 'home';

    require_once('controller/' . $action . '.controller.php');
    $VIEW = $action;

    echo TWIG($VIEW . '.twig.html', $TWIGdata, dirname(__FILE__), true);
}

function UKMdropbox()
{
    $TWIGdata = [];
    require_once('controller/dropbox.controller.php');
    $VIEW = 'dropbox';

    echo TWIG($VIEW . '.html.twig', $TWIGdata, dirname(__FILE__), true);
}
function UKMflickr()
{
    require_once('controller/flickr.controller.php');
}


function UKMsystemtools_scripts_and_styles()
{
    
}
function UKMsystemtools_newSeason($messages)
{
    // Påmeldingssystemet må testes hver sesong!
    if (get_site_option('delta_is_tested') != get_site_option('season')) {
        $messages[] = array(
            'level'     => 'alert-danger',
            'module'    => 'System',
            'header'    => 'Påmeldingssystemet må testes!',
            'link'        => 'admin.php?page=UKMsystemtools_deltaTest'
        );
    }
    return $messages;
}
function UKMsystemtools_check_postnumber_updates($messages)
{
    $last_postnumber_timestamp = get_site_option('ukm_systemtools_last_postnumber_update', false);
    if ($last_postnumber_timestamp && is_numeric($last_postnumber_timestamp)) {
        $last_year = intval(date("Y", intval($last_postnumber_timestamp, 10)));
        $current_year = intval(date("Y"));

        if ($last_year < $current_year) {
            $messages[] = array(
                'level'     => 'alert-warning',
                'module'    => __('System', 'UKM'),
                'header'    => sprintf(__('Postnummer må oppdateres, sist oppdatert %s', 'UKM'), date("d.m.Y", $last_postnumber_timestamp)),
                'body'         => __('Rett problemet under system-verktøy', 'UKM'),
                'link'        => 'admin.php?page=UKMsystemtools'
            );
        }
    } else if ($last_postnumber_timestamp == false) {
        $messages[] = array(
            'level'     => 'alert-error',
            'module'    => __('System', 'UKM'),
            'header'    => __('Postnummer må oppdateres', 'UKM'),
            'body'         => __('Rett problemet under system-verktøy', 'UKM'),
            'link'        => 'admin.php?page=UKMsystemtools'
        );
    }

    return $messages;
}


function UKMsystemtools_modrewrite()
{
    require_once('controller/modrewrite.controller.php');
}
function UKMsystemtools_deltaTest()
{
    $TWIGdata = array();
    require_once('controller/testdelta.controller.php');
    echo TWIG('testdelta.html.twig', $TWIGdata, dirname(__FILE__), true);
}

function UKMsystemtools_ssb_import()
{
    $TWIGdata = array();
    require_once('controller/ssb_import.controller.php');
    echo TWIG('ssb_import.html.twig', $TWIGdata, dirname(__FILE__), true);
}

function UKMsystemtools_ssb_warning($MESSAGES)
{
    // Kun gjør beregningen og vis advarselen i september
    // Prøver å gjøre minst mulig i disse funksjonene fordi de inkluderes hver page load.
    $m = date("m");
    if ($m == 9) {
        #if(true) {
        require_once('controller/SSB/levendefodte.controller.php');
        $last = get_latest_year_updated();
        if ($last < date("Y") - 1) {
            #if (true) {
            $MESSAGES[] = array(
                'level'     => 'alert-warning',
                'module'    => __('System', 'UKM'),
                'header'     => 'SSB-statistikk må importeres. Nyeste data er for ' . $last,
                'body'         => 'Dette er ikke krise, da det går noen år fra barn er født til de deltar på UKM :)',
                'link'        => 'admin.php?page=UKMsystemtools_ssb_import'
            );
        }
    }
    return $MESSAGES;
}
*/
