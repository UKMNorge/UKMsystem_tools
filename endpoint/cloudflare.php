<?php
require_once('UKM/cloudflare.class.php');

#var_dump($_GET);
#Cloudflare cache page
if (isset($_GET['delete'])) {
	$files = array();

	$num = $_GET['num'];
	while ($num > 0) {
		$files[] = $_GET['file'.$num];
		$num--;
	}
	#var_dump($files);

	$cf = new cloudflare();
	$res = $cf->purge($files);
	if ($res == true) {
		$view_data['message'] = array('success', 'Filene ble fjernet fra cachen!');
	}
	else {
		$view_data['message'] = array('danger', 'Klarte ikke slette filene! <br>Cloudflare sier: ' . $cf->result->errors[0]->message);
	}
}
else if (isset($_GET['deleteAll'])) {
	$cf = new cloudflare();
	$res = $cf->purgeAll();
	#var_dump($res);
	if($res == true) {
		$view_data['message'] = array('success', 'Cachen ble clearet!');
	}
	else {
		// Noe feilet
		$view_data['message'] = array('danger', 'Klarte ikke cleare cachen! <br>Cloudflare sier: ' . $cf->result->errors[0]->message);
	}
	#var_dump($cf);
}