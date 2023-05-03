<?php

require_once("src/Core/WebSocket.php");
require_once('src/Crypt/AesOpenSSL.php');
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Core\WebSocket;

class WebSocketTest extends \PHPUnit\Framework\TestCase
{
    public function testEnabled() {
        global $CFG;
        $CFG->wwwroot = 'http://localhost:8888';
        $CFG = $CFG ?? new \Tsugi\Config\ConfigInfo("dir", "www");
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
        $launch = new \stdClass();
        $this->assertFalse(WebSocket::getToken($launch));
        $launch->link = new \stdClass();
        $this->assertFalse(WebSocket::getToken($launch));
        $launch->link->id = 42;
        $token = WebSocket::getToken($launch);
        $this->assertNotEquals($token, 'http://localhost:8888::42::no_context::no_user');
        $decode = WebSocket::decodeToken('X'.$token);
        $this->assertFalse($decode);
        $decode = WebSocket::decodeToken($token);
        $this->assertEquals($decode, 'http://localhost:8888::42::no_context::no_user');
        $space = WebSocket::getSpaceFromToken($decode);
        $this->assertEquals($space, 'http://localhost:8888::42');
    }

}
