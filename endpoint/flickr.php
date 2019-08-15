<?php

use MariusMandal\Flickr\App;
use MariusMandal\Flickr\Exception;
use MariusMandal\Flickr\Auth;
use MariusMandal\Flickr\Flickr;

@session_start();

require_once('Flickr/autoloader.php');
require_once('UKMconfig.inc.php');
require_once('UKM/inc/twig-admin.inc.php');



// SET LOG LEVEL (throw, default:log)
Exception::setLogMethod('throw');

// SET APP DETAILS
App::setId(FLICKR_API_KEY);
App::setSecret(FLICKR_API_SECRET);
App::setPermissions('write');


if (defined('FLICKR_AUTH_USER') && defined('FLICKR_AUTH_TOKEN') && defined('FLICKR_AUTH_SECRET')) {
    // As long as input parameters is valid or null, it is always best to test
    Auth::authenticate( FLICKR_AUTH_USER, FLICKR_AUTH_TOKEN, FLICKR_AUTH_SECRET );
}

$flickr = new Flickr();
$TWIGdata = [];


// Does flickr have the authentication variables?
if( !$flickr->hasAuthentication() ) {
    // User returned from flickr - store tokens
    if( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) ) {
        try {
            $accessData = $flickr->getAccessToken( $_GET['oauth_token'], $_GET['oauth_verifier'] );
            $template = 'store';
            $data = $accessData->getData();
            
            $TWIGdata['OAUTH_USER'] = $data['user_nsid'];
            $TWIGdata['OAUTH_TOKEN'] = $data['oauth_token'];
            $TWIGdata['OAUTH_SECRET'] = $data['oauth_token_secret'];
        } catch( \Exception $e ) {
            $TWIGdata['error'] = $e->getMessage();
            try {
                $TWIGdata['link'] = $flickr->getAuthenticationUrl( FLICKR_ENDPOINT );
                $template = 'error';
            } catch( \Exception $e ) {
                $template = 'retry';
            }
        }
    } else {
        try {
            header("Location: ". $flickr->getAuthenticationUrl( FLICKR_ENDPOINT ));
            exit();
        } catch ( \Exception $e ) {
            $template = 'retry';
        }
    } 
    echo TWIG(
        'flickr/'. $template .'.html.twig', 
        $TWIGdata,
        dirname(__DIR__)
    );
    die();
}

echo 'har auth - vis det';
/*
echo '<h2>Utfører login-test</h2>';
$test = new Request\Test\Login();
$res = $test->execute();
echo '<pre>';
var_dump( $res->getDataRaw() );
echo '</pre>';
echo '</div>';
}
*/