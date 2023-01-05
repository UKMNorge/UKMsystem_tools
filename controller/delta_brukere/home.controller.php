<?php
use UKMNorge\Database\SQL\Query;

$tel = $_POST['tel'] ? $_POST['tel'] : null;
$slettId = $_POST['slettId'] ? $_POST['slettId'] : null;

// Hvis mobilnummer er tilgjengelig, prøv å finne brukeren
if($tel) {
    $deltaBrukere = [];
    
    $SQL = new Query(
        "SELECT id, first_name, last_name, phone from ukm_user WHERE phone = '#tel'",
        ['tel' => $tel],
        'ukmdelta'
    );
    
    $res = $SQL->run();

    while ($b = Query::fetch($res)) {
        $deltaBrukere[] = $b;
    }
}
else if($slettId) {
    var_dump('Slett bruker med id ' . $slettId);
}

UKMsystem_tools::addViewData(
    [
        'deltaBrukere' => $deltaBrukere, 
        'tel' => $tel,
        'slettId' => $slettId,
    ]
);


