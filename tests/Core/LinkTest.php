<?php

require_once("src/Core/Link.php");
use \Tsugi\Core\Link;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Link();
        $this->assertTrue(true);
    }

}
