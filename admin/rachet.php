<?php

/** To run

   php rachet.php

   In client:

   // Then some JavaScript in the browser:
   var conn = new WebSocket('ws://localhost:2021/echo');
   conn.onmessage = function(e) { console.log(e.data); };
   conn.onopen = function(e) { conn.send('Hello Me!'); };

*/

require_once "../config.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyChat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
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

    // Run the server application through the WebSocket protocol on port 2021
    $app = new Ratchet\App('localhost', 2021);
    $app->route('/chat', new MyChat);
    $app->route('/echo', new Ratchet\Server\EchoServer, array('*'));
    $app->run();
