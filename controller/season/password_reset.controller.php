<?php

use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;

require_once('UKM/Autoloader.php');

if( isset($_GET['do'] ) ) {
    global $wpdb;

    $results = $wpdb->get_results('SELECT `user_email` FROM `' . $wpdb->prefix . 'users`');
    if ($results) {
        foreach ($results as $data) {

            $user = User::loadByEmail( $data->user_email );

            // UKM Norge-brukeren får overleve
            if( $user->getId() != 1 ) {
                WriteUser::setPassord( $user, wp_generate_password(120, true, true) );
                WriteUser::deaktiver( $user );
            }
        }
    }
}