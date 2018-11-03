<?php

/** To run

   php rachet.php

   In client:

   // Then some JavaScript in the browser:
   var conn = new WebSocket('ws://localhost:2021/echo');
   conn.onmessage = function(e) { console.log(e.data); };
   conn.onopen = function(e) { conn.send('Hello Me!'); };

   https://github.com/ratchetphp/Ratchet
   https://github.com/cboden/Ratchet-examples
   http://socketo.me/docs/install

*/

require_once "../config.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Tsugi\Util\U;
use Tsugi\Core\WebSocket;

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyNotify implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // https://github.com/ratchetphp/Ratchet/issues/604
        // https://stackoverflow.com/questions/22761900/access-extra-parameters-in-ratchet-web-socket-requests
        $querystring = $querystring = $conn->httpRequest->getUri()->getQuery();
        // echo("QS $querystring \r\n");
        parse_str($querystring,$queryarray);
        $token = U::get($queryarray,'token');
        $decode = WebSocket::decodeToken($token);
        if ( ! $decode ) {
            error_log("Not authorized $token");
            return;
        }
        error_log("New WebSocket $decode");
        $room = U::get($queryarray,'room', null);
        $conn->room = $room;
        $conn->token = $token;
        $conn->decode = $decode;
        $conn->space = WebSocket::getSpaceFromToken($decode);
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        if ( ! isset($from->space) ) return;
        foreach ($this->clients as $client) {
            if ( ! isset($client->space) ) return;
            $from_room = isset($from->room) ? $from->room : false;
            $client_room = isset($client->room) ? $client->room : false;
            echo("From=".$from->space." r=".$from_room." Client=".$client->space." r=".$client_room."\r\n");
            if ( $from_room != $client_room ) continue;
            if ( $from->space != $client->space ) continue;
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

if ( WebSocket::enabled() ) {
    
    $proxyport = WebSocket::getProxyPort();

    if ( $proxyport ) {
        $port = $proxyport;
    } else {
        $port = WebSocket::getPort();
    }
    
    $host = WebSocket::getHost();
    $app = new Ratchet\App($host, $port, '0.0.0.0');
    $app->route('/notify', new MyNotify, array('*'));
    echo("Websocket server started on $host port $port\r\n");
    $app->run();
} else {
    echo("Error: no websocket configuration, websocket server not started \r\n");
}
