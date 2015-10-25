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

function validate($sanity, $code ) {
    if ( strlen($code) < 1 ) return "Python code is required";
    foreach($sanity as $match => $message ) {
        if ( $match[0] == '/' ) {
            if ( preg_match($match, $code) ) return $message;
        } else if ( $match[0] == '!' ) {
            if ( strpos($code,substr($match,1)) !== false ) return $message;
        } if (strpos($code,$match) === false ) {
            return $message;
        }
    }
    return true;
}

function deHttps($url) {
    return str_replace('https://', 'http://', $url);
}

