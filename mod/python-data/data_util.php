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
    return $url;
    // return str_replace('https://', 'http://', $url);
}

function httpsWarning($url) {
    if ( strpos($url, 'https://') !== 0 ) return;
?>
<p><b>Note:</b> If you get an error when you run your program that complains about 
<b>CERTIFICATE_VERIFY_FAILED</b> when you call <b>urlopen()</b>, make the following
changes to your program:
<pre>
import urllib
import json
<b>import ssl</b>

...

    print 'Retrieving', url
    <b>scontext = ssl.SSLContext(ssl.PROTOCOL_TLSv1)</b>
    uh = urllib.urlopen(url<b>, context=scontext</b>)
    data = uh.read()
</pre>
This will keep your Python code from rejecting the server's certificate.
</p>
<?php
}

