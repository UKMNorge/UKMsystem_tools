<?php
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
require_once('UKMconfig.inc.php');


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
    slettDeltaBruker($slettId);
}

UKMsystem_tools::addViewData(
    [
        'deltaBrukere' => $deltaBrukere, 
        'tel' => $tel,
        'slettId' => $slettId,
    ]
);


function slettDeltaBruker($id) {
    $passord = $_POST['password'] ? $_POST['password'] : null;

    // Sjekk passord
    if($passord != null && verifyPassword($passord)) {

        $update = new Update(
            'ukm_user',
            [
                'id' => $id
            ],
            'ukmdelta'
        );

        $email = uniqid() . '@' . uniqid() . '.unique';

        $update->add('first_name', '-');
        $update->add('last_name', '-');
        $update->add('phone', null);
        $update->add('username', $email);
        $update->add('username_canonical', $email);
        $update->add('email', $email);
        $update->add('email_canonical', $email);
        $update->add('birthdate', null);
        $update->add('foresatt_navn', '-');
        $update->add('foresatt_mobil', null);
        $update->add('address', null);
        $update->add('post_number', null);
        $update->add('post_place', null);
        $update->add('facebook_id', null);
        $update->add('facebook_id_unencrypted', null);
        $update->add('facebook_access_token', null);


        $res = $update->run();
        if($res) {
            echo '<br><h1>Brukeren er slettet!</h1>';
        }
        else {
            echo '<br><h1>Det var en feil og brukeren er ikke slettet!</h1>';
        }

    }
    else {
        echo '<br><h1>Feil passord!</h1>';
    }
}

function verifyPassword($passord) : bool {
    return UKM_DELTA_SLETT_BRUKER === $passord;
}