<?php
define('PHPEXCEL_ROOT', '');
/** PHPExcel_IOFactory */
require_once('UKM/inc/excel.inc.php');

$objPHPExcel = PHPExcel_IOFactory::load($_FILES['postnummer']['tmp_name']);
$sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

$header_row = $sheetData[1];

$index_postnummer = array_search('Postnummer', $header_row);
$index_poststed = array_search('Poststed', $header_row);
$index_kommune = array_search('Kommunenummer', $header_row);

if( $index_kommune == $index_poststed && $index_poststed == $index_postnummer ) {
    UKMsystem_tools::getFlash()->add(
        'danger',
        'Fant ikke kolonnene Postnummer, Poststed og Kommunenummer i rad 1, og kan derfor ikke gjennomføre import.'
    );
} else {
    $lagt_til = [];
    // Loop all rows
    for ($row = 2; $row < sizeof($sheetData)+1; $row++) {
        $postnummer = $sheetData[$row][$index_postnummer];
        $poststed = mb_convert_case($sheetData[$row][$index_poststed], MB_CASE_TITLE);
        $kommune = $sheetData[$row][$index_kommune];

        $insert = new SQLins('smartukm_postalplace');
        $insert->add('postalcode', $postnummer);
        $insert->add('postalplace', $poststed);
        $insert->add('k_id', $kommune);

        // Prøv insert
        try {
            $res = $insert->run();
            if ($res) {
                $lagt_til[] = '<code>' . $postnummer . ' ' . $poststed . '</code>';
            }
        } catch (Exception $e) {
            // Hvis feilet fordi den finnes - oppdater
            if ($e->getCode() == 901001) {
                $update = new SQLins('smartukm_postalplace', array('postalcode' => $postnummer));
                $update->add('k_id', $kommune);
                $update->run();
            }
            // Ukjent feil: stopp
            else {
                throw $e;
            }
        }
    }

    if( sizeof( $lagt_til ) > 0 ) {
        UKMsystem_tools::getFlash()->add(
            'success',
            sizeof( $lagt_til ) .' av '. (sizeof( $sheetData )-1) .' postnummer ble lagt til. <br />'.
            implode(' ', $lagt_til)
        );
        update_site_option('ukm_systemtools_last_postnumber_update', time());
    } else {
        UKMsystem_tools::getFlash()->add(
            'info',
            'Ingen av '. (sizeof( $sheetData )-1) .' postnummer ble lagt til.'
        );
    }
}