<?php
require_once('UKMconfig.inc.php');

@session_start();

require_once('UKM/inc/dropbox.inc.php');


if( isset( $_GET['state'] ) && isset( $_GET['code'] ) ) {
	$accessToken = $DROPBOX->getAuthHelper()->getAccessToken($_GET['code'], $_GET['state'], DROPBOX_ENDPOINT);
	echo '<h1>Access granted!</h1>'
		.'<p>Save this accesstoken as "DROPBOX_AUTH_ACCESS_TOKEN" in UKMconfig.inc.php: <pre>'. $accessToken->getToken() .'</pre></p>'
		.'<p><a href="?success">Then go to this page</a>';
	die();
} elseif( !defined('DROPBOX_AUTH_ACCESS_TOKEN') || empty('DROPBOX_AUTH_ACCESS_TOKEN') ) {
	header("Location: ". $DROPBOX->getAuthHelper()->getAuthUrl( DROPBOX_ENDPOINT ));
	exit();
} else {
	echo '<h1>Access token stored!</h1>'
		.'<p>Close this tab.</p>';
	die();
}