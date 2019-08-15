<?php


use GuzzleHttp\Exception\ClientException;

@session_start();

/** DROPBOX */
require_once('api/dropbox.controller.php');

/** FLICKR */
require_once('api/flickr.controller.php');

/** CLOUDFLARE */
require_once('api/cloudflare.controller.php');
