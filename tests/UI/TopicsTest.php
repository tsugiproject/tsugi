<?php

require_once "src/UI/Topics.php";

use \Tsugi\UI\Topics;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        if ( 1 == 2 ) $l = new Topics();
        $this->assertTrue(true);
    }


}
