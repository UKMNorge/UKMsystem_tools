<?php
echo '<div class="clearfix"></div>';
$limit = 150;

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
	$url = get_site_url($site->blog_id);
	echo 'Side '. get_blog_option($site->blog_id, 'blogname') . '<br />';
	switch_to_blog($site->blog_id);
	$wp_rewrite->init();
	$wp_rewrite->flush_rules();
	restore_current_blog();
}	

echo '<strong>Totalt oppdatert '. $i .' sites</strong>';
die('<a href="?page='.$_GET['page'].'">Tilbake</a>');