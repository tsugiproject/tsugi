<?php

require "src/Util/Net.php";

use \Tsugi\Util\Net;

class NetTest extends \PHPUnit\Framework\TestCase
{
    public function testGet() {
        $stuff = Net::doGet("https://www.dr-chuck.com/page1.htm");
        // Handle rate limiting or network failures gracefully in CI
        if ($stuff === false || empty($stuff)) {
            $this->markTestSkipped('External HTTP request failed (likely rate limiting in CI)');
            return;
        }
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
    }

    public function testGetStream() {
        $stuff = Net::getStream("https://www.dr-chuck.com/page1.htm");
        // Handle rate limiting or network failures gracefully in CI
        if ($stuff === false) {
            $this->markTestSkipped('External HTTP request failed (likely rate limiting in CI)');
            return;
        }
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
    }

    public function testGetCurl() {
        global $LastCurlError;
        $stuff = Net::getCurl("https://www.dr-chuck.com/page1.htm");
        // Handle rate limiting or network failures gracefully in CI
        if ($stuff === false || empty($stuff) || $LastCurlError) {
            $this->markTestSkipped('External HTTP request failed (likely rate limiting in CI)');
            return;
        }
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
        $this->assertFalse($LastCurlError);
    }

    public function testGetCurlFail() {
        global $LastCurlError;
        // Wow - AT&T fakes a domain response - Thanks AT&T
        $stuff = Net::getCurl("https://fail.lkdfjdfljfdlj1298.com/page1.htm");
        $this->assertTrue(is_string($LastCurlError));
    }

    public function testGetIP() {
        $stuff = Net::getIP("https://www.dr-chuck.com/page1.htm");
        $this->assertNull($stuff);
    }

    public function testRoutable() {
        $this->assertFalse(Net::isRoutable('bob'));
        $this->assertFalse(Net::isRoutable('172.20.5.5'));
        $this->assertFalse(Net::isRoutable('10.20.5.5'));
        $this->assertFalse(Net::isRoutable('192.168.5.5'));
        $this->assertTrue(Net::isRoutable('120.138.20.36'));
        $this->assertTrue(Net::isRoutable('35.8.1.10'));
        $this->assertTrue(Net::isRoutable('141.8.1.10'));
    }

}
