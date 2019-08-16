<?php

use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;

require_once('UKM/fylker.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/WriteUser.class.php');

UKMsystem_tools::addViewData('fylker', fylker::getAll());

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $user = User::loadByEmail($_POST['email']);
    } catch (Exception $e) {
        $user = User::createEmpty();
    }

    // OPPRETT + OPPDATER BRUKER
    if (isset($_POST['user_id'])) {
        // Opprett bruker
        if ($_POST['user_id'] == 'new') {
            $user->setEmail($_POST['email']);
            $user->setUsername($_POST['username']);
        }
        $user->setFirstName($_POST['first_name']);
        $user->setLastName($_POST['last_name']);
        $user->setPhone((int) $_POST['phone']);

        WriteUser::save($user);

        $fylke = Fylker::getById($_POST['fylke_id']);

        UKMsystem_tools::getFlash()->add(
            'info',
            $user->getName() . ' er lagt til som bruker (og etter hvert administrator for ' . $fylke->getNavn() . ')'
        );
    }
}
