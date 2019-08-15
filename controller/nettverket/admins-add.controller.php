<?php

require_once('UKM/fylker.class.php');
$TWIGdata['fylke'] = fylker::getById( $_GET['fylke'] );

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	$user = get_user_by('email',$_POST['email']);

	if( isset( $_POST['user_id'] ) ) {
		if( $_POST['user_id'] == 'new' && username_exists( $_POST['suername'] ) ) {
			$TWIGdata['error'] = 'Brukernavnet er opptatt. Prøv et annet.';
		}
		$user->first_name = $_POST['first_name'];
		$user->last_name = $_POST['last_name'];
		
	} else {
		$TWIGdata['doAdd'] = true;
		if( $user ) {
			$user->first_name = get_user_meta( $user->ID, 'first_name', true );
			$user->last_name = get_user_meta( $user->ID, 'last_name', true );
			$user->phone = get_user_meta( $user->ID, 'phone', true );
			$user->email = $user->data->user_email;
			$user->username = $user->data->user_login;
		} else {
			$user = new stdClass();
			$user->ID = 'new';
			$user->first_name = '';
			$user->last_name = '';
			$user->email = $_POST['email'];
			$user->username = substr(
				$_POST['email'],
				0,
				strpos( $_POST['email'], '@' )
			);
			$TWIGdata['username_taken'] = username_exists( $user->username );
		}
	}
	$TWIGdata['user'] = $user;
}