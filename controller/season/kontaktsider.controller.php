<?php

use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Wordpress\Blog;

die('Die er aktivert, deaktiver det i koden på linje 5 i ' . __FILE__);

require_once('UKM/Autoloader.php');
$resultater = [];
foreach(Fylker::getAll() as $fylke) {
    $link = '/' . $fylke->getLink() . '/';
    $blogId = get_blog_id_from_url( UKM_HOSTNAME, $link );

    // Legg til kontaktisde for fylke
    $resultater[] = leggTilKontaktside($blogId, $fylke->getNavn());
    
    foreach($fylke->getKommuner()->getAll() as $kommune) {
        $kommuneLink = $kommune->getPath();
        $kommuneBlogId = get_blog_id_from_url( UKM_HOSTNAME, $kommuneLink );

        // Legg til kontaktisde for kommune
        $resultater[] = leggTilKontaktside($kommuneBlogId, $kommune->getNavn());
    }
}



function leggTilKontaktside($blog_id, $navn) {
    try {
        if($blog_id == 0) {
            return ['navn' => $navn, 'success' => false, 'msg' => 'Blogg finnes ikke (mulig nettsiden eksisterer ikke), ERROR'];
        }
        Blog::leggTilSider(
            $blog_id,
            [
                ['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner']
            ]
        );
        return ['navn' => $navn, 'success' => true, 'msg' => 'OK'];
    } catch(Exception $e) {
        throw $e;
    }
}


UKMsystem_tools::addViewData('resultater', $resultater);