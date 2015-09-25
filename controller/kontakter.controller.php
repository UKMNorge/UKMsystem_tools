<?php
define('ZIP_WRITE_PATH', '/home/ukmno/public_subdomains/download/zip/');
require_once('UKM/sql.class.php');
require_once('UKM/vcard.class.php');
require_once('UKM/zip.class.php');

$SQL = new SQL("SELECT *, `fylke`.`name` AS `fylke_name`
				FROM `smartukm_contacts` AS `c`
				JOIN `smartukm_rel_pl_ab` AS `rel` ON (`rel`.`ab_id` = `c`.`id`)
				JOIN `smartukm_place` AS `pl` ON (`rel`.`pl_id` = `pl`.`pl_id`)
				JOIN `smartukm_rel_pl_k` AS `rel_pl_k` ON (`pl`.`pl_id` = `rel_pl_k`.`pl_id`)
				JOIN `smartukm_kommune` AS `kommune` ON (`rel_pl_k`.`k_id` = `kommune`.`id`)
				JOIN `smartukm_fylke` AS `fylke` ON (`kommune`.`idfylke` = `fylke`.`id`)
				WHERE `pl`.`season` = '#season'
				GROUP BY `c`.`id`
				", array('season' => get_site_option('season')-1 )
			);

$res = $SQL->run();

$STORAGE = '/tmp/UKMkontakter/';

$zipname = 'UKMkontakter';
$zip = new zip($zipname, true);
$zip->debugMode();
$counter = 0;
if( $res ) {
	while( $row = mysql_fetch_assoc( $res ) ) {
		$counter++;
		$contact = new stdClass();
		$row['name'] = utf8_encode( $row['name'] );
		$contact->first_name = utf8_encode($row['firstname']);
		$contact->last_name = utf8_encode($row['lastname']);
		if( empty( $contact->first_name ) || empty( $contact->last_name ) ) {
			$name = explode(' ', $row['name']);
			$ant_names = sizeof($name);
			if($ant_names == 3) {
				$contact->first_name = array($name[0]);
			} else {
				$contact->first_name = array_splice($name, 0, round($ant_names/2));
			}
			$contact->first_name = implode(' ', $contact->first_name );
			$contact->last_name = str_replace( $contact->first_name, '', $row['name'] );
	
		}
		$contact->phone = $row['tlf'];
		$contact->email = $row['email'];
		$contact->title = utf8_encode($row['title']);
		$contact->facebook = $row['facebook'];

		$contact->monstring = utf8_encode($row['pl_name']);
		$contact->fylke = utf8_encode( $row['fylke_name'] );

		// VCARD
		$cardname = 'UKM_kontakt_'.$counter;

		$card = new stdClass();
		$card->first_name 	= $contact->first_name;
		$card->last_name 	= $contact->last_name;
		$card->phone 		= $contact->phone;
		$card->fax_tel 		= $contact->phone .'#600';
		$card->pager_tel 	= $contact->phone .'#500';
		$card->title 		= $contact->title;
		$card->email1		= $contact->email;
		$card->url			= $contact->facebook;
		$card->company		= 'UKM '. $contact->monstring;
		$card->department	= '('. $contact->fylke .')';
		
		$card = new vcard( (array) $card );
		$card->build();
		$card->store( $STORAGE . $cardname, false );
		// EOVCARD
		$zip->add( $STORAGE . $cardname.'.vcf', $cardname.'.vcf' );

		$TWIGdata['contacts'][] = $contact;
	}
	;
	$TWIGdata['zip'] = $zip->compress(); //'http://download.ukm.no/zip/'.$zipname; 
}
	
?>