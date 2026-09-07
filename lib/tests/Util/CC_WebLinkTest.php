<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CC_WebLink.php";

use \Tsugi\Util\CC_WebLink;

class CC_WebLinkTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneral() {

        $web_dom = new CC_WebLink();
        $web_dom->set_title('Autograder: Single-table SQL');
        $web_dom->set_url('http://www.php-intro.com/lessons.php?anchor=install', array("target" => "_iframe"));
        $save = $web_dom->saveXML();
        $this->assertStringContainsString('xmlns="'.\Tsugi\Util\CC::WL_NS.'"', $save);
        $this->assertStringContainsString(\Tsugi\Util\CC::WL_SCHEMA_LOCATION, $save);
        $this->assertStringContainsString('<title>Autograder: Single-table SQL</title>', $save);
        $this->assertStringContainsString('href="http://www.php-intro.com/lessons.php?anchor=install"', $save);
        $this->assertStringNotContainsString('imsccv1p1', $save);

    }
}
