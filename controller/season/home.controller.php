<?php

UKMsystem_tools::addViewData(
    [
        'season_active' => (Int) get_site_option('season'),
        'season_new' => (Int) get_site_option('season') + 1
    ]
);