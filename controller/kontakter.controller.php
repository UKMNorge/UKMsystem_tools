<?php
require_once('UKM/sql.class.php');
require_once('UKM/vcard.class.php');

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

////
	global $objPHPExcel;
	require_once('UKM/inc/excel.inc.php');
	$objPHPExcel = new PHPExcel();
	
	// Endret parameter 22.11.12, navn fungerer nå som direction mens navn hentes fra klassen
	if($navn == 'portrait' || $navn == 'landscape') {
		$orientation = $navn;
		$navn = $this->name;
	} else {
		$orientation = 'portrait';
	}
	
	exorientation($orientation);
	
	$objPHPExcel->getProperties()->setCreator('UKM Norges arrangørsystem');
	$objPHPExcel->getProperties()->setLastModifiedBy('UKM Norges arrangørsystem');
	$objPHPExcel->getProperties()->setTitle('UKM-rapport '.ucfirst(str_replace('_',' ',$this->name)));
	$objPHPExcel->getProperties()->setKeywords('UKM-rapporter');
	## Sett standard-stil
	$objPHPExcel->getDefaultStyle()->getFont()->setName('Calibri');
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(12);
	## OPPRETTER ARK
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->setActiveSheetIndex(0)->getTabColor()->setRGB('A0CF67');
///// 

$row = 1;
	exCell('A'.$row, 'First name', 'bold');
	exCell('B'.$row, 'Last name', 'bold');
	exCell('C'.$row, 'E-mail', 'bold');
	exCell('D'.$row, 'Title', 'bold');
	exCell('E'.$row, 'Company', 'bold');
	exCell('F'.$row, 'Address', 'bold');
	exCell('G'.$row, 'Telefon', 'bold');
	exCell('H'.$row, 'Fax, private', 'bold');
	exCell('I'.$row, 'Fax, work', 'bold');

if( $res ) {
	while( $row = mysql_fetch_assoc( $res ) ) {
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

		//// EXCEL
		$row++;
		exCell('A'.$row, $contact->first_name);
		exCell('B'.$row, $contact->last_name);
		exCell('C'.$row, $contact->email);
		exCell('D'.$row, $contact->title);
		exCell('E'.$row, $contact->monstring);
		exCell('F'.$row, $contact->fylke);
		exCell('G'.$row, $contact->phone);
		exCell('H'.$row, $contact->phone.'#600');
		exCell('I'.$row, $contact->phone.'#500');
		//// EOEXCEL


		$TWIGdata['contacts'][] = $contact;
	}
	
	return exWrite($objPHPExcel,'UKMkontakter_'.date('dmYhis'));
}
	
?>