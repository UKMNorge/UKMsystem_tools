<?php

use UKMNorge\Wordpress\Blog;
use UKMNorge\API\SSB\Klass;
use UKMNorge\Arrangement\Write;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Log\Logger;

global $log_meldinger;
global $log_errors;
global $log_criticals;
$alle_kommuner = [];

die;

$dataset = new Klass();
// 131 er "Standard for kommuneinndeling"
$dataset->setClassificationId("131");
$startDato = new DateTime(date('Y')."-01-01");
$sluttDato = new DateTime(date('Y')."-12-31");

$startDato = new DateTime("2020-01-01");
$sluttDato = new DateTime("2024-01-02");

$dataset->setRange($startDato, $sluttDato);
$dataset->includeFutureChanges(true);
$dataEndringer = $dataset->getChanges();

$endringer = [];

$kommunerOldNewIds = [];

// Legg til endringer i kommuner i array
foreach($dataEndringer as $dataEndring) {
	foreach($dataEndring as $endring) {  
        $old_kommune = new Kommune($endring->oldCode);
        $new_kommune = new Kommune($endring->newCode);
        $kommunerOldNewIds[$old_kommune->getId()] = $new_kommune->getId();
    }
}

foreach($dataEndringer as $dataEndring) {
	foreach($dataEndring as $endring) {
        try {
            $old_kommune = new Kommune($endring->oldCode);
            $new_kommune = new Kommune($endring->newCode);
        } catch(Exception $e) {
            $log_errors[$old_kommune->getId()][] = "ERROR code 0: " . $e->getMessage();
        }

        // Do not continue if the oldCode == newCode
        if($endring->oldCode == $endring->newCode) {
            $log_meldinger[$old_kommune->getId()][] = 'Kommune med id ' . $endring->oldCode . ' har IKKE fått ny kode ' . $endring->newCode;
            continue;
        }

        $alle_kommuner[$old_kommune->getId()] = $old_kommune;
        
        if($new_kommune->getNavn() == 'ukjent') {
            $log_criticals[$old_kommune->getId()][] = $endring->newName . '(' . $endring->newCode . ') finnes ikke i systemet. Har du hentet endringer på forhånd? Sjekk denne lenken: http://ukm.dev/wp-admin/network/admin.php?page=UKMsystem_tools&action=kommuner%2FimportChanges';
                continue;
            }
            
        if($old_kommune->getNavn() == 'ukjent') {
            $log_criticals[$old_kommune->getId()][] = 'Kommune med navn: ' . $endring->newName . ' ' . $endring->newCode . ' finnes ikke i systemet';
            continue;
        }

        if($endring->oldCode != $endring->newCode) {
            $log_meldinger[$old_kommune->getId()][] = 'Kommune med id ' . $endring->oldCode . ' har fått ny id ' . $endring->newCode;
        }

        // 2. For each blog (kommune), change kommune and fylke id
        try{
            $blogID = Blog::getIdByPath($old_kommune->getPath());

            // Oppdater kommune blog
            Blog::oppdaterFraKommune($blogID, $new_kommune);
            $log_meldinger[$old_kommune->getId()][] = "Kommune blog med id " . $blogID . " er oppdatert";
            
        } catch(Exception $e) {
            $log_errors[$old_kommune->getId()][] = "ERROR code 2: " . $e->getMessage();
        }

        // 3. For each blog (arrangement), change kommune and fylke id
        try{
            $log_meldinger[$old_kommune->getId()][] = "Antall arrangementer: " . $old_kommune->getOmrade()->getArrangementer()->getAntall();
            foreach( $old_kommune->getOmrade()->getArrangementer()->getAll() as $arrangement) {
                
                Logger::initWP($arrangement->getId());
                // 5. For each arrangement, change to right kommune and fylke

                // Convert old kommune ids with new ones
                $k_ids_updated = [];
                foreach ($arrangement->kommuner_id as $k_id) {
                    if(array_key_exists($k_id, $kommunerOldNewIds)) {
                        $k_ids_updated[] = $kommunerOldNewIds[$k_id];
                    } else {
                        $k_ids_updated[] = $k_id;
                    }
                }

                $log_meldinger[$old_kommune->getId()][] = "Kommuner i arrangementet " . $arrangement->getId() . ". Før oppdatering: " . implode(" ", $arrangement->kommuner_id) . ", etter oppdatering: " . implode(" ", $k_ids_updated);

                // Add kommuner to arrangement
                $arrangement->setKommuner($k_ids_updated);
                
                // Set fylke og kommune på Arrangement
                $arrangement->setEierKommune($new_kommune);
                $arrangement->setEierFylke($new_kommune->getFylke());
                $arrangement->setFylke($new_kommune->getFylke()->getId());
        
                // Sjekk hvis arrangement har path
                if(strlen($arrangement->getPath()) > 0) {
                    $blogID = Blog::getIdByPath($arrangement->getPath());
        
                    // Lagre arrangement endringer i DB
                    $resSave = Write::save($arrangement);
                    if(!$resSave) {
                        $log_errors[$old_kommune->getId()][] = "Arrangementet ble ikke lagret";
                    }
        
                    // Oppdater arrangement blog
                    Blog::oppdaterFraArrangement($blogID, $arrangement);
                    $log_meldinger[$old_kommune->getId()][] = "Oppdatert blog " . $blogID . " for arrangement  " . $arrangement->getId();
        
                } else {
                    $log_errors[$old_kommune->getId()][] = "Arrangement: " . $arrangement->getId() . ' har ikke path';
                }
            }
        } catch(Exception $e) {
            $log_errors[$old_kommune->getId()][] = "ERROR code 3: " . $e->getMessage();
        }
        
        // 6. Update administrators on DB at table ukm_nettverk_admins (kommune og fylke)
        try {
            // Kommune
            $res = updateAdminKommune($old_kommune, $new_kommune);

            if($res > 0) {
                $log_meldinger[$old_kommune->getId()][] = "KOMMUNE: Admins er oppdatert, code: " . $res;
            }
            else {
                $log_errors[$old_kommune->getId()][] = 'KOMMUNE: 0 endringer at updateAdminKommune(), code: ' . $res;
            }
        } catch(Exception $e) {
            $log_errors[$old_kommune->getId()][] = "ERROR code 4: " . $e->getMessage();
        }
        
        // 7. Update relation kommune arrrangement (fellesmønstring) on DB at table smartukm_rel_pl_k
        try {
            $res = updateArrangementKommuneRelasjon($old_kommune, $new_kommune);

            if($res > 0) {
                $log_meldinger[$old_kommune->getId()][] = "Arrangement-kommune relasjon/er er oppdatert, code: " . $res;
            }
            else {
                $log_errors[$old_kommune->getId()][] = '0 endringer at updateArrangementKommuneRelasjon(), code: ' . $res;
            }
        } catch(Exception $e) {
            $log_errors[$old_kommune->getId()][] = "ERROR code 5: " . $e->getMessage();
        }


        // 9. Deaktiver gammel kommune
        $res = deaktiverKommune($old_kommune);
        if($res > 0) {
            $log_meldinger[$old_kommune->getId()][] = "Gammel kommune ble deaktivert, code: " . $res;
        }
        else {
            $log_errors[$old_kommune->getId()][] = '0 endringer at deaktiverKommune(), code: ' . $res;
        }


        // Add kommune in tables
        $tables = [
            'smartukm_band' => 'b_kommune',
            'smartukm_contacts' => 'kommune',
            'smartukm_participant' => 'p_kommune',
            'smartukm_postalplace' => 'k_id',
            'ukm_befolkning' => 'k_id',
            'ukmno_wp_related' => 'b_kommune',
            'venteliste' => 'k_id',
        ];

        try {
            foreach($tables as $table => $key) {
                $res = generalKommuneUpdate($table, $key, $old_kommune, $new_kommune);
    
                if($res > 0) {
                    $log_meldinger[$old_kommune->getId()][] = $table . ' with key ' . $key . " er oppdatert " . $res;
                }
                else {
                    $log_errors[$old_kommune->getId()][] = '0 endringer at table ' . $table . ' code: ' . $res;
                }
            }
        } catch(Exception $e) {
            $log_criticals[$old_kommune->getId()][] = 'CRITICAL ERROR at generalKommuneUpdate(): ' . $e->getMessage();
        }

    }

    // 12. Check if the blog is available for each new kommune (because of the name change) and check if kommune is active
    foreach($dataEndringer as $dataEndring) {
        foreach($dataEndring as $endring) {
            try {
                $old_kommune = new Kommune($endring->oldCode);
                $new_kommune = new Kommune($endring->newCode);
                $blogID = Blog::getIdByPath($new_kommune->getPath());
                
                if(!$new_kommune->erAktiv()) {
                    $log_criticals[$old_kommune->getId()][] = 'Kommune: ' . $new_kommune->getNavn() . ' er ikke aktiv!';
                }

                if($endring->oldCode == $endring->newCode) {
                    $log_meldinger[$old_kommune->getId()][] = "Blog id for old kommune: " . $blogID;
                }
                else {
                    $log_meldinger[$old_kommune->getId()][] = "Blog id for new kommune: " . $blogID;
                }
            } catch(Exception $e) {
                $log_criticals[$old_kommune->getId()][] =  $e->getMessage();
            }
        }
    }
}



