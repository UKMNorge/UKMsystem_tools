<?php

$new_season = (int) get_site_option('season') + 1;
if( $new_season == 1 ) { 
    $new_season = (int) date('Y');
}
if( isset($_GET['do']) ) {
    update_site_option('season', $new_season);
    update_blog_option(1, 'season', $new_season );
}

UKMsystem_tools::addViewData('season', $new_season);