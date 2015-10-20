<?php

use \Tsugi\Util\Mersenne_Twister;


function getShuffledNames($code) {
    global $NAMES;
    if ( ! is_array($NAMES) ) {
        die("Name data not loaded");
    }
    $MT = new Mersenne_Twister($code);
    $new = $MT->shuffle($NAMES);
    return $new;
}

function getRandomNumbers($code, $count=400, $max=10000) {
    $retval = array();
    $MT = new Mersenne_Twister($code);
    for($i=0; $i < $count; $i++ ) {
        $retval[] = $MT->getNext(1,$max);
    }
    return $retval;
}
