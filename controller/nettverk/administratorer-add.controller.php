<?php

use UKMNorge\Wordpress\User;

require_once('UKM/fylker.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/WriteUser.class.php');

UKMsystem_tools::addViewData('fylke', fylker::getById($_GET['fylke']));

/* BRUKEREN HAR SKREVET INN E-POSTADRESSE */
if (true || $_SERVER['REQUEST_METHOD'] == 'POST') {
    // DEBUG
    $_POST['email'] = 'testbruker@ukm.dev'; # Ledig bruker
    $_POST['email'] = 'mariusmandal@gmail.com'; # Eksisterende bruker
    #$_POST['email'] = 'ukm norge@gmail.com'; # Ledig bruker, opptatt brukernavn


    // Hent bruker eller opprett placeholder-objekt
    try {
        $user = User::loadByEmail($_POST['email']);
    } catch (Exception $e) {
        $user = User::createEmpty();
    }

    UKMsystem_tools::addViewData('doAdd',true);

    // Bruker eksisterer ikke - fyll ut e-post og brukernavn
    if (!$user->isReal()) {
        $user->setEmail($_POST['email']);
        $user->setUsername(
            substr(
                $_POST['email'],
                0,
                strpos($_POST['email'], '@')
            )
        );
    }
    UKMsystem_tools::addViewData('user',$user);
}