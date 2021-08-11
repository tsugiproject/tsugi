<?php

require_once "../config.php";

use \Tsugi\Util\Net;
use \Tsugi\Core\Rest;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\Output;

if ( Rest::preFlight() ) return;

$LAUNCH = LTIX::requireData();

// Takes raw data from the request
$json = file_get_contents('php://input');
$data = json_decode($json);

Settings::linkUpdate((array) $data);

// Avoid empty body which creates havoc with JQuery
echo("{}");
