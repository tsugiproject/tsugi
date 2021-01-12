<?php
use \Tsugi\Core\I18N;

// Convience method, pattern borrowed from WordPress
if (! function_exists('__')) {
    function __($message, $textdomain=false) {
        return I18N::__($message, $textdomain);
    }
}

function _e($message, $textdomain=false) {
    I18N::_e($message, $textdomain);
}

function _m($message, $textdomain=false) {
    return I18N::_m($message, $textdomain);
}

function _me($message, $textdomain=false) {
    I18N::_me($message, $textdomain);
}

// Set up the user's locale - May be overridden later
$TSUGI_LOCALE = null;
$TSUGI_LOCALE_RELOAD = true;
I18N::setLocale();