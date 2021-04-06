<?php

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

$FIRST_YEAR = 1985;
$LAST_YEAR  = (int) date('Y');

$infos	= new Query("SELECT * FROM `ukm_befolkning_ssb`");
$res	= $infos->run();

if($res)
	while($r = Query::fetch($res)) {
		echo '<h1>'. $r['kommune_navn'] .'</h1>';
		for($i=$FIRST_YEAR; $i<$LAST_YEAR; $i++) {
			$test = new Query("SELECT * FROM `ukm_befolkning`
							 WHERE `k_id` = '#kommune'
							 AND `year` = '#year'",
							 array('kommune'=>$r['kommune_id'], 'year'=>$i));
			$test = $test->run();
			if(Query::numRows($test) == 0) {
				$insert = new Insert('ukm_befolkning');
				$insert->add('k_id', $r['kommune_id']);
				$insert->add('year', $i);
				$insert->add('count', $r[$i]);
				$insert->run();
				echo $insert->debug();
			}
		}
	}
