<?php

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Write;
use UKMNorge\Geografi\Kommune;

require_once('UKM/Autoloader.php');

// superseed skal aldri være tom string.
$fixNull = new Update(
    'smartukm_kommune',
    ['superseed' => '']
);
$fixNull->add('superseed', NULL);
$res = $fixNull->run();


// Finn kommuner som har overtatt for en annen
$superseed = new Query(
    "SELECT TRIM( 
        TRAILING ',' FROM GROUP_CONCAT( 
            TRIM( TRAILING ',' FROM `superseed` )
        )
    ) AS `superseeds`
    FROM `smartukm_kommune`
    WHERE `superseed` IS NOT NULL"
);
$superseed = $superseed->run('field');

// Finn kommuner som har blitt overtatt
$old = new Query(
    "SELECT *
    FROM `smartukm_kommune`
    WHERE `id` IN(#idlist)
    AND `active` != 'false'",
    ['idlist' => $superseed]
);
$res = $old->run();

while( $row = Query::fetch( $res ) ) {
    $kommuner[] = new Kommune( $row );
}

if( isset($_GET['do'] ) ) {
    $deactivate = new Write(
        "UPDATE `smartukm_kommune`
        SET `active` = 'false'
        WHERE `id` IN(#idlist)
        AND `active` != 'false'",
        [
            'idlist' => $superseed
        ]
    );
    $res = $deactivate->run();
}

UKMsystem_tools::addViewData('kommuner', $kommuner);
UKMsystem_tools::addViewData('preview', !isset($_GET['do']));