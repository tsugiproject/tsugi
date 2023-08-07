<?php

require_once("src/Core/Key.php");
use \Tsugi\Core\Key;

class KeyTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Key();
        $this->assertTrue(true);
    }

}
