<?php

require_once('UKM/API/SSB.class.php');

### KREVER tabellen ssb_kommune_areal
# Tabellen krever feltene kommune_id og kommune_navn.
$kommuneareal = new stdClass();
$import = new kommuneAreal();

if(isset($_POST['postform']) AND $_POST['postform'] == 'kommuneareal_update') {	
	$log = $import->get_data($_POST['year']);

	if(is_string($log)) {
		$message = new stdClass();
		$message->level = "danger";
		$message->header = $log;
		$kommuneareal->message = $message;
	}
	else {
		$message = new stdClass();
		$message->level = "success";
		$message->header = 'Importerer kommuneareal-data for år '.$_POST['year'].'.';
		$TWIGdata['message'] = $message;
		$kommuneareal->log = $log;
	}	

	#var_dump($kommuneareal->log);
}


$TWIGdata['kommuneareal'] = $kommuneareal;

class kommuneAreal {
	function get_data($year) {
		$this->year = $year;

		$this->kommuneareal = new KommuneArealImport();
		$this->kommuneareal->year = $year;
		$this->kommuneareal->buildQuery();
		/*echo '<pre>';
		echo $levendefodte->buildQuery();
		echo '</pre>';*/

		$result = $this->kommuneareal->run();
		
		/*echo '<pre>';
		echo var_dump($result);
		echo '</pre>';*/
		$kommunedata = $this->get_data_from_kommune_result($result);
		#var_dump($kommunedata);
		$log = $this->addMissingKommuner($kommunedata);

		if(!in_array($year, $this->get_all_years())) {
			// Dobbeltsjekk at vi har mottatt data for dette året før vi gjør dette.
			if(!empty($kommunedata))
				$this->add_year_column($year);
		}

		$log[] = $this->update_db($kommunedata, $year);
		#var_dump($log);
		return $log;
	}

	private function addMissingKommuner($kommunedata) {
		$log = array();
		$log[] = 'Finner kommuner som mangler fra tabellen.';

		$qry = new SQL("SELECT * FROM ssb_kommune_areal");
		$res = $qry->run();
		$kommuner = [];
		while ($row = mysql_fetch_array($res)) {
			$kommuner[$this->kommuneareal->getSSBifiedKommuneID($row['kommune_id'])] = $row['kommune_navn'];
		}

		$missing = array_diff_key($kommunedata, $kommuner);
		
		if(empty($missing)) {
			$log[] = '<b>Ingen kommuner mangler fra ssb_kommune_areal-tabellen, fortsetter...</b>';
			return $log;
		}
		else {
			$log[] = '<b>Følgende kommuner mangler fra ssb_kommune_areal-tabellen:</b>';
			foreach($missing as $id => $val) 
				$log[] = 'ID: '.$id .' - importerer.';
			#$log[] = var_export($missing, true);
		}	

		## Finn navn og fylke på manglende kommuner
		$qry = new SQL("SELECT * FROM smartukm_kommune");
		$res = $qry->run();
		$kommuneListe = [];
		$fylkeListe = [];
		while($row = SQL::fetch($res)) {
			$id = $this->kommuneareal->getSSBifiedKommuneID($row['id']);
			$kommuneListe[$id] = $row['name'];
			$fylkeListe[$id] = $row['idfylke'];
		}

		$missing2 = array_diff_key($kommunedata, $kommuneListe);
		if(!empty($missing2)) {
			$log[] = '<b>Kommuner fra SSB som ikke finnes i smartukm_kommune: </b>';
			foreach($missing2 as $id => $val) 
				$log[] = 'ID: '.$id;
		}

		## Legg til manglende i databasen!
		foreach($missing as $k_id => $value) {
			$sql = new SQLins("ssb_kommune_areal");
			$sql->add("kommune_id", (int)$k_id);
			if(isset($fylkeListe[$k_id]))
				$sql->add("fylke_id", $fylkeListe[$k_id]);
			if(isset($kommuneListe[$k_id])) 
				$sql->add("kommune_navn", $kommuneListe[$k_id]);
			$sql->run();
		}

		return $log;
	}

	# Returnerer true ved suksess, eller et array med objekter som beskriver elementene som mislyktes.
	function update_db($kommunedata, $year) {
		$log = array();
		$log[] = "Oppdaterer databasen...";
		#var_dump($kommunedata);
		if(null == $kommunedata || null == $year) {
			return "Kunne ikke hente data for år ".$year.".";
		}
		foreach ($kommunedata as $k_id => $areal) {
			if(null == $k_id || null === $areal) {
				var_dump($k_id);
				var_dump($areal);
				return "Kan ikke oppdatere uten k_id eller areal!";
			}
			$qry = new SQLins('ssb_kommune_areal', array('kommune_id' => $k_id));	
			$qry->add($year, $areal);
			$res = $qry->run();
			
			$log_entry = new stdClass();
			$log_entry->id = $k_id;
			$log_entry->antall = $antall;
			if($res != 1 && $qry->error()) {	
				$log_entry->success = false;
				$log_entry->message = $qry->error() . ' med areal '.$areal . ' og year = '.$year;
				#$log_entry->message = $qry->debug();
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
		$qry = "ALTER TABLE ssb_kommune_areal ADD `".$year."` DOUBLE(8,3) NOT NULL";

		$con = mysql_connect(UKM_DB_HOST, UKM_DB_WRITE_USER, UKM_DB_PASSWORD);
		mysql_select_db(UKM_DB_NAME, $con);
		$res = mysql_query($qry);
		if(false == $res) 
			echo mysql_error($con);
		return $res;
	}

	# Returnerer et array med kommune-ID som nøkkel for hver verdi.
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

	function get_all_years() {
		require_once('UKM/sql.class.php');

		$sql = new SQL("DESCRIBE ssb_kommune_areal");

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
}