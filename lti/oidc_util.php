<?php

function getBrowserSignature() {
    $concat = getBrowserSignatureRaw();

    $h = hash('sha256', $concat);
    return $h;
}

function getBrowserSignatureRaw() {
    global $CFG;

    $look_at = array( 'x-forwarded-proto', 'x-forwarded-port', 'host',
    'accept-encoding', 'cf-ipcountry', 'user-agent', 'accept', 'accept-language');

    $headers = \Tsugi\Util\U::apache_request_headers();

    $concat = \Tsugi\Util\Net::getIP();
    if ( isset($CFG->cookiepad) ) $concat .= ':::' . $CFG->cookiepad;
    if ( isset($CFG->cookiesecret) ) $concat .= ':::' . $CFG->cookiesecret;
    $used = array();
    ksort($headers);
    foreach($headers as $k => $v ) {
        if ( ! in_array(strtolower($k), $look_at) ) continue;
        if ( is_string($v) ) { 
            $used[$k] = $v;
            $concat .= ':::' . $k . '=' . $v;
            continue; 
        }
    }

    foreach($_COOKIE as $k => $v ) {
        if ( $k == getTsugiStateCookieName() ) continue;
        $concat .= '===' . $k . '=' . $v;
    }

    return $concat;
}

function getTsugiStateCookieName() {
    return "tsugi-state-lti-advantage";
}
