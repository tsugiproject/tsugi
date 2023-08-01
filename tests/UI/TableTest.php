<?php

require_once "src/UI/Table.php";

use \Tsugi\UI\Table;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $x = new Table();
        $this->assertTrue(is_object($x));
    }


}
