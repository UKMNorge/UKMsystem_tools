<?php


use UKMNorge\API\SSB\Levendefodte;
use UKMNorge\Wordpress\Blog;

@session_start();

require_once('UKMconfig.inc.php');

/** SSB-SETUP  **/
/** SSB: Levendefødte-API må eksponeres for view */
require_once('UKM/Autoloader.php');

$levendefodte = new Levendefodte();
UKMsystem_tools::addViewData('SSB_levendefodte', $levendefodte);

UKMsystem_tools::addViewData('current_theme', Blog::getCurrentTheme()->name);

/** API-TJENESTER **/
/** DROPBOX */
require_once('api/dropbox.controller.php');

/** FLICKR */
require_once('api/flickr.controller.php');

/** CLOUDFLARE */
require_once('api/cloudflare.controller.php');

/** POST-HANDLERS **/
if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    /* IMPORT AV POSTNUMMER */
    if( isset( $_POST['import_postnummer'] ) ) {
        require_once('api/postnummer.controller.php');
    }

    /* IMPORT FRA SSB : LEVENDEFØDTE */
    if( isset( $_POST['levendefodte_year'] ) ) {
        require_once('api/SSB/levendefodte.controller.php');
    }
}