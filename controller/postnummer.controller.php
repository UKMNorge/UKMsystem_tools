<?php
	
if( strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ) {
	$TWIGdata['show'] = 'import';	


	define('PHPEXCEL_ROOT','');	
	/** PHPExcel_IOFactory */
	require_once('PHPExcel/PHPExcel.php');
	require_once('PHPExcel/IOFactory.php');

	$inputFileName = $_FILES['postnummer']['tmp_name'];
	$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
	$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

	$header_row = $sheetData[1];
	
	$index_postnummer = array_search( 'Postnummer', $header_row );
	$index_poststed = array_search( 'Poststed', $header_row );
	
	// Loop all rows
	for( $row=2; $row<sizeof( $sheetData ); $row++ ) {
		$postnummer = $sheetData[ $row ][ $index_postnummer ];
		$poststed = mb_convert_case( $sheetData[ $row ][ $index_poststed ], MB_CASE_TITLE );
		
		$insert = new SQLins('smartukm_postalplace');
		$insert->add('postalcode', $postnummer);
		$insert->add('postalplace', $poststed);
		
		$res = $insert->run();
		if( $res == 1 ) {
			$TWIGdata['added'][] = $postnummer .' '. $poststed;
		}
	}
	
	
} else {
	$TWIGdata['show'] = 'form';
	
	if( sizeof( $_FILES ) == 0 ) {
		$TWIGdata['error'] = true;
	}
}