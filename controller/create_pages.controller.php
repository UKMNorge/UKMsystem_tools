<?php

use UKMNorge\Wordpress\Blog;

echo '<div class="clearfix"></div>';
$limit = 100;

global $wpdb, $wp_rewrite;
$sites = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->blogs", array()));
$start = $_GET['start'];
$i = 0;

if( $i < $start ) {
	echo '<h2>Hopper over</h2>';
		
}
foreach ( $sites as $site ) {
	$i++;
	if( $i == $start ) {
		echo '<h2>Oppdaterer</h2>';
	}
	if($i==$start+$limit)
		die('<a href="?page='.$_GET['page'].'&action='.$_GET['action'] .'&start='.($start+$limit).'">Neste '.$limit.'</a>');
	if($i < $start){
		echo ''. $i .', ';
		continue;
    }
    if( $site->blog_id == 1 ) {
        continue;
    }
	$url = get_site_url($site->blog_id);
	echo 'Side '. $i .': '. $site->blog_id .' - '. get_blog_option($site->blog_id, 'blogname') . '<br />';
    Blog::setStandardInnhold(intval($site->blog_id));
}	

echo '<strong>Totalt oppdatert '. $i .' sites</strong>';
die('<a href="?page='.$_GET['page'].'">Tilbake</a>');