<?php

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;

require_once('UKM/Autoloader.php');


if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    foreach( $_POST['tidligere'] as $kommune_id => $tidligere ) {
        if( empty( $kommune_id ) ) {
            continue;
        }

        if( strlen( $tidligere ) == 4 && strpos($tidligere,',') === false ) {
            $tidligere .= ',';
        }

        $update = new Update(
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

        // Deaktiver kommunene som har blitt overtatt
        $array_tidligere = explode(',', $tidligere );
        foreach( $array_tidligere as $id_tidligere ) {
            $sql = new Update(
                'smartukm_kommune',
                [
                    'id' => $id_tidligere
                ]
            );
            $sql->add('active',false);
            $sql->run();
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


$fylker = Fylker::getAll();
$alle_kommuner = [];

foreach( $fylker as $fylke ) {
    $kommuner = $fylke->getKommuner();

    foreach( $kommuner as $kommune ) {
        if( $kommune->harTidligere() ) {
            $flyttet = new Query(
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
            $flyttet = new Query(
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
        while( $row = Query::fetch($flyttet) ) {
            $kommune->lignende[] = new Kommune( $row );
        }
        $alle_kommuner[ $fylke->getId() ][] = $kommune;
    }
}

UKMsystem_tools::addViewData('fylker', $fylker );
UKMsystem_tools::addViewData('kommuner', $alle_kommuner);