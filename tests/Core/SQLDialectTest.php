<?php

require_once("src/Core/SQLDialect.php");
use \Tsugi\Core\SQLDialect;

class SQLDialectTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $dialect = new \Tsugi\Core\SQLDialect();
        $this->assertInstanceOf(\Tsugi\Core\SQLDialect::class, $dialect, 'SQLDialect should instantiate correctly');
    }

}
