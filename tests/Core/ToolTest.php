<?php

require_once("src/Core/Tool.php");
use \Tsugi\Core\Tool;

class ToolTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $tool = new \Tsugi\Core\Tool();
        $this->assertInstanceOf(\Tsugi\Core\Tool::class, $tool, 'Tool should instantiate correctly');
        $this->assertTrue(property_exists($tool, 'analytics'), 'Tool should have analytics property');
    }

}
