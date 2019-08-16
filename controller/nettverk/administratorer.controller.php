<?php

use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\WriteAdministrator;
use UKMNorge\Samtykke\Write;

require_once('UKM/fylker.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/WriteUser.class.php');
require_once('UKM/Nettverk/WriteAdministrator.class.php');

UKMsystem_tools::addViewData('fylker', fylker::getAll());

if( isset( $_GET['removeAdmin'] ) ) {
    $fylke = Fylker::getById( $_GET['fylke'] );
    $admin = $fylke->getAdministratorer()->get( $_GET['removeAdmin'] );

    $res = WriteAdministrator::fjernFraOmrade( $admin, $fylke->getAdministratorer() );
    if( $res ) {
        UKMsystem_tools::getFlash()->add(
            'success',
            $admin->getUser()->getNavn() .' er fjernet som administrator for '. $fylke->getNavn()
        );
    }
}

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
            $created = true;
        } else {
            $created = false;
        }
        $user->setFirstName($_POST['first_name']);
        $user->setLastName($_POST['last_name']);
        $user->setPhone((int) $_POST['phone']);

        WriteUser::save($user);

        $fylke = Fylker::getById($_POST['fylke_id']);
        $administrator = new Administrator( $user->getId() );
        WriteAdministrator::leggTilIOmrade( $administrator, $fylke->getAdministratorer());

        UKMsystem_tools::getFlash()->add(
            'success',
            ($created ? 
                'Bruker er oppprettet for '. $user->getName() .' og '
                : $user->getName() . ' er ') .
            'lagt til som administrator for ' . $fylke->getNavn()
        );
    }
}
