<?php
define('ZIP_WRITE_PATH', '/home/ukmno/public_subdomains/download/zip/');
require_once('UKM/sql.class.php');
require_once('UKM/vcard.class.php');
require_once('UKM/zip.class.php');
global $objPHPExcel;
require_once('UKM/inc/excel.inc.php');

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

// Init excel document
exInit();
$excel_row = 1;
// Header row
excell( i2a(1).'1', 'First name', 'bold' );
excell( i2a(2).'1', 'Last name', 'bold' );
excell( i2a(3).'1', 'E-mail', 'bold' );
excell( i2a(4).'1', 'Title', 'bold' );
excell( i2a(5).'1', 'Mønstring', 'bold' );
excell( i2a(6).'1', 'Fylke', 'bold' );
excell( i2a(7).'1', 'Phone', 'bold' );
excell( i2a(8).'1', 'Phone UKM', 'bold' );
excell( i2a(9).'1', 'Phone UKM support', 'bold' );

$emails = [];
if( $res ) {
	while( $row = SQL::fetch( $res ) ) {
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
		$card->home_tel		= $contact->phone;
		$card->fax_tel 		= $contact->phone .'600';
		$card->pager_tel 	= $contact->phone .'500';
		$card->title 		= $contact->title;
		$card->email1		= $contact->email;
		$card->url			= $contact->facebook;
		$card->company		= 'UKM '. $contact->monstring;
		$card->department	= '('. $contact->fylke .')';
		
		$vcard = new vcard( (array) $card );
		$vcard->build();
		$vcard->store( $STORAGE . $cardname, false );
		// EOVCARD
		$zip->add( $STORAGE . $cardname.'.vcf', $cardname.'.vcf' );
		
		$excel_row++;
		excell( i2a(1).$excel_row, $card->first_name );
		excell( i2a(2).$excel_row, $card->last_name );
		excell( i2a(3).$excel_row, $card->email1 );
		excell( i2a(4).$excel_row, $card->title );
		excell( i2a(5).$excel_row, $card->company );
		excell( i2a(6).$excel_row, $card->department );
		excell( i2a(7).$excel_row, $card->home_tel );
		excell( i2a(8).$excel_row, $card->fax_tel );
		excell( i2a(9).$excel_row, $card->pager_tel );

		$TWIGdata['contacts'][] = $contact;
		$emails[] = $card->email1;
	}
	;
	$TWIGdata['zip'] = $zip->compress(); //'https://download.ukm.no/zip/'.$zipname; 
	$TWIGdata['excel'] = exWrite($objPHPExcel, 'kontakteksport');
}


// FRA PASSORDLISTEN
exInit();
excell( 'A1', 'Brukernavn', 'bold');
excell( 'B1', 'E-post', 'bold');
$row = 1;

$db = mysql_connect(UKM_WP_DB_HOST, UKM_WP_DB_USER, UKM_WP_DB_PASSWORD) or die(mysql_error());
mysql_select_db(UKM_WP_DB_NAME, $db);

$qry = 'SELECT `b_name`, `b_email`
		FROM `ukm_brukere` 
		ORDER BY `b_name` ASC';
$res = mysql_query( $qry, $db );
if( $res ) {
	while( $r = SQL::fetch( $res ) ) {
		if( !in_array( $r['b_email'], $emails ) 
		 && ( strpos( $r['b_email'], 'fake.ukm') === false ) 
		 && ( strpos( $r['b_email'], 'falsk.ukm') === false )
		) {	
			$row++;
			exCell('A'. $row, $r['b_name']);
			exCell('B'. $row, $r['b_email']);
		}
	}
}
$TWIGdata['excel_passord'] = exWrite( $objPHPExcel, 'passordliste');
?>