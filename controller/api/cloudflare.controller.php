<?php
require_once('UKM/cloudflare.class.php');

try {
    $required_constants = ['UKM_CLOUDFLARE_URL', 'UKM_CLOUDFLARE_UKMNO_ZONE', 'UKM_CLOUDFLARE_AUTH_KEY', 'UKM_CLOUDFLARE_EMAIL'];
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            UKMsystem_tools::addViewData('CFconstants', $required_constants);
            throw new Exception(
                'Mangler en eller flere konstanter i UKMconfig (' .
                    implode(', ', $required_constants)
                    . ')',
                301
            );
        }
    }

    UKMsystem_tools::addViewData('CFauthenticated', true);



    // HANDLE STUFF

    if (isset($_GET['cloudflare'])) {
        switch ($_GET['cloudflare']) {
            case 'purge':
                $cf = new cloudflare();
                $res = $cf->purgeAll();
                if ($res == true) {
                    UKMsystem_tools::getFlash()->add(
                        'success',
                        'Cloudflare-cache tømt. Det kan ta 30 sekunder før endringen er effektiv'
                    );
                } else {
                    UKMsystem_tools::getFlash()->add(
                        'danger',
                        'Klarte ikke cleare cachen! <br>Cloudflare sier: ' . $cf->result->errors[0]->message
                    );
                }
                break;
            case 'delete':
                if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                    $cf = new cloudflare();
                    $res = $cf->purge($_POST['CFfiles']);
                    if ($res == true) {
                        UKMsystem_tools::getFlash()->add(
                            'success',
                            'Filene ble fjernet fra cachen!'
                        );
                    } else {
                        UKMsystem_tools::getFlash()->add(
                            'danger',
                            'Klarte ikke slette filene! <br>Cloudflare sier: ' . $cf->result->errors[0]->message
                        );
                    }
                }
                break;
        }
    }
} catch (Exception $e) {
    UKMsystem_tools::addViewData('CFauthenticated', false);
}
