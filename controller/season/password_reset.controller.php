<?php

use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;

require_once('UKM/Autoloader.php');

if( isset($_GET['do'] ) ) {
    global $wpdb;

    $results = $wpdb->get_results('SELECT `user_email` FROM `' . $wpdb->prefix . 'users`');
    if ($results) {
        foreach ($results as $data) {

            try {
                $user = User::loadById( $data->user_email );
            } catch( Exception $e ) {
                var_dump( $data );
                throw $e;
            }

            // UKM Norge-brukeren får overleve
            if( $user->getId() != 1 ) {
                WriteUser::setPassord( $user, wp_generate_password(120, true, true) );
                WriteUser::deaktiver( $user );
            }
        }
    }
}