// Used to update table containing kommune id
function generalKommuneUpdate(String $table, String $key, Kommune $old_kommune, Kommune $new_kommune) {
    $query = new Update(
        $table,
        [
            $key => $old_kommune->getId(),
        ]
    );
    
    $query->add($key, $new_kommune->getId());
    $res = $query->run();
    
    return $res;
}



function updateAdminKommune(Kommune $old_kommune, Kommune $new_kommune) {
    // Return true fordi det er samme kommune, trenger ikke å endre noe
    if($old_kommune->getId() == $new_kommune->getId()) {
        $log_meldinger[$old_kommune->getId()][] = "Samme kommune, trenger ikke oppdatering av admins";
        return true;
    }
    
    $query = new Update(
        'ukm_nettverk_admins',
        [
            'geo_type' => 'kommune',
            'geo_id' => $old_kommune->getId()
            ]
        );

    $query->add('geo_id', $new_kommune->getId());
    
    $res = $query->run();
    
    return $res;
}

    
function updateArrangementKommuneRelasjon(Kommune $old_kommune, Kommune $new_kommune) {
    
    // Return true fordi det er samme kommune, trenger ikke å endre noe
    if($old_kommune->getId() == $new_kommune->getId()) {
        $log_meldinger[$old_kommune->getId()][] = "Samme kommune, trenger ikke oppdatering av arrangement-kommune relasjon";
        return true;
    }

    $query = new Update(
        'smartukm_rel_pl_k',
        [
            'k_id' => $old_kommune->getId()
        ]
    );
    $query->add('k_id', $new_kommune->getId());
    
    $res = $query->run();

    return $res;
}


function deaktiverKommune(Kommune $kommune) {
    $query = new Update(
        'smartukm_kommune',
        [
            'id' => $kommune->getId()
        ]
    );
    $query->add('active', false);
    
    $res = $query->run();

    return $res;
}

UKMsystem_tools::addViewData('log_meldinger', $log_meldinger);
UKMsystem_tools::addViewData('log_errors', $log_errors);
UKMsystem_tools::addViewData('log_criticals', $log_criticals);
UKMsystem_tools::addViewData('alle_kommuner', $alle_kommuner);

?>