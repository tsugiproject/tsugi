<?php

require_once("src/Core/Tool.php");
use \Tsugi\Core\Tool;

class ToolTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Tool();
        $this->assertTrue(true);
    }

}
