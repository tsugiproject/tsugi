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
        $this->assertFalse(WebSocket::enabled(), 'WebSocket should be disabled when url and secret are not set');
        $CFG->websocket_secret = 'xyzzy';
        $this->assertFalse(WebSocket::enabled(), 'WebSocket should be disabled when only secret is set');
        $CFG->websocket_url = 'xyzzy';
        $this->assertFalse(WebSocket::enabled(), 'WebSocket should be disabled when url is invalid');

        $CFG->websocket_url = 'ws://localhost:2021';
        $this->assertTrue(WebSocket::enabled(), 'WebSocket should be enabled when valid url and secret are set');
        $this->assertEquals('localhost', WebSocket::getHost(), 'getHost should extract host from websocket URL');
        $this->assertEquals('2021', WebSocket::getPort(), 'getPort should extract port from websocket URL');

        // For now...
        $launch = new \stdClass();
        $this->assertFalse(WebSocket::getToken($launch), 'getToken should return false when launch has no link');
        $launch->link = new \stdClass();
        $this->assertFalse(WebSocket::getToken($launch), 'getToken should return false when link has no id');
        $launch->link->id = 42;
        $token = WebSocket::getToken($launch);
        $this->assertNotEquals('http://localhost:8888::42::no_context::no_user', $token, 'Token should be encrypted');
        $decode = WebSocket::decodeToken('X'.$token);
        $this->assertFalse($decode, 'decodeToken should return false for invalid token');
        $decode = WebSocket::decodeToken($token);
        $this->assertEquals('http://localhost:8888::42::no_context::no_user', $decode, 'decodeToken should decode valid token');
        $space = WebSocket::getSpaceFromToken($decode);
        $this->assertEquals('http://localhost:8888::42', $space, 'getSpaceFromToken should extract space from decoded token');
    }

}
