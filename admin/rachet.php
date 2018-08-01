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
        if ( $token != 'xyzzy' ) {
            error_log('Not authorized\r\n');
            return;
        }
        $room = U::get($queryarray,'room');
        if (! is_numeric($room) ) $room = null;
        $conn->room = $room;
        $conn->token = $token;
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        if ( ! isset($from->token) ) return;
        foreach ($this->clients as $client) {
            if ( ! isset($client->token) ) return;
            $from_room = isset($from->room) ? $from->room : false;
            $client_room = isset($client->room) ? $client->room : false;
echo("FR=".$from_room." / ".$from->token." CL=".$client_room." / ".$client->token."\r\n");
            if ( $from_room != $client_room ) continue;
            if ( $from->token != $client->token ) continue;
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

if ( isset($CFG->websocket_port) ) {
    // Run the server application through the WebSocket protocol on port 2021
    $app = new Ratchet\App('localhost', $CFG->websocket_port);
    $app->route('/notify', new MyNotify);
    echo("Websocket server started on port $CFG->websocket_port\r\n");
    $app->run();
} else {
    echo("Error: CFG->websocket_port is not set, websocket server not started \r\n");
}
