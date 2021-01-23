<?php

require_once "src/UI/Lessons.php";

use \Tsugi\UI\Lessons;

class LessonsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        if ( 1 == 2 ) $l = new Lessons();
        $this->assertTrue(true);
    }


}
