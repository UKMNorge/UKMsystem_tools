<?php
	require_once('UKM/monstringer.class.php');
	require_once('UKM/monstring.class.php');
	require_once('UKM/innslag.class.php');
	
	$SEASON = 2014;
	$monstringer = new monstringer( $SEASON );
	$monstringer = $monstringer->etter_sesong();
	
	while( $r = mysql_fetch_assoc( $monstringer ) ) {
		$monstring = new monstring( $r['pl_id'] );
		
		#echo '<hr />'. $monstring->get('pl_name') .'<br />';
		
		$innslagene = $monstring->innslag();
		
		foreach( $innslagene as $inn ) {
			$innslag = new innslag( $inn['b_id'] );
			$bt_id = $innslag->get('bt_id');
			if( $bt_id != 1 ) {
				continue;
			}

			#echo ' &nbsp; '. $innslag->get('b_name') .'<br />';
			
			$titler = $innslag->titler( $monstring->get('pl_id') );
			foreach( $titler as $tittel ) {
				$tonoInfo = new tonoInfo( $tittel->get('t_id'), $tittel->get('form') );
				$tonoInfo->load_from_tittel( $tittel );
				$tonoInfo->load_UKMTV( $innslag );
				$TWIGdata['titler'][] = $tonoInfo;
				
				#echo ' &nbsp; &nbsp; '. $tittel->get('tittel') .' - '. $tittel->get('tekst_av') .' - '. $tittel->get('melodi_av') . ' - '. $tittel->get('varighet') .'<br />';
			}
		}
	}
	
	
class tonoInfo {
	private $t_id;
	private $t_type;
	
	public function __construct( $t_id, $t_type ) {
		$this->t_id = $t_id;
		$this->t_type = $t_type;	
	}
	
	public function load_from_tittel( $tittel ) {
		$this->tittel = $tittel->get('tittel');
		$this->tekst_av = $tittel->get('tekst_av');
		$this->melodi_av = $tittel->get('melodi_av');
		$this->selvlaget = 'ukjent';#$tittel->get('selvlaget');
		$this->varighet = $tittel->get('varighet');
		
		$this->_init_UKMTV();
	}
	
	public function load_UKMTV( $innslag ) {
		$related = $innslag->related_items();
		$tv_files = $related['video'];
		if( is_array( $tv_files ) ) {
			foreach( $tv_files as $tv_file ) {
				$tv = new tv( $tv_file['tv_id'] );
				if( $tv->id == false ) {
					continue;
				}
				$this->UKMTV->exists = true;
				$this->UKMTV->click = $tv->getPlayCount();
			}
		}
	}
	
	private function _init_UKMTV() {
		$this->UKMTV = new stdClass();
		$this->UKMTV->exists = false;
		$this->UKMTV->click = 0;
		$this->UKMTV->seconds = 0;
	}
	
	public function getTittel() {
		return $this->tittel;			
	}
	public function getTekst() {
		return $this->tekst_av;
	}
	public function getMelodi() {
		return $this->melodi_av;
	}
	public function getSelvlaget() {
		return $this->selvlaget;
	}
	public function getVarighet() {
		return $this->varighet;
	}
	public function getUKMTV() {
		return $this->UKMTV->exists;
	}
	public function getUKMTVClick() {
		return $this->UKMTV->click;
	}
	public function getUKMTVSeconds() {
		return $this->UKMTV->seconds;
	}
}
?>