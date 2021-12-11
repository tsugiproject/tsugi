<?php

require "src/Util/Net.php";

use \Tsugi\Util\Net;

class NetTest extends \PHPUnit\Framework\TestCase
{
    public function testGet() {
        $stuff = Net::doGet("http://www.dr-chuck.com/page1.htm");
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
    }

    public function testGetStream() {
        $stuff = Net::getStream("http://www.dr-chuck.com/page1.htm");
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
    }

    public function testGetCurl() {
        global $LastCurlError;
        $stuff = Net::getCurl("http://www.dr-chuck.com/page1.htm");
        $this->assertStringContainsStringIgnoringCase("The First Page",$stuff);
        $this->assertFalse($LastCurlError);
    }

    public function testGetCurlFail() {
        global $LastCurlError;
        $stuff = Net::getCurl("http://fail.lkdfjdfljfdlj1298.com/page1.htm");
        $this->assertTrue(is_string($LastCurlError));
    }

    public function testGetIP() {
        $stuff = Net::getIP("http://www.dr-chuck.com/page1.htm");
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
