<?php

require_once("src/Core/Context.php");
use \Tsugi\Core\Context;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Context();
        $this->assertTrue(true);
    }

}
