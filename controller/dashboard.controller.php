<?php


use GuzzleHttp\Exception\ClientException;

@session_start();

/** SSB-SETUP  **/
/** SSB: Levendefødte-API må eksponeres for view */
require_once('UKM/API/SSB/levendefodte.class.php');
$levendefodte = new Levendefodte();
UKMsystem_tools::addViewData('SSB_levendefodte', $levendefodte);

/** SSB: Kommuneareal-API må eksponeres for view */
require_once('UKM/API/SSB/kommuneAreal.class.php');
$kommuneareal = new KommuneAreal();
UKMsystem_tools::addViewData('SSB_kommuneareal', $kommuneareal);


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
    /* IMPORT FRA SSB : KOMMUNEAREAL*/
    if( isset( $_POST['areal_year'] ) ) {
        require_once('api/SSB/areal.controller.php');
    }
}