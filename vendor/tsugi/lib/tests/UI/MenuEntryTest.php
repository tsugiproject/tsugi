<?php

require_once "src/UI/MenuEntry.php";

use \Tsugi\UI\MenuEntry;

class MenyEntryTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct() {
        $left = new MenuEntry('<b>x</b>','y');
        $this->assertEquals($left->link,'<b>x</b>');
        $this->assertEquals($left->href,'y');
        $x = MenuEntry::separator();
        $this->assertEquals($x->link,'----------');
        $this->assertFalse($x->href);
    }


}
