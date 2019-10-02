<?php

use UKMNorge\Database\SQL\Insert;

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    foreach( $_POST['tidligere'] as $kommune_id => $tidligere ) {
        if( empty( $kommune_id ) ) {
            continue;
        }

        // TODO WHAAAT?! Hvorfor lagrer den tomme felt?

        $update = new Insert(
            'smartukm_kommune',
            [
                'id' => $kommune_id
            ]
        );
        if( empty( $tidligere ) ) {
            $tidligere = null;
        }
        $update->add('superseed', $tidligere);
        
        $affected_rows = $update->run();
        if( $affected_rows > 1 ) {
            echo 'OPPDATERTE '. $affected_rows .' RADER: <code>'. $update->debug() .'</code>';
        }
    }
    UKMsystem_tools::getFlashbag()->add(
        'success',
        'Lagret kommune-relasjoner'
    );

    UKMsystem_tools::getFlash()->add(
        'danger',
        'Iterer over alle kommuner, og sett de som inaktive hvis de er med i $tidligere ovenfor'
    );
}


$fylker = fylker::getAll();
$alle_kommuner = [];

foreach( $fylker as $fylke ) {
    $kommuner = $fylke->getKommuner();

    foreach( $kommuner as $kommune ) {
        if( $kommune->harTidligere() ) {
            $flyttet = new SQL(
                "SELECT *
                FROM `smartukm_kommune`
                WHERE `name` = '#name'
                AND `id` != '#id'
                AND `id` NOT IN(#overtatt)",
                [
                    'name' => $kommune->getNavn(),
                    'id' => $kommune->getId(),
                    'overtatt' => $kommune->getTidligereIdList()
                ]
            );
        } else {
            $flyttet = new SQL(
                "SELECT *
                FROM `smartukm_kommune`
                WHERE `name` = '#name'
                AND `id` != '#id'",
                [
                    'name' => $kommune->getNavn(),
                    'id' => $kommune->getId()
                ]
            );
        }

        $flyttet = $flyttet->run();
        while( $row = SQL::fetch($flyttet) ) {
            $kommune->lignende[] = new kommune( $row );
        }
        $alle_kommuner[ $fylke->getId() ][] = $kommune;
    }
}

UKMsystem_tools::addViewData('fylker', $fylker );
UKMsystem_tools::addViewData('kommuner', $alle_kommuner);