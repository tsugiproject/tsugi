<?php

require_once("src/Core/SessionTrait.php");
use \Tsugi\Core\SessionTrait;

class SessionTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $this->assertTrue(true);
    }

}
