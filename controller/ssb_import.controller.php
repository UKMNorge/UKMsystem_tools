<?php
### LEVENDEFØDTE - OOPS, denne fyller opp generisk namespace med funksjoner og stuff
require_once(__DIR__.'/SSB/levendefodte.controller.php');
### AREAL AV KOMMUNER:
require_once(__DIR__.'/SSB/areal.controller.php');
### KOMMUNER I NORGE:
require_once(__DIR__.'/SSB/kommuneliste.controller.php');


### LEVENDEFØDTE:
$TWIGdata = checkUpdate($TWIGdata);

$levendefodte = new stdClass();
$levendefodte->last_updated = get_latest_year_updated();
$levendefodte->missing_years = get_missing_years();

$TWIGdata['levendefodte'] = $levendefodte;