<?php

require "src/Util/Net.php";

use \Tsugi\Util\Net;

class NetTest extends PHPUnit_Framework_TestCase
{
    public function testGet() {
        $stuff = Net::doGet("http://www.dr-chuck.com/page1.htm");
        $this->assertContains("The First Page",$stuff);
    }

    public function testGetStream() {
        $stuff = Net::getStream("http://www.dr-chuck.com/page1.htm");
        $this->assertContains("The First Page",$stuff);
    }

    public function testGetCurl() {
        $stuff = Net::getCurl("http://www.dr-chuck.com/page1.htm");
        $this->assertContains("The First Page",$stuff);
    }

}
