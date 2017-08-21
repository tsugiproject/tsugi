<?php

use \Tsugi\Util\U;
use \Tsugi\Core\Activity;

require_once "../config.php";

if ( ! U::isCli() ) {
    echo("Must run comand line\n");
    return;
}

$stuff = Activity::pushCaliperEvents(2, 100, true);
print_r($stuff);

