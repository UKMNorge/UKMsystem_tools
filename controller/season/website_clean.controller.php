<?php

$blogs = get_sites(
    [
        'number' => 5000
    ]
);

UKMsystem_tools::addViewData('blogs', $blogs);