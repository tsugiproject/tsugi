<?php

require_once("src/Core/SQLDialect.php");
use \Tsugi\Core\SQLDialect;

class SQLDialectTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\SQLDialect();
        $this->assertTrue(true);
    }

}
