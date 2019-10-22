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
require_once('UKM/Autoloader.php');
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
        $scripts[] =
            add_menu_page(
                'UKM Norge Systemverktøy',
                'System',
                'superadmin',
                'UKMsystem_tools',
                ['UKMsystem_tools', 'renderAdmin'],
                'dashicons-admin-generic', #//ico.ukm.no/system-16.png',
                22
            )
        ;

        $season =
            add_submenu_page(
                'UKMsystem_tools',
                'Ny sesong',
                'Ny sesong',
                'superadmin',
                'UKMsystem_tools_season',
                ['UKMsystem_tools', 'renderSeason']
            )
        ;
        $scripts[] = $season;
        add_action(
            'admin_print_styles-' . $season,
            ['UKMsystem_tools', 'scripts_and_styles_season']
        );

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
    }

    public static function scripts_and_styles_season() {
        wp_enqueue_script('TwigJS');

        if( $_GET['action'] == 'website_clean' ) {
            wp_enqueue_script(
                'UKMsystem_tools_websiteClean',
                plugin_dir_url(__FILE__) . 'js/website_clean.js'
            );
        }
        if( $_GET['action'] == 'website_create' ) {
            wp_enqueue_script(
                'UKMsystem_tools_websiteCreate',
                plugin_dir_url(__FILE__) . 'js/website_create.js'
            );
        }
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
        $messages = static::filterMessagesSeason($messages);
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
        /** SSB: Levendefødte-API må eksponeres for view */
        $levendefodte = new Levendefodte();

        require_once('controller/api/SSB/levendefodte.controller.php');
        $last = $levendefodte->getLatestYearUpdated();
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

    /**
     * Varsle admin hvis det er på tide å sette opp ny sesong
     *
     * @param Array $messages
     * @return Array $messages
     */
    public static function filterMessagesSeason($messages) {
        // Etter juli må ny sesong settes opp
        if( 7 < (int)date('m') && get_site_option('season') == date('Y') ) {
            $messages[] = array(
                'level' 	=> 'alert-danger',
                'module'	=> 'System',
                'header'	=> 'NY SESONG MÅ SETTES OPP!',
                'link'		=> 'admin.php?page=UKMsystem_tools_season'
            );
        }
        
        return $messages;
    }


    /**
     * Ny sesong-admin
     *
     * @return void
     */
    public static function renderSeason() {
        if( isset( $_GET['action'] ) ) {
            $action = 'season/'. basename( $_GET['action'] );
        } else {
            $action = 'season/home';
        }
        static::setAction( $action );
        static::renderAdmin();
    }
}

UKMsystem_tools::init(__DIR__);
UKMsystem_tools::hook();