<?php
/**
 * 2019-11-21 - Marius Mandal
 * Scriptet itererer over smartukm_kommune, og lagrer
 * path i tabellen, heller enn at klassen genererer det hver gang.
 * Dette gjør at vi kan håndtere kommuner med samme navn (og path).
 */

ini_set('display_errors',true);
require_once('UKM/Autoloader.php');

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Geografi\Kommune;

$fetch = new Query(
    Kommune::getLoadQuery()    
);
$result = $fetch->run();

while( $row = Query::fetch( $result ) ) {
    try {
        $kommune = new Kommune($row);
    } catch( Exception $e ) {
        // I tilfelle "falske fylker" i gammel tabell, 
        // hopp over de
        continue;
    }
    $update = new Update(
        'smartukm_kommune',
        [
            'id' => $row['id']
        ]
    );
    $update->add('path', $kommune->getPath());
    $update->run();
}
echo 'done';