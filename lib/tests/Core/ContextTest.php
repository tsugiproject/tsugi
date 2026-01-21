<?php

require_once("src/Core/Context.php");
use \Tsugi\Core\Context;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $context = new \Tsugi\Core\Context();
        $this->assertInstanceOf(\Tsugi\Core\Context::class, $context, 'Context should instantiate correctly');
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $context, 'Context should extend Entity');
    }

}
