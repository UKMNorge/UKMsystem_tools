<?php
use GuzzleHttp\Exception\ClientException;

@session_start();
require_once('UKM/inc/dropbox.inc.php');

try {
	$account = $DROPBOX->getCurrentAccount();
	$TWIGdata['client'] = $account;
	$TWIGdata['authenticated'] = true;
} catch( Exception $e ) {
	$TWIGdata['authenticated'] = false;
	$TWIGdata['authURL'] = $DROPBOX->getAuthHelper()->getAuthUrl( DROPBOX_ENDPOINT );
}