<?php

require_once('UKM/fylker.class.php');

UKMsystem_tools::addViewData('fylker', fylker::getAll());