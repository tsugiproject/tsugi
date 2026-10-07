<?php

// Session mode is fixed before config.php loads. A cookie session is only
// for notifications and analytics_cookie. Every other /api/ URL, including
// a 404, stays cookieless so an LTI session id in the query string is used.
// Do not send /api through tsugi.php: that file defines COOKIE_SESSION
// unless _LTI_TSUGI is already on the query string.

$apiUri = $_SERVER['REQUEST_URI'] ?? '';
$apiQ = strpos($apiUri, '?');
$apiPath = $apiQ === false ? $apiUri : substr($apiUri, 0, $apiQ);
$apiPrefix = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$apiRest = $apiPath;
if ( $apiPrefix !== '' && $apiPrefix !== '/' && $apiPrefix !== '.' && strpos($apiPath, $apiPrefix) === 0 ) {
    $apiRest = substr($apiPath, strlen($apiPrefix));
}
$apiRest = ltrim($apiRest, '/');
$apiSlash = strpos($apiRest, '/');
$apiHead = $apiSlash === false ? $apiRest : substr($apiRest, 0, $apiSlash);
if ( str_ends_with($apiHead, '.php') ) {
    $apiHead = substr($apiHead, 0, -4);
}

if ( ($apiHead === 'notifications' || $apiHead === 'analytics_cookie') && ! defined('COOKIE_SESSION') ) {
    define('COOKIE_SESSION', true);
}

require_once __DIR__ . '/../config.php';

\Tsugi\Controllers\Api\Router::dispatch($apiHead);
