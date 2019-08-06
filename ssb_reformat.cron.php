<?php
require_once('UKM/sql.class.php');

$FIRST_YEAR = 1985;
$LAST_YEAR  = 2012;

$infos	= new SQL("SELECT * FROM `ukm_befolkning_ssb`");
$res	= $infos->run();

if($res)
	while($r = SQL::fetch($res)) {
		echo '<h1>'. $r['kommune_navn'] .'</h1>';
		for($i=$FIRST_YEAR; $i<$LAST_YEAR; $i++) {
			$test = new SQL("SELECT * FROM `ukm_befolkning`
							 WHERE `k_id` = '#kommune'
							 AND `year` = '#year'",
							 array('kommune'=>$r['kommune_id'], 'year'=>$i));
			$test = $test->run();
			if(SQL::numRows($test) == 0) {
				$insert = new SQLins('ukm_befolkning');
				$insert->add('k_id', $r['kommune_id']);
				$insert->add('year', $i);
				$insert->add('count', $r[$i]);
				$insert->run();
				echo $insert->debug();
			}
		}
	}
