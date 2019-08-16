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

        add_filter('UKMWPNETWDASH_messages', ['UKMsystem_tools', 'filterMessages']);
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

    public static function renderAdministratorer()
    {

        if (isset($_GET['action'])) {
            $_GET['action'] = 'administratorer-' . basename($_GET['action']);
        } else {
            $_GET['action'] = 'administratorer';
        }

        static::setAction('nettverk/' . $_GET['action']);
        static::renderAdmin();
    }


    /**
     * Filtrer meldinger i network admin, og varsle sys-admin ved bevov
     *
     * @param Array $messages
     * @return Array $messages
     */
    public static function filterMessages($messages)
    {
        $messages = static::filterMessagesDeltaTest($messages);
        $messages = static::filterMessagesPostal($messages);
        $messages = static::filterMessagesSSB($messages);
        return $messages;
    }

    /**
     * Påminnelse om at påmeldingssystemet må testes hver sesong
     *
     * @param Array $messages
     * @return Array $messages
     */
    public static function filterMessagesDeltaTest($messages)
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

    /**
     * Varsle admin hvis postnummer-tabellen ikke er oppdatert 
     * innenfor intervallet (hver sesong)
     *
     * @param Array $messages
     * @return Array $messages
     */
    public static function filterMessagesPostal($messages)
    {
        $last_postnumber_timestamp = get_site_option('ukm_systemtools_last_postnumber_update', false);
        if ($last_postnumber_timestamp && is_numeric($last_postnumber_timestamp)) {
            $last_year = intval(date("Y", intval($last_postnumber_timestamp, 10)));
            $current_year = intval(date("Y"));

            if ($last_year < $current_year) {
                $messages[] = array(
                    'level'     => 'alert-warning',
                    'module'    => 'System',
                    'header'    => 'Postnummer må oppdateres, sist oppdatert ' . date("d.m.Y", $last_postnumber_timestamp),
                    'body'      => 'Rett problemet under system-verktøy',
                    'link'      => 'admin.php?page=UKMsystemtools'
                );
            }
        } else if ($last_postnumber_timestamp == false) {
            $messages[] = array(
                'level'     => 'alert-error',
                'module'    => 'System',
                'header'    => 'Postnummer må oppdateres',
                'body'      => 'Rett problemet under system-verktøy',
                'link'      => 'admin.php?page=UKMsystemtools'
            );
        }

        return $messages;
    }

    /**
     * Varsle admin hvis tall for levendefødte ikke er importert
     * Det haster jo ikke, da det tar noen år før nyfødte deltar på UKM,
     * men det er greit å gjøre regelmessig likevel-
     * 
     * Beregningen kjøres kun i september, da dette påvirker page-load
     *
     * @param Array $messages
     * @return Array $messages
     */
    public static function filterMessagesSSB($messages)
    {
        if (date("m") != 9) {
            return $messages;
        }
        require_once('controller/SSB/levendefodte.controller.php');
        $last = get_latest_year_updated();
        if ($last < date("Y") - 1) {
            $messages[] = array(
                'level'     => 'alert-warning',
                'module'    => 'System',
                'header'    => 'SSB-statistikk må importeres. Nyeste data er for ' . $last,
                'body'      => 'Dette er ikke krise, da det går noen år fra barn er født til de deltar på UKM :)',
                'link'      => 'admin.php?page=UKMsystemtools_ssb_import'
            );
        }
        return $messages;
    }
}

UKMsystem_tools::init(__DIR__);
UKMsystem_tools::hook();

/*
   add_action('wp_ajax_UKMsystem_tools_ajax', 'UKMsystem_tools_ajax');

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


function UKMsystemtools_modrewrite()
{
    require_once('controller/modrewrite.controller.php');
}

*/