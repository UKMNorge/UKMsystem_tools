<?php

use UKMNorge\API\SSB\Klass;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Twig\Twig as TwigAdmin;

require_once('UKM/fylker.class.php');
require_once('UKM/API/SSB/Klass.php');
require_once('UKM/Twig/Twig.php');
require_once('UKM/Database/SQL/select.class.php');
TwigAdmin::enableDebugMode();

$dataset = new Klass();
$dataset->setPageSize(1000);
// 131 er "Standard for kommuneinndeling"
$dataset->setClassificationId("131");
$startDato = new DateTime("1900-01-01");
$sluttDato = new DateTime((date('Y')+50)."-01-01");

$dataset->setRange($startDato, $sluttDato);
$kommuner = $dataset->getCodes()->codes;

$nye_kommuner = [];
foreach($kommuner as $kommune) {
    $eksisterer = new Query(
        "SELECT * FROM `smartukm_kommune`
        WHERE `id` = '#id'",
        ['id' => (int)$kommune->code]
    );
    $eksisterer = $eksisterer->run();

    // Har vi kommunen fra før er det bortkastet å importere den
    if(Query::numRows($eksisterer) > 0 ) {
        continue;
    }
    // Hopp over Oslo
    if( $kommune->code == '0301' ) {
        continue;
    }

    $sql = new Insert('smartukm_kommune');

    $sql->add('id', $kommune->code);
    $sql->add('idfylke', substr( $kommune->code, 0, 2));
    $sql->add('name', $kommune->name);
    $sql->add('alternate_name', $kommune->name);
    $sql->add('ssb_name', $kommune->name);
    
    if( new DateTime( $kommune->validToInRequestedRange ) > new DateTime() ) {
        $kommune->aktiv = true;
    } else {
        $kommune->aktiv = false;
    }
    $sql->add('active', $kommune->aktiv);

    $kommune->query = $sql->debug();
    if( isset( $_GET['do'] ) ) {
        try {
            $res = $sql->run();
        } catch( Exception $e ) {
            // Insert-ID == 0 er null stress i dette tilfellet
            if( !$e->getCode() == 901001 ) {
                throw $e;
            }
        }            
    }
    $nye_kommuner[] = $kommune;
}

UKMsystem_tools::addViewData('preview', !isset($_GET['do']));
UKMsystem_tools::addViewData('startDato', $startDato);
UKMsystem_tools::addViewData('sluttDato', $sluttDato);
UKMsystem_tools::addViewData('kommuner', $nye_kommuner);