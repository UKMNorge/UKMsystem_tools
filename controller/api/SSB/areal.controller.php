<?php

// Hent, behandle og lagre data
$log = $kommuneareal->getData($_POST['areal_year']);

if (is_string($log)) {
    UKMsystem_tools::getFlash()->add(
        'danger',
        $log
    );
} else {
    UKMsystem_tools::getFlash()->add(
        'success',
        'Importerte kommuneareal-data for ' . $_POST['areal_year'] . '. Sjekk at alle import-linjer i status-listen (der du startet importen) er grønne.'
    );
    UKMsystem_tools::addViewData('SSB_kommuneareal_log', $log );
}