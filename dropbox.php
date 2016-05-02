<?php
require_once('UKMconfig.inc.php');

@session_start();

require_once('UKM/inc/dropbox.inc.php');

if( isset( $_GET['state'] ) && isset( $_GET['code'] ) ) {
	list( $accessToken ) = $webAuth->finish( $_GET );
	echo '<h1>Access granted!</h1>'
		.'<p>Save this accesstoken as "DROPBOX_AUTH_ACCESS_TOKEN" in UKMconfig.inc.php: <pre>'. $accessToken .'</pre></p>'
		.'<p><a href="?success">Then go to this page</a>';
	die();
} elseif( !defined('DROPBOX_AUTH_ACCESS_TOKEN') || empty('DROPBOX_AUTH_ACCESS_TOKEN') ) {
	$authUrl = $webAuth->start();
	header("Location: ". $authUrl);
	exit();
} else {
	die('Lukk denne fanen');
}