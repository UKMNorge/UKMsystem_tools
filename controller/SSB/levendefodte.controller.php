<?php

### LEVENDEFØDTE:
function checkUpdate($TWIGdata) {
	if(isset($_POST['postform']) AND $_POST['postform'] == 'levendefodte_update') {
		// Hent, behandle og lagre data
		$log = get_data($_POST['year']);
		
		if(is_string($log)) {
			$message = new stdClass();
			$message->level = "danger";
			$message->header = $log;
			$TWIGdata['message'] = $message;
		}
		else {
			$message = new stdClass();
			$message->level = "success";
			$message->header = 'Importerer levendefødte-data for år '.$_POST['year'].'.';
			$TWIGdata['message'] = $message;
			$TWIGdata['log'] = $log;
		}
	}
	return $TWIGdata;
}

function get_data($year) {
	require_once(__DIR__.'/../class/SSBapi.class.php');
	$levendefodte = new Levendefodte_data();
	$levendefodte->year = $year;
	$levendefodte->buildQuery();
	/*echo '<pre>';
	echo $levendefodte->buildQuery();
	echo '</pre>';*/

	$result = $levendefodte->run();
	
	/*echo '<pre>';
	echo var_dump($result);
	echo '</pre>';	*/
	$kommunedata = get_data_from_kommune_result($result);
	#var_dump($kommunedata);

	if(!in_array($year, get_all_years())) {
		// Dobbeltsjekk at vi har mottatt data for dette året før vi gjør dette.
		if(!empty($kommunedata))
			add_year_column($year);
	}

	$log = update_db($kommunedata, $year);
	return $log;
}

# Returnerer true ved suksess, eller et array med objekter som beskriver elementene som mislyktes.
function update_db($kommunedata, $year) {
	$log = array();
	if(null == $kommunedata || null == $year) {
		return "Kunne ikke hente data for år ".$year.".";
	}
	foreach ($kommunedata as $k_id => $antall) {
		if(null == $k_id || null === $antall) {
			var_dump($k_id);
			var_dump($antall);
			return "Kan ikke oppdatere uten k_id eller antall!";
		}
		$qry = new SQLins('ukm_befolkning_ssb', array('kommune_id' => $k_id));	
		$qry->add($year, $antall);
		$res = $qry->run();
		
		$log_entry = new stdClass();
		$log_entry->id = $k_id;
		$log_entry->antall = $antall;
		if($res != 1 && $qry->error()) {	
			$log_entry->success = false;
			$log_entry->message = $qry->error();
		} elseif($res != 1) {
			$log_entry->success = true;
			$log_entry->message = 'Ingen endring.';
		} 
		else {
			$log_entry->success = true;
			$log_entry->message = 'Suksess!';
		}
		$log[] = $log_entry;
		// Resett SQL-objekt for minnesparing?
		$qry = null;
	}

	if(empty($log))
		return true;
	return $log;
}

# Adds a year-column for the selected year.
function add_year_column($year) {
	require_once('UKMconfig.inc.php');

	$year = mysql_real_escape_string($year);
	$year = (int)$year;
	$qry = "ALTER TABLE ukm_befolkning_ssb ADD `".$year."` INTEGER NOT NULL";

	$con = mysql_connect(UKM_DB_HOST, UKM_DB_WRITE_USER, UKM_DB_PASSWORD);
	mysql_select_db(UKM_DB_NAME, $con);
	$res = mysql_query($qry);
	if(false == $res) 
		echo mysql_error($con);
	return $res;
}

# Returnerer et array med kommune-ID som nøkkel og antall levendefødte som verdi.
function get_data_from_kommune_result($results) {
	$kommunedata = array();
	// For hver kommune
	foreach($results->dataset->dimension->Region->category->index as $k_id => $position) {
		$kommunedata[$k_id] = $results->dataset->value[$position];
	}
	/*# Numeriske nøkler
	foreach($results->dataset->value as $key, $value) {
		$k_id = 
		$kommunedata[] 
	}*/
	#var_dump($kommunedata);
	return $kommunedata;
}

function get_latest_year_updated() {
	$years = get_all_years();
	return max($years);
}

// Finner kun manglende år mellom siste og første.
function get_missing_years() {
	$years = get_all_years();
	$max = max($years);
	$min = min($years);
	$missing = array();
	
	for ($year = $min; $year < $max; $year++) {
		if(!in_array($year, $years)) {
			$missing[] = $year;
		}
	}

	return $missing;
}

function get_all_years() {
	require_once('UKM/sql.class.php');

	$sql = new SQL("DESCRIBE ukm_befolkning_ssb");

	$res = $sql->run();
	$years = array();
	while($row = SQL::fetch($res)) {
		if(is_numeric($row['Field'])) {
			$years[] = $row['Field'];
		}
	}
	sort($years);
	return $years;
	# Test:
	#return array(2000, 2012);
}