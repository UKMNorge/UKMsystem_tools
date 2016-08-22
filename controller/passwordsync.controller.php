<?php
if( !isset($_GET['sync'] ) ) {
	echo TWIG('passwordsync.twig.html', array(), dirname(dirname(__FILE__)), true);
	die();
}

global $wpdb;
$brukere = $wpdb->get_results("SELECT * FROM `ukm_brukere`", OBJECT);

echo '<h2>Synkroniserer brukere</h2>';
foreach( $brukere as $bruker ) {
	echo '<strong>ID: '. $bruker->wp_bid .'</strong><br />';
	echo ' &nbsp; Name: '. $bruker->b_name .' <br />';
	echo ' &nbsp; Password: '. $bruker->b_password .' <br />';
	wp_set_password( $bruker->b_password, $bruker->wp_bid );
}