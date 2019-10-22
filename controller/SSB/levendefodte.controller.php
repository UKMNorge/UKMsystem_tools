<?php

require_once('UKM/Autoloader.php');

$levendefodte = new Levendefodte();
### LEVENDEFØDTE:
if(isset($_POST['postform']) AND $_POST['postform'] == 'levendefodte_update') {
    // Hent, behandle og lagre data
    $log = $levendefodte->getData($_POST['year']);
    
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