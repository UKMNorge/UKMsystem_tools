<?php

use UKMNorge\Wordpress\User;

require_once('UKM/Wordpress/User.class.php');

$success = User::isAvailableUsername( $_POST['username'] );

echo json_encode(
    [
        'success' => $success,
        'message' => 'Brukernavnet er '. ($success ? 'ledig' : 'allerede i bruk'),
        'username' => $_POST['username'],
        'count' => $_POST['count']
    ]
);
