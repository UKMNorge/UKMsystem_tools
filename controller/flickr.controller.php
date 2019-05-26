<?php

namespace MariusMandal\Flickr;

require_once('Flickr/autoloader.php');
require_once('UKMconfig.inc.php');
@session_start();

define('FLICKR_REDIR_URL', 'https://ukm.no/wp-content/plugins/UKMsystem_tools/controller/flickr.controller.php');

echo '<h1>Flickr-setup</h1>';
// SET LOG LEVEL (throw, default:log)
Exception::setLogMethod('throw');

// SET APP DETAILS
App::setId(FLICKR_API_KEY);
App::setSecret(FLICKR_API_SECRET);
App::setPermissions('write');

// As long as input parameters is valid or null, it is always best to test
Auth::authenticate( FLICKR_AUTH_USER, FLICKR_AUTH_TOKEN, FLICKR_AUTH_SECRET );

$flickr = new Flickr();

// Does flickr have the authentication variables?
if( !$flickr->hasAuthentication() ) {
	out( 'Authentication details is null.' );
	// User returned from flickr - store tokens
	if( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) ) {
		try {
			$accessData = $flickr->getAccessToken( $_GET['oauth_token'], $_GET['oauth_verifier'] );
		} catch( \Exception $e ) {
			out( 'FLICKR ERROR: '. $e->getMessage() );
			out( '<a href="'. $flickr->getAuthenticationUrl( FLICKR_REDIR_URL ) .'">Authorize app</a>' );			
			die();
		}
		out( 'STORE OAUTH_USER, OAUTH_TOKEN and OAUTH SECRET FOR LATER APP AUTH' );
		var_dump( $accessData );
		die();
	}
	// Send user to flickr for authentication
	 else {
		out( '<a href="'. $flickr->getAuthenticationUrl( FLICKR_REDIR_URL ) .'" target="_blank">Authorize app</a>' );
		die();
	}
}

echo '<h2>Utfører login-test</h2>';
$test = new Request\Test\Login();
$res = $test->execute();
echo '<pre>';
var_dump( $res->getDataRaw() );
echo '</pre>';