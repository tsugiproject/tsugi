<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CC_WebLink.php";

use \Tsugi\Util\CC_WebLink;

class CC_WebLinkTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral() {

        $web_dom = new CC_WebLink();
        $web_dom->set_title('Autograder: Single-table SQL');
        $web_dom->set_url('http://www.php-intro.com/lessons.php?anchor=install', array("target" => "_iframe"));
        $save = $web_dom->saveXML();
        $xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2    http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imswl_v1p2.xsd">
  <title>Autograder: Single-table SQL</title>
  <url href="http://www.php-intro.com/lessons.php?anchor=install" target="_iframe"/>
</webLink>
';
        $this->assertEquals($xmlout,$save);

    }
}
