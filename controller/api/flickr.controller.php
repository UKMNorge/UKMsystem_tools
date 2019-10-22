<?php

use MariusMandal\Flickr\App;
use MariusMandal\Flickr\Exception;
use MariusMandal\Flickr\Auth;
use MariusMandal\Flickr\Flickr;
use MariusMandal\Flickr\Request\Test\Login;

require_once('Flickr/autoloader.php');
// SET LOG LEVEL (throw, default:log)
Exception::setLogMethod('throw');

$required_constants = ['FLICKR_API_KEY', 'FLICKR_API_SECRET', 'FLICKR_ENDPOINT'];
try {
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            UKMsystem_tools::addViewData('FlickrConstants', $required_constants);
            throw Exception::handle(
                'Mangler en eller flere konstanter i UKMconfig (' .
                    implode(', ', $required_constants)
                    . ')'
            );
        }
    }

    // SET APP DETAILS
    App::setId(FLICKR_API_KEY);
    App::setSecret(FLICKR_API_SECRET);
    App::setPermissions('write');

    if (defined('FLICKR_AUTH_USER') && defined('FLICKR_AUTH_TOKEN') && defined('FLICKR_AUTH_SECRET')) {
        // As long as input parameters is valid or null, it is always best to test
        Auth::authenticate(FLICKR_AUTH_USER, FLICKR_AUTH_TOKEN, FLICKR_AUTH_SECRET);
        $flickr = new Flickr();
        // Does flickr have the authentication variables?
        if ($flickr->hasAuthentication()) {
            UKMsystem_tools::addViewData('FlickrAuth', true);
            $test = new Login();
            $res = $test->execute();
            UKMsystem_tools::addViewData('FlickrResult', $res->getData());
        } else {
            UKMsystem_tools::addViewData('FlickrAuth', false);
        }
    } else {
        echo 'YUP';
        UKMsystem_tools::addViewData('FlickrAuthURL', FLICKR_ENDPOINT);
        Exception::handle('Brukeren er ikke logget inn');
    }
} catch (\Exception $e) {
    UKMsystem_tools::addViewData('FlickrAuth', false);
}
