<?php

use \Tsugi\Util\Mersenne_Twister;


function getShuffledNames($code) {
    global $names;
    $MT = new Mersenne_Twister($code);
    $new = $names;
    $new = $MT->shuffle($names);
    return $new;
}

function getRadomNumbers($code, $count=400, $max=10000) {
    $retval = array();
    $MT = new Mersenne_Twister($code);
    for($i=0; $i < $count; $count++ ) {
        $retval[] = $MT->getNext(1,$max);
    }
    return $retval;
}
