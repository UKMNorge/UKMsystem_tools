<?php
if( isset($_GET['approved'] ) && 'true' == $_GET['approved'] ) {
	update_site_option('delta_is_tested', get_site_option('season') );
}

$TWIGdata['delta_is_tested'] = get_site_option('delta_is_tested');
$TWIGdata['status'] = $TWIGdata['delta_is_tested'] == get_site_option('season') ? 'success' : 'danger';
?>