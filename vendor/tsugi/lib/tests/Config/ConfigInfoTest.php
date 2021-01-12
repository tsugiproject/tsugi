<?php

require "src/Config/ConfigInfo.php";

use \Tsugi\Config\ConfigInfo;

class ConfigInfoTest extends PHPUnit_Framework_TestCase
{
    public function testGet() {
        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)),
            'http://localhost:8888/tsugi');
        $this->assertEquals('http://localhost:8888/tsugi', $CFG->wwwroot);
        $this->assertEquals(realpath(dirname(__FILE__)), $CFG->dirroot);
    }

}
