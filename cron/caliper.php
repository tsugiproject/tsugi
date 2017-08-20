<?php

use \Tsugi\Util\U;
use \Tsugi\Core\Activity;

require_once "../config.php";

if ( ! U::isCli() ) {
    echo("Must run comand line\n");
    return;
}

//for($i=0; $i<100; $i++) {
    $retval = Activity::sendCaliperEvent(false);
    if ( ! $retval ) return;
//}
