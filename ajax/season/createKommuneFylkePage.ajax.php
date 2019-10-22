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
            $blog_id = Blog::getIdByPath($path);
            Blog::oppdaterFraFylke( $blog_id, $fylke );
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
            $path = $kommune->getPath();
            if (Blog::isAvailablePath($path)) {
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
                $blog_id = Blog::getIdByPath($path);
                Blog::oppdaterFraKommune( $blog_id, $kommune );
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
