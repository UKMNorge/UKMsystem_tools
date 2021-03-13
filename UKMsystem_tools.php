<?php
/* 
Plugin Name: UKM System Tools
Plugin URI: http://www.ukm-norge.no
Description: Network admin system-verktøy for import av postnummer, ssb-tall osv
Author: UKM Norge / M Mandal 
Version: 2.0
Author URI: http://mariusmandal.no
*/

use UKMNorge\API\SSB\Levendefodte;
use UKMNorge\Wordpress\Modul;

require_once('UKM/Autoloader.php');

class UKMsystem_tools extends Modul
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
            ['UKMsystem_tools', 'meny', 'sjekk_mapper'],
            -10
        );

        add_filter('UKMWPNETWDASH_messages', ['UKMsystem_tools', 'filterMessages']);
        
        // IMPORTANT: dette må kalles network_admin_meny action!
        static::sjekk_mapper();
    }

    /**
     * Sjekker om mappene er opprettet for dette året.
     */
    public static function sjekk_mapper() {

        // Opening a directory 
        $downloadMappe = "../../../../var/www/download/"; // word og excel mapper
        
        // TEST FUNKJSONALITET: Øk den med + 1 
        $aarNaa = (int) date('Y');
        $oldAar = get_site_option('UKM_download_folder_last_created');
        
        // get_site_option finnes ikke, legg til 0.
        $oldAar = empty($oldAar) ? 0 : $oldAar;

        // Hvis år er større enn lagret site_option år, så må opprettes nye mapper, de gamle mappene må slettes og site_option må oppdateres
        if($aarNaa > $oldAar) {
            // Oppdater update_site_option, sett $arrNaa
            update_site_option('UKM_download_folder_last_created', ((int) date('Y')) );

            foreach(array('word', 'excel', 'zip') as &$mappe) {
                // Slette alle gamle mapper og filer
                static::delete_all_inside_directory($downloadMappe . $mappe);

                // Legg til mapper med navn $aarNaa i $mappe
                try{
                    mkdir($downloadMappe . $mappe .'/' . $aarNaa, 0777);
                } catch(Exception $e) {
                    throw new Exception('Mappe ' . $mappe . ' ble ikke opprettet!');
                }
            }            
        }
    }

    /**
     * Slett alle filler og sub-mapper i mappe
     * Denne metoden bruker rekursjon
     * PGA sikkerhetsmessige årsaker må alle filene i mappen slettes før man kan slette mappen selv
     *
     * @param string $dirname
     * @return bool
     */
    private static function delete_all_inside_directory($dirname, $startDirname = null) {
        // Lagre hovedmappe når funksjonen starter
        if($startDirname == null) {
            $startDirname = $dirname;
        }
        
        // Om det er mappe så åpen det
        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }
        if (!$dir_handle) {
            return false;
        }
        // For hver fil som er ikke selv filen eller tilbake, slett det.
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file)) {
                    unlink($dirname."/".$file);
                }
                else {
                    // Kall rekursivt
                    static::delete_all_inside_directory($dirname.'/'.$file, $startDirname);
                }
            }
        }
        // Lukk mappen
        closedir($dir_handle);
        // Ikke slet hovedmappe
        if($dirname != $startDirname) {
            rmdir($dirname);
        }
        // Returner true til slutt
        return true;
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