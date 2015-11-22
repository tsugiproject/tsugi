<?php

use \Tsugi\Util\Mersenne_Twister;

// Global Configuration Options

// $GLOBAL_PYTHON_DATA_URL = false; // To serve locally
$GLOBAL_PYTHON_DATA_URL = 'http://python-data.dr-chuck.net/';

// $GLOBAL_PYTHON_DATA_REMOVE_HTTPS = true;  // To map data urls to http:
$GLOBAL_PYTHON_DATA_REMOVE_HTTPS = false;

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
    global $GLOBAL_PYTHON_DATA_REMOVE_HTTPS;
    if ( ! $GLOBAL_PYTHON_DATA_REMOVE_HTTPS ) return $url;
    return str_replace('https://', 'http://', $url);
}

function dataUrl($file) {
    global $GLOBAL_PYTHON_DATA_URL;
    if ( is_string($GLOBAL_PYTHON_DATA_URL) ) {
        return $GLOBAL_PYTHON_DATA_URL.$file;
    }
    $url = curPageUrl();
    $retval = str_replace('index.php','data/'.$file,$url);
    return $retval;
}

