<?php

use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

$selector = $_POST['type'] . '_' . $_POST['id'];
$success = true;

$fylkeEllerKommune = null;

foreach(Fylker::getAll() as $fylke) {
    $link = '/' . $fylke->getLink() . '/';
    $blogId = get_blog_id_from_url( "ukm.dev", $link );

    // Legg til kontaktisde for fylke
    leggTilKontaktside($blogId, $fylke->getNavn());
    
    foreach($fylke->getKommuner()->getAll() as $kommune) {
        $kommuneLink = $kommune->getPath();
        $kommuneBlogId = get_blog_id_from_url( "ukm.dev", $kommuneLink );

        // Legg til kontaktisde for kommune
        leggTilKontaktside($kommuneBlogId, $kommune->getNavn());
    }

}

function leggTilKontaktside($blog_id, $navn) {
    try {
        if($blog_id == 0) {
            var_dump($navn . ' har blogg 0');
            // die;
            return;
        }
        Blog::leggTilSider(
            $blog_id,
            [
                ['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner']
                ]
            );
        var_dump($navn . ' OK');
    } catch(Exception $e) {
        var_dump($e);
    }
}