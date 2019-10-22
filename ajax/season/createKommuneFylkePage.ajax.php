<?php

use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

$selector = $_POST['type'] . '_' . $_POST['id'];
$success = true;

switch ($_POST['type']) {
    case 'fylke':
        $fylke = Fylker::getById($_POST['id']);
        $navn = $fylke->getNavn();

        $path = '/' . Blog::sanitizePath($fylke->getNavn()) .'/';
        if (Blog::isAvailablePath($path)) {
            $action = 'opprett fylkesside';
            $color = 'primary';

            try {
                $blog_id = Blog::opprettForFylke( $fylke );
                $success = true;
                $color = 'success';
            } catch( Exception $e ) {
                $success = false;
                $color = 'danger';
            }
        } else {
            $action = 'fylkesside eksisterer';
            $color = 'info';
        }
        break;
    
    // OPPRETT FIKS KOMMUNE
    case 'kommune':
        try {
            $kommune = new Kommune($_POST['id']);
            $color = 'primary';
            $action = 'create_kommune';
            $navn = $kommune->getNavn();

            if (Blog::isAvailablePath($kommune->getPath())) {
                $action = 'opprett lokalside';
                try {
                    $blog_id = Blog::opprettForKommune( $kommune );
                    $success = true;
                    $color = 'success';
                } catch( Exception $e ) {
                    $success = false;
                    $color = 'danger';
                }
            } else {
                $action = 'lokalside eksisterer';
            }
        } catch (Exception $e) {
            $action = 'ukjent_kommune';
            $color = 'danger';
            $success = false;
        }
        break;
}

UKMsystem_tools::addResponseData(
    [
        'action' => $action,
        'color' => $color,
        'navn' => $navn,
        'success' => $success,
        'selector' => $selector,
        'type' => $_POST['type'],
        'path' => $path,
    ]
);
