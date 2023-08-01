<?php

require_once("src/Core/Activity.php");
use \Tsugi\Core\Activity;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Activity();
        $this->assertTrue(true);
    }

}
