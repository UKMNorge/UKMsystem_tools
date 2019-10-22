<?php
@session_start();

require_once('UKMconfig.inc.php');
require_once('UKM/inc/twig-admin.inc.php');

try {
    require_once('UKM/inc/dropbox.inc.php');

    if( isset( $_GET['state'] ) && isset( $_GET['code'] ) ) {
        $accessToken = $DROPBOX->getAuthHelper()->getAccessToken($_GET['code'], $_GET['state'], DROPBOX_ENDPOINT);
        $template = 'store';
        $data = $accessToken->getToken();
    } elseif( !defined('DROPBOX_AUTH_ACCESS_TOKEN') || empty('DROPBOX_AUTH_ACCESS_TOKEN') ) {
        header("Location: ". $DROPBOX->getAuthHelper()->getAuthUrl( DROPBOX_ENDPOINT ));
        exit();
    } else {
        $template = 'success';
        $data = null;
    }


    echo TWIG(
        'dropbox/'. $template .'.html.twig', 
        [
            'data' => $data
        ],
        dirname(__DIR__)
    );
    die();

} catch( Exception $e ) {
    echo TWIG(
        'dropbox/error.html.twig', 
        [
            'error' => $e->getMessage()
        ],
        dirname(__DIR__)
    );
    die();
}