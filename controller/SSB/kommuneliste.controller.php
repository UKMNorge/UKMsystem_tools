<?php

require_once('UKM/fylker.class.php');
require_once('UKM/API/DIFI/DIFI.class.php');

$difi = new DIFI();
$difi->setResource('regioner/kommuner');
$kommuner = $difi::parseKommuneData($difi->getAllPages());

$kommunerViHar = array();
$sql = new SQL("SELECT * FROM smartukm_kommune");
$res = $sql->run();
while ($row = SQL::fetch($res)) {
	$kommunerViHar[$row['id']] = array('name' => $row['name'], 'idfylke' => $row['idfylke']);
}

$TWIGdata['fylker'] = new fylker();
$TWIGdata['kommunerViIkkeHar'] = array_diff_key($kommuner, $kommunerViHar);
$TWIGdata['kommunerViHarSomIkkeFinnesLenger'] = array_diff_key($kommunerViHar, $kommuner);