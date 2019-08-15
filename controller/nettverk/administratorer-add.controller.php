<?php

require_once('UKM/fylker.class.php');
UKMsystem_tools::addViewData(
    'fylke',
    fylker::getById($_GET['fylke'])
);

// DEBUG
if (true || $_SERVER['REQUEST_METHOD'] == 'POST') {
    // DEBUG
    #$_POST['email'] = 'testbruker@ukm.dev';
    $_POST['email'] = 'mariusmandal@gmail.com';
    $_POST['email'] = 'testkommune@gmail.com';

    $user = get_user_by('email', $_POST['email']);

    if (isset($_POST['user_id'])) {
        if ($_POST['user_id'] == 'new' && username_exists($_POST['username'])) {
            UKMsystem_tools::getFlash()->add(
                'danger',
                'Brukernavnet er opptatt. Prøv et annet.'
            );
        }
        $user->first_name = $_POST['first_name'];
        $user->last_name = $_POST['last_name'];
    } else {
        UKMsystem_tools::addViewData(
            'doAdd',
            true
        );
        if ($user) {
            $user->first_name = get_user_meta($user->ID, 'first_name', true);
            $user->last_name = get_user_meta($user->ID, 'last_name', true);
            $user->phone = get_user_meta($user->ID, 'phone', true);
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
                strpos($_POST['email'], '@')
            );

            UKMsystem_tools::addViewData(
                'username_taken',
                username_exists($user->username)
            );
        }
    }
    UKMsystem_tools::addViewData(
        'user',
        $user
    );
}
