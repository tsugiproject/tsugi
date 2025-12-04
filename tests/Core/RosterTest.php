<?php

require_once("src/Core/Roster.php");
use \Tsugi\Core\Roster;

class RosterTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $roster = new \Tsugi\Core\Roster();
        $this->assertInstanceOf(\Tsugi\Core\Roster::class, $roster, 'Roster should instantiate correctly');
    }

}
