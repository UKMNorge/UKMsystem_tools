<?php

require_once('UKM/inc/dropbox.inc.php');

$client = new Dropbox\Client( DROPBOX_AUTH_ACCESS_TOKEN, $appName, 'UTF-8' );

try {
	$test = $client->getAccountInfo();
	$TWIGdata['client'] = $test;
	$TWIGdata['authenticated'] = true;
} catch( Dropbox\Exception_InvalidAccessToken $e ) {
	$authUrl = $webAuth->start();
	$TWIGdata['authenticated'] = false;
	$TWIGdata['authURL'] = $authUrl;
}