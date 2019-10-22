<?php

try {
    $required_constants = ['DROPBOX_APP_ID', 'DROPBOX_APP_SECRET', 'DROPBOX_ENDPOINT'];
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            UKMsystem_tools::addViewData('DBconstants', $required_constants);
            throw new Exception(
                'Mangler en eller flere konstanter i UKMconfig (' .
                implode(', ', $required_constants)
                . ')',
                301
            );
        }
    }
    require_once('UKM/inc/dropbox.inc.php');

    $account = $DROPBOX->getCurrentAccount();
    UKMsystem_tools::addViewData('DBclient', $account);
    UKMsystem_tools::addViewData('DBauthenticated', true);
} catch (Exception $e) {
    UKMsystem_tools::addViewData('DBauthenticated', false);
    if( $e->getCode() == 301 ) {
        UKMsystem_tools::addViewData('DBmissingParameters',true);
    } else {
        UKMsystem_tools::addViewData('DBauthURL', DROPBOX_ENDPOINT);// $DROPBOX->getAuthHelper()->getAuthUrl(DROPBOX_ENDPOINT));
    }
}