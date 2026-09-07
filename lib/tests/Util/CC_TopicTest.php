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
        $this->assertStringContainsString('xmlns="'.\Tsugi\Util\CC::TOPIC_NS.'"', $save);
        $this->assertStringContainsString(\Tsugi\Util\CC::TOPIC_SCHEMA_LOCATION, $save);
        $this->assertStringContainsString('<title>Why program?</title>', $save);
        $this->assertStringContainsString('We learn why one might want to learn to program', $save);
        $this->assertStringNotContainsString('imsccv1p1', $save);

    }
}
