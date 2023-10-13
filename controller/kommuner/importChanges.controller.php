<?php

use UKMNorge\API\SSB\Klass;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Twig\Twig as TwigAdmin;

require_once('UKM/Autoloader.php');
TwigAdmin::enableDebugMode();

$dataset = new Klass();
// 131 er "Standard for kommuneinndeling"
$dataset->setClassificationId("131");
$startDato = new DateTime(date('Y')."-01-01");
$sluttDato = new DateTime(date('Y')."-12-31");

$startDato = new DateTime("2020-01-01");
$sluttDato = new DateTime("2024-01-02");

$dataset->setRange($startDato, $sluttDato);
$dataset->includeFutureChanges(true);

// echo '<pre>';
// var_dump($dataset->getCodes());
// echo '</pre>';


$dataEndringer = $dataset->getChanges();


// Sorter og grupper
$endringer = [];
foreach($dataEndringer as $dataEndring) {
	foreach($dataEndring as $endring) {        

        if( $endring->oldCode == $endring->newCode ) {
            $sql = new Update(
                'smartukm_kommune',
                ['id' => $endring->newCode]
            );
            $endring->action = 'update';
        } else {
            $sql = new Insert('smartukm_kommune');
            $endring->action = 'insert';
        }
        $sql->add('id', $endring->newCode);
        $sql->add('idfylke', substr( $endring->newCode, 0, 2));
        $sql->add('name', $endring->newName);
        $sql->add('alternate_name', $endring->newName);
        $sql->add('ssb_name', $endring->newName);
        $sql->add('active', true);
        
        $old_kommune = new Kommune($endring->oldCode);
        if($old_kommune->getId() != false) {
            $sql->add('path', $old_kommune->getPath());
        }
        

        // Hent tidligere info, og legg til, heller enn å overskrive
        $superseed = new Query(
            "SELECT `superseed` 
            FROM `smartukm_kommune` 
            WHERE `id` = '#id'",
            [
                'id' => $endring->newCode
            ]
        );
        $superseed = $superseed->run('field');
        if( !empty( $superseed ) ) {
            $superseed .= ','.$endring->oldCode;
        } else {
            $superseed = $endring->oldCode;
        }
        $sql->add('superseed', $superseed);

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
        $endringer[$endring->newName][] = $endring;
	}
}

UKMsystem_tools::addViewData('preview', !isset($_GET['do']));
UKMsystem_tools::addViewData('startDato', $startDato);
UKMsystem_tools::addViewData('sluttDato', $sluttDato);
UKMsystem_tools::addViewData('endringer', $endringer);