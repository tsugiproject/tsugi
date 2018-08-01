<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * This class contains the server side of Tsugi web sockets
 */
class WebSocket {

    /**
     * Get a singleton global connection or set it up if not already set up.
     */
    public static function enabled() {
        global $CFG;
        $config = isset($CFG->websocket_url) && isset($CFG->websocket_secret);
        if ( ! $config ) return false;
        $pieces = parse_url($CFG->websocket_url);
        $port = U::get($pieces,'port');
        $host = U::get($pieces,'host');
        if ( ! $port ) return false;
        if ( ! $host ) return false;
        return true;
    }

    public static function getPort() {
        global $CFG;
        if ( ! self::enabled() ) return null;
        $pieces = parse_url($CFG->websocket_url);
        return U::get($pieces,'port');
    }

    public static function getHost() {
        global $CFG;
        if ( ! self::enabled() ) return null;
        $pieces = parse_url($CFG->websocket_url);
        return U::get($pieces,'host');
    }

    public static function getToken() {
        return "xyzzy";
    }

    public static function verifyToken($token) {
        return $token == 'xyzzy';
    }


}
