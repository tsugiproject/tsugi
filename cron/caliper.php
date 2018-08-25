<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\Activity;

require_once "../config.php";

if ( ! U::isCli() ) {
    echo("Must run comand line\n");
    return;
}

$seconds = 2;
$maxevents = 1;
$debug = false;  // true implies no delete

$stuff = Activity::pushCaliperEvents($seconds, $maxevents, $debug);
if ( $debug ) echo ("\nCaliper results:\n");
print_r($stuff);

