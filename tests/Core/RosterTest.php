<?php

require_once("src/Core/Roster.php");
use \Tsugi\Core\Roster;

class RosterTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Roster();
        $this->assertTrue(true);
    }

}
