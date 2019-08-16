<?php

// Hent, behandle og lagre data
$log = $levendefodte->getData($_POST['levendefodte_year']);

if (is_string($log)) {
    UKMsystem_tools::getFlash()->add(
        'danger',
        $log
    );
} else {
    UKMsystem_tools::getFlash()->add(
        'success',
        'Importerte levendefødte-data for ' . $_POST['levendefodte_year'] . '. Sjekk at alle import-linjer i status-listen (der du startet importen) er grønne.'
    );
    UKMsystem_tools::addViewData('SSB_levendefodte_log', $log );
}