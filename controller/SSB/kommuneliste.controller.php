<?php

require_once('UKM/curl.class.php');
require_once('UKM/fylker.class.php');
$difi = new DIFI();
$difi->setResource('regioner/kommuner');
$kommuner = parseKommuneData($difi->getAllPages());

$kommunerViHar = array();
$sql = new SQL("SELECT * FROM smartukm_kommune");
$res = $sql->run();
while ($row = SQL::fetch($res)) {
	$kommunerViHar[$row['id']] = array('name' => utf8_encode($row['name']), 'idfylke' => $row['idfylke']);
}

$TWIGdata['fylker'] = new fylker();
$TWIGdata['kommunerViIkkeHar'] = array_diff_key($kommuner, $kommunerViHar);
$TWIGdata['kommunerViHarSomIkkeFinnesLenger'] = array_diff_key($kommunerViHar, $kommuner);

function parseKommuneData($kommuner) {
	$kommuneListe = array();
	foreach($kommuner as $kommune) {
		$kommuneListe[(int)$kommune->kode] = $kommune;
	}
	return $kommuneListe;
}

class DIFI {
	const API_URL = 'http://hotell.difi.no/api/json/ssb/';
	private $resource;

	public function __construct() {
	}

	public function setResource($resource) {
		$this->resource = $resource;
		return $this;
	}

	public function getAllPages() {
		$url = self::API_URL . $this->resource;
		$curl = new UKMCURL();
		$res = $curl->process($url);

		if(!is_object($res)) {
			return false;
		}

		// Data is now an array of entries
		$data = $res->entries;
		$pages = $res->pages;

		for($i = 2; $i <= $pages; $i++) {
			$pagedURL = $url.'?page='.$i;
			$res = $curl->process($pagedURL);
			$data = array_merge($data, $res->entries);
		}

		return $data;
	}
}