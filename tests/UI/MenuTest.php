<?php

require_once "src/UI/MenuEntry.php";
require_once "src/UI/Menu.php";


class MenuTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct() {
        $left = new \Tsugi\UI\Menu();
        $x = new \Tsugi\UI\MenuEntry('<b>x</b>','y');
        $left->add($x);
        $this->assertEquals($left->menu[0]->href,'y');
    }

}
