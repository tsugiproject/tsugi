<?php

namespace Tsugi\Core;

use  Tsugi\Util\U;

/**
 * Helper class for Tsugi REST end points
 */
class Rest {
    /**
     * Preflight a REST request - handle OPTIONS
     */
     public static function preFlight() {
         $http_origin = U::get($_SERVER, 'HTTP_ORIGIN');
         $http_method = U::get($_SERVER, 'REQUEST_METHOD');

         header('Content-Type: application/json; charset=UTF-8');
         header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
         header('Pragma: no-cache'); // HTTP 1.0.
         header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past - proxies

        if ( strpos($http_origin, "http://localhost:") === 0 )  {
            header("Access-Control-Allow-Origin: $http_origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: x-tsugi-authorization');
            header('Access-Control-Max-Age: 86400');
            if ( $http_method == "OPTIONS" ) return true;
        }

        $headers = apache_request_headers();

        $session = U::get($headers, 'X-Tsugi-Authorization');
        error_log("Rest session ".$session);

        if ( is_string($session) ) $_GET[session_name()] = $session;
        return false;

     }

}
