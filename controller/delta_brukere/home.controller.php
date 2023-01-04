<?php
use UKMNorge\Database\SQL\Query;

$tel = $_GET['tel'] ? $_GET['tel'] : null;

if($tel) {
    $deltaBrukere = [];
    
    $SQL = new Query(
        "SELECT id, first_name, last_name from ukm_user WHERE phone = '#tel'",
        ['tel' => $tel],
        'ukmdelta'
    );
    
    $res = $SQL->run();

    while ($b = Query::fetch($res)) {
        $deltaBrukere[] = $b;
    }
    
    var_dump($deltaBrukere);
}

UKMsystem_tools::addViewData([]);