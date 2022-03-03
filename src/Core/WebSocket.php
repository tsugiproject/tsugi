<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesOpenSSL;

/**
 * This class contains the server side of Tsugi notification web sockets
 *
 * The primary purpose of this class is to provide secure, isolated socket
 * notification spaces for each link_id in Tsugi.  With those spaces, the
 * tool can also have a set of named rooms.
 *
 * WebSockets and frameworks like SocketIO that build atop WebSocket connections
 * allow for a very rich way of developing multi-browser low-latency interactions.
 *
 * Tsugi cannot make that rich fabric available in a reliable way, so we provide
 * a simple, generic service that works across all tools reliably that does not
 * compromise the integrity or memory footprint of a single shared socket server.
 * So Tsugi only provides a single broadcast notification service.  All the browsers
 * in a particular link in a particular course have a secure and isolated space.

 * The application can make "rooms" within that space using whatever room naming convention
 * it likes.  The rooms are isolated by room name through security-by-obscurity.  To
 * get a notification socket, the pplication makes the following call in JavaScript
 * when it is loaded:
 *
 *     var global_web_socket = tsugiNotifySocket("place_42");
 *
 * This will return false if web sockets are not enabled on the Tsugi server.  But
 * you won't know that the web socket can be used until the `onopen()` method
 * is called or the `readyState` attribute is OPEN.  A failure to open
 * might be due to bad token or missing server.
 *
 * For a simple example of how to use this functionality in a tool see:
 *
 * https://github.com/tsugiproject/socket-test
 *
 * For a more complex tool that uses but does not require the notification service
 * please see:
 *
 * https://github.com/tsugitools/michat
 *
 * This tool keeps message and presence information in database tables and uses
 * the notification service to avoid a slow and costly polling approach.  But the
 * application falls back to the slow/costly polling approach so it works with
 * or without WebSockets.  If it has Web Sockets it is both quicker and
 * performs better.
 *
 */
class WebSocket {

    /**
     * Determine if this server is configured for web sockets.
     *
     * Set these values in your config.php:
     *
     *    $CFG->websocket_secret = 'opensource';
     *    $CFG->websocket_url = 'ws://localhost:2021';
     *
     * You can run a local notification service for your development
     * by doing the following:
     *
     *    cd tsugi/admin
     *    php rachet.php
     *
     * You need to keep the rachet.php running for websockets to work.
     *
     * The web socket server does not have to be on the same server as
     * the Tsugi hosting server.  You can support more than one Tsugi
     * server with a single rachet server as long as all of the Tsugi
     * servers have the websocket_url and websocket_secret.
     *
     *     $CFG->websocket_url = 'wss://socket.tsugicloud.org:443';
     *
     * If the Tsugi tool server is running https, then it needs a
     * socket server that runs wss.  The TsugiCloud server uses
     * CloudFlare to converts its ws server to a wss server.
     *
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

    /**
     * Returns the port that the configured web socket server
     */
    public static function getPort() {
        global $CFG;
        if ( ! self::enabled() ) return null;
        $pieces = parse_url($CFG->websocket_url);
        return U::get($pieces,'port');
    }

    /**
     * Returns the proxyport if set in config
     */
    public static function getProxyPort() {
        global $CFG;
        if ( ! self::enabled() ) return null;
        return $CFG->websocket_proxyport;
    }

    /**
     * Returns the host that the configured web socket server
     */
    public static function getHost() {
        global $CFG;
        if ( ! self::enabled() ) return null;
        $pieces = parse_url($CFG->websocket_url);
        return U::get($pieces,'host');
    }

    /**
     * Build a plaintext token for a particular link_id
     *
     * The token includes the host, link_id, context_id, and user_id
     *
     * @param string $launch The LTI launch object
     *
     * @return string The plaintext token or false if we cannot make a token
     */
    public static function makeToken($launch) {
        global $CFG;
        if ( ! isset($launch->link->id) ) return false;
        $token = $CFG->wwwroot . '::' . $launch->link->id . '::';
        $token .= isset($launch->context->id) ? $launch->context->id : 'no_context';
        $token .= '::';
        $token .= isset($launch->user->id) ? $launch->context->id : 'no_user';
        return $token;
    }

    /**
     * Build and sign a token for a particular link_id
     *
     * @param string $launch The LTI launch object
     *
     * @return string The encrypted token or false if we cannot make a token
     */
    public static function getToken($launch) {
        global $CFG;
        if ( ! isset($CFG->websocket_secret) || strlen($CFG->websocket_secret) < 1 ) return false;
        $plain = self::makeToken($launch);
        if ( ! $plain ) return $plain;
        $encrypted = AesOpenSSL::encrypt($plain, $CFG->websocket_secret) ;
        return $encrypted;
    }

    /**
     * Decode and parse a token to make sure it is valid
     *
     * @param string $token The encrypted token
     *
     * @return string The plaintext token (or false on failure)
     */
    public static function decodeToken($token) {
        global $CFG;
        $plain = AesOpenSSL::decrypt($token, $CFG->websocket_secret, 256) ;
        if ( ! is_string($plain) ) return false;
        $pieces = explode('::', $plain);
        if ( count($pieces) != 4 ) return false;
        return $plain;
    }

    /**
     * Pull out the host and link_id so as to create the "space"
     *
     * The token includes the host, link_id, context_id, and user_id.
     * The space includes the host and link_id.
     *
     * @param string $token The plaintext token
     *
     * @return string The space for this link_id
     */
    public static function getSpaceFromToken($token) {
        $pieces = explode('::', $token);
        if ( count($pieces) != 4 ) return false;
        $space = implode('::', array_slice($pieces,0,2));
        return $space;
    }

}
