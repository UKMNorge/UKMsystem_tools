<?php

require_once('UKM/curl.class.php');

interface SSBapi_interface {

	const API_URL = 'http://data.ssb.no/api/v0/no/';

	# Denne funksjonen velger hvilken SSB-ressurs spørringen skal kjøre mot (oftest en tabell).
	# Argument må være på formen 'ressurs/ressurs-id', ie 'table/04231' for Levendefødte.
	public function setResource($resource);

	# Dette er funksjonen som kjører spørringen mot SSBs systemer.
	public function run();

	# Dette er funksjonen som returnerer selve spørringen som et JSON-objekt.
	# Kan echoes ut for debugging.
	public function query();
}

class SSBapi implements SSBapi_interface {
	private $resource = null;

	// The main query-object.
	private $query = null;

	public function setResource($resource) {
		$this->resource = $resource;
	}
	
	public function run() {
		if(null == $this->resource) {
			throw new Exception("Kan ikke kjøre en SSB-spørring mot ukjent ressurs.");
		}

		# Build full API-url
		$url = self::API_URL . $this->resource;

		$curl = new UKMCURL();
		$curl->post($this->query());
		$result = $curl->process($url);
		return $result;
	}

	# SSB krever at kommune-ID er et firesifret tall med fylkes_id og et kommunetall, med 0-padding for hvert ènsifrede tall (i.e. 0104 = Moss). 
	# Vår kommune-ID paddes med 0 i front til ett firesifret tall, da det allerede er fylke_id.kommunetall med 0 der nødvendig internt.
	public function getSSBifiedKommuneID($k_id) {
		$k_id = (string)$k_id;
		if(count($k_id) < 4)
			$k_id = str_pad($k_id, 4, '0', STR_PAD_LEFT);	
		return $k_id;
	}

	public function query() {
		return json_encode($this->query);
	}

	public function addQueryParameter($code, $filter, $values) {
		$param = new stdClass();
		$param->code = $code;
		$param->selection = new stdClass();
		$param->selection->filter = $filter;
		$param->selection->values = $values;

		$this->query->query[] = $param;
	}

	public function addResponseFormat($format) {
		$this->query->response = new stdClass();
		$this->query->response->format = $format;
	}
}

class Levendefodte_data extends SSBapi {

	public $year;
	public $table = '04231';

	public function buildQuery() {
		$this->setResource('table/'.$this->table);

		$this->addQueryParameter("Region", "item", $this->_getAllKommuner());
		$this->addQueryParameter("Kjonn", "all", array()); # La SSB summere summene for kjønn for oss :D
		$this->addQueryParameter("ContentsCode", "item", array("Levendefodte"));
		$this->addQueryParameter("Tid", "item", array($this->year));
		$this->addResponseFormat("json-stat");

	}

	# Returnerer et array av kommuneIDer
	private function _getAllKommuner() {
		$qry = new SQL("SELECT id FROM smartukm_kommune");
		$res = $qry->run();
		$kommuner = array();
		while ($row = SQL::fetch($res)) {
			$kommuner[] = $this->getSSBifiedKommuneId($row['id']);
		}
		return $kommuner;
		# Test:
		#return array($this->getSSBifiedKommuneId(104),$this->getSSBifiedKommuneId(105));
	}
}

class KommuneArealImport extends SSBapi {
	public $year;
	public $table = '09280';

	public function buildQuery() {
		$this->setResource('table/'.$this->table);

		$this->addQueryParameter("Region", "item", $this->_getAllKommuner());
		$this->addQueryParameter("Arealtype", "all", array());
		$this->addQueryParameter("ContentsCode", "item", array("Areal1"));
		$this->addQueryParameter("Tid", "item", array($this->year));
		$this->addResponseFormat("json-stat");
	}

	# Returnerer et array av kommuneIDer
	private function _getAllKommuner() {
		$qry = new SQL("SELECT id FROM smartukm_kommune");
		$res = $qry->run();
		$kommuner = array();
		while ($row = SQL::fetch($res)) {
			$kommuner[] = $this->getSSBifiedKommuneId($row['id']);
		}
		return $kommuner;
		# Test:
		#return array($this->getSSBifiedKommuneId(104),$this->getSSBifiedKommuneId(105));
	}
}