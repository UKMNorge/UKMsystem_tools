<?php

$blogs = get_sites(
    [
        'number' => 1500
    ]
);

UKMsystem_tools::addViewData('blogs', $blogs);