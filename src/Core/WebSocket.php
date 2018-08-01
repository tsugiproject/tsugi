<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;

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

    public static function makeToken($launch) {
        global $CFG;
        if ( ! isset($launch->link->id) ) return false;
        $token = $CFG->wwwroot . '::' . $launch->link->id . '::';
        $token .= isset($launch->context->id) ? $launch->context->id : 'no_context';
        $token .= '::';
        $token .= isset($launch->user->id) ? $launch->context->id : 'no_user';
        return $token;
    }

    public static function getToken($launch) {
        global $CFG;
        $plain = self::makeToken($launch);
        if ( ! $plain ) return $plain;
        $encrypted = AesCtr::encrypt($plain, $CFG->websocket_secret, 256) ;
        return $encrypted;
    }

    public static function decodeToken($token) {
        global $CFG;
        $plain = AesCtr::decrypt($token, $CFG->websocket_secret, 256) ;
        $pieces = explode('::', $plain);
        if ( count($pieces) != 4 ) return false;
        return $plain;
    }

    public static function getSpaceFromToken($token) {
        $pieces = explode('::', $token);
        if ( count($pieces) != 4 ) return false;
        $space = implode('::', array_slice($pieces,0,2));
        return $space;
    }

}
