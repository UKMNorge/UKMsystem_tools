<?php

require_once('UKM/API/SSB/kommuneAreal.class.php');

$kommuneareal = new KommuneAreal();

if(isset($_POST['postform']) AND $_POST['postform'] == 'kommuneareal_update') {	
	$log = $kommuneareal->getData($_POST['year']);

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
}

$TWIGdata['kommuneareal'] = $kommuneareal;