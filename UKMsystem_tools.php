<?php
/* 
Plugin Name: UKM System Tools
Plugin URI: http://www.ukm-norge.no
Description: Network admin system-verktøy for import av postnummer, ssb-tall osv
Author: UKM Norge / M Mandal 
Version: 2.0
Author URI: http://mariusmandal.no
*/

use UKMNorge\Nettverk\Administrator;

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
            ['UKMsystem_tools', 'nettverkMeny'],
            200
        );
        
        add_action(
            'user_admin_menu',
            ['UKMsystem_tools', 'meny'],
            200
        );

        add_filter('UKMWPNETWDASH_messages', ['UKMsystem_tools', 'filterMessages']);
    }

    public static function meny() {
        require_once('UKM/Nettverk/Administrator.class.php');
        $current_admin = new Administrator( get_current_user_id() );
        
        $scripts = [];
        
        // Hvis vedkommende er admin for ett eller flere fylker
        if( $current_admin->erAdmin('fylke') ) {
            $meny = ($current_admin->getAntallOmrader('fylke') == 1 ) ? 
                $current_admin->getOmrade('fylke')->getNavn() : 
                'Dine fylker';
            $scripts = [
                add_menu_page(
                    'GEO',
                    $meny,
                    'subscriber',
                    'UKMsystem_tools_fylke',
                    ['UKMsystem_tools', 'renderFylke'],
                    'dashicons-location-alt', #//ico.ukm.no/system-16.png',
                    22
                )
            ];
        }

        // Hvis vedkommende er admin for en eller flere kommuner
        if( $current_admin->erAdmin('kommune') ) {
            $meny = ($current_admin->getAntallOmrader('kommune') == 1 ) ? 
                $current_admin->getOmrade('kommune')->getNavn() : 
                'Dine kommuner';
            $scripts = [
                add_menu_page(
                    'GEO',
                    $meny,
                    'subscriber',
                    'UKMsystem_tools_kommune',
                    ['UKMsystem_tools', 'renderKommune'],
                    'dashicons-location', #//ico.ukm.no/system-16.png',
                    23
                )
            ];
        }

        foreach ($scripts as $page) {    
            add_action(
                'admin_print_styles-' . $page,
                ['UKMsystem_tools', 'administratorer_scripts_and_styles'],
                11000
            );
            add_action(
                'admin_print_styles-' . $page,
                ['UKMsystem_tools', 'scripts_and_styles']
            );
        }
    }

    /**
     * Add menu
     */
    public static function nettverkMeny()
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

    public static function renderFylke() {
        self::renderAdministratorer('fylke');
    }

    public static function renderAdministratorer( $SELECTION=false )
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
        $messages = static::filterMessagesPostal($messages);
        $messages = static::filterMessagesSSB($messages);
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