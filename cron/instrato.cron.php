<?php

use UKMNorge\Database\SQL\Query;
use UKMNorge\Wordpress\User;

require_once('UKM/Autoloader.php');

$sql = new Query(
    "SELECT `wp_user_id`
    FROM `ukm_nettverk_admins`"
);
$res = $sql->run();

while( $row = Query::fetch( $res ) ) {
    $user = User::loadByIdInStandaloneEnvironment((Int) $row['wp_user_id'] );
    if( !$user->hasInstratoKey() ) {
        $user->generateInstratoKey();
    }
}
echo 'done';