<?php

require_once("src/Core/Activity.php");
use \Tsugi\Core\Activity;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $activity = new \Tsugi\Core\Activity();
        $this->assertInstanceOf(\Tsugi\Core\Activity::class, $activity, 'Activity should instantiate correctly');
    }

}
