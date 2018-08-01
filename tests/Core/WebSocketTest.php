<?php

require_once "src/Core/WebSocket.php";

use \Tsugi\Core\WebSocket;

class WebSocketTest extends PHPUnit_Framework_TestCase
{
    public function testEnabled() {
        global $CFG;
        unset($CFG->websocket_url);
        unset($CFG->websocket_secret);
        $this->assertFalse(WebSocket::enabled());
        $CFG->websocket_secret = 'xyzzy';
        $this->assertFalse(WebSocket::enabled());
        $CFG->websocket_url = 'xyzzy';
        $this->assertFalse(WebSocket::enabled());

        $CFG->websocket_url = 'ws://localhost:2021';
        $this->assertTrue(WebSocket::enabled());
        $this->assertEquals(WebSocket::getHost(),'localhost');
        $this->assertEquals(WebSocket::getPort(),'2021');

        // For now...
        $this->assertEquals(WebSocket::getToken(),'xyzzy');
        $this->assertFalse(WebSocket::verifyToken('broke'));
        $this->assertTrue(WebSocket::verifyToken('xyzzy'));
    }

}
