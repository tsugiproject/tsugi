<?php

require_once "src/UI/MenuEntry.php";
require_once "src/UI/Menu.php";
require_once "src/UI/MenuSet.php";


class MenuSetTest extends PHPUnit_Framework_TestCase
{
    public function testBasics() {
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

    public function testChain() {
        $set = new \Tsugi\UI\MenuSet();
        $set->addHome('Home','http://www.tsugi.org/')
            ->addleft('Left 1 IMS', 'http://www.imsglobal.org')
            ->addleft('Left 2 SAK', 'http://www.sakiaproject.org')
            ->addRight('Right 1 Settings', 'about.php')
            ->addRight('Right 2 Settings', 'settings.php');
        
        // print_r($set);
        $expected = '{"home":{"menu":[{"link":"Home","href":"http:\/\/www.tsugi.org\/"}]},"left":{"menu":[{"link":"Left 1 IMS","href":"http:\/\/www.imsglobal.org"},{"link":"Left 2 SAK","href":"http:\/\/www.sakiaproject.org"}]},"right":{"menu":[{"link":"Right 2 Settings","href":"settings.php"},{"link":"Right 1 Settings","href":"about.php"}]}}';
        // echo(json_encode($set));
        $this->assertEquals($expected,json_encode($set));
    }

    public function testSub() {
        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('Sakai', 'http://www.sakaiproject.org/')
            ->addLink('Apereo', 'http://www.apereo.org');

        $menu = new \Tsugi\UI\Menu();
        $menu->addLink('Home','http://www.tsugi.org/')
            ->addLink('Settings', 'settings.php')
            ->addLink('More...', $submenu)
            ->addLink('GitHub', 'http://www.github.org');
        print_r($menu);
        // echo(json_encode($menu->menu, JSON_PRETTY_PRINT));
    }

}
