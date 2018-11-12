<?php
	
if( strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ) {
	$TWIGdata['show'] = 'import';	


	define('PHPEXCEL_ROOT','');	
	/** PHPExcel_IOFactory */
	require_once('UKM/inc/excel.inc.php');

	$inputFileName = $_FILES['postnummer']['tmp_name'];
	$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
	$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

	$header_row = $sheetData[1];
	
	$index_postnummer = array_search( 'Postnummer', $header_row );
	$index_poststed = array_search( 'Poststed', $header_row );
	$index_kommune = array_search( 'Kommunenummer', $header_row );
	
	// Loop all rows
	for( $row=2; $row<sizeof( $sheetData ); $row++ ) {
		$postnummer = $sheetData[ $row ][ $index_postnummer ];
		$poststed = mb_convert_case( $sheetData[ $row ][ $index_poststed ], MB_CASE_TITLE );
		$kommune = $sheetData[ $row ][ $index_kommune ];
		
		$insert = new SQLins('smartukm_postalplace');
		$insert->add('postalcode', $postnummer);
		$insert->add('postalplace', $poststed);
		$insert->add('k_id', $kommune);
		
		$res = $insert->run();
		if( $res ) {
			$TWIGdata['added'][] = $postnummer .' '. $poststed;
		} else {
			$update = new SQLins('smartukm_postalplace', array('postalcode' => $postnummer ) );
			$update->add('k_id', $kommune);
			$update->run();
		}
	}

	update_site_option('ukm_systemtools_last_postnumber_update', time());
	
} else {
	$TWIGdata['show'] = 'form';
	
	if( sizeof( $_FILES ) == 0 ) {
		$TWIGdata['error'] = true;
	}
}