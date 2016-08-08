<?php

require_once "src/UI/MenuEntry.php";
require_once "src/UI/Menu.php";
require_once "src/UI/MenuSet.php";


class MenuSetTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct() {
        $right = new \Tsugi\UI\Menu();
        $x = new \Tsugi\UI\MenuEntry('IMS','http://www.imsglobal.org/');
        $right->add($x);
        $this->assertEquals($right->menu[0]->href,'http://www.imsglobal.org/');
        $set = new \Tsugi\UI\MenuSet();
        $set->right = $right;
        $home = new \Tsugi\UI\Menu();
        $x = new \Tsugi\UI\MenuEntry('Home','http://www.tsugi.org/');
        $home->add($x);
        $set->home = $home;
        // print_r($set);
        $expected = '{"home":{"menu":[{"link":"Home","href":"http:\/\/www.tsugi.org\/"}]},"left":false,"right":{"menu":[{"link":"IMS","href":"http:\/\/www.imsglobal.org\/"}]}}';
        // echo(json_encode($set));
        $this->assertEquals($expected,json_encode($set));
    }

}
