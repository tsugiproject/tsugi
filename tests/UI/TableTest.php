<?php

require_once "src/UI/Table.php";

use \Tsugi\UI\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $table = new Table();
        $this->assertInstanceOf(\Tsugi\UI\Table::class, $table, 'Table should instantiate correctly');
    }

}
