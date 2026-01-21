<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CC_Topic.php";

use \Tsugi\Util\CC_Topic;

class CC_TopicTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneral() {
        $web_dom = new CC_Topic();
        $web_dom->set_title('Why program?');
        $web_dom->set_text('We learn why one might want to learn to program, and look at the basic issues with learning to program.');
        $save = $web_dom->saveXML();
        $xmlout = '<?xml version="1.0"?>
<topic xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imsdt_v1p1.xsd">
  <title>Why program?</title>
  <text>We learn why one might want to learn to program, and look at the basic issues with learning to program.</text>
</topic>
';
        $this->assertEquals($xmlout,$save);

    }
}
