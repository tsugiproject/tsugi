<?php

require_once "src/UI/MenuEntry.php";
require_once "src/UI/Menu.php";
require_once "src/UI/MenuSet.php";
require_once "src/UI/Output.php";
require_once "src/Config/ConfigInfo.php";


class MenuSetTest extends PHPUnit_Framework_TestCase
{
    public function testBasics() {
        $right = new \Tsugi\UI\Menu();
        $x = new \Tsugi\UI\MenuEntry('IMS','http://www.imsglobal.org/');
        $right->add($x);
        $this->assertEquals($right->menu[0]->href,'http://www.imsglobal.org/');
        $set = new \Tsugi\UI\MenuSet();
        $set->right = $right->menu;
        $home = new \Tsugi\UI\Menu();
        $x = new \Tsugi\UI\MenuEntry('Home','http://www.tsugi.org/');
        $home->add($x);
        $set->home = $home->menu;
        // print_r($set);
        $expected = '{"home":[{"link":"Home","href":"http:\/\/www.tsugi.org\/"}],"left":false,"right":[{"link":"IMS","href":"http:\/\/www.imsglobal.org\/"}]}';
        $this->assertEquals($expected,json_encode($set));
    }

    public function testChain() {
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Home','http://www.tsugi.org/')
            ->addLeft('Left 1 IMS', 'http://www.imsglobal.org')
            ->addLeft('Left 2 SAK', 'http://www.sakiaproject.org')
            ->addRight('Right 1 Settings', 'settings.php')
            ->addRight('Right 2 About', 'about.php');
        
        // print_r($set);
        $expected = '{"home":{"link":"Home","href":"http:\/\/www.tsugi.org\/"},"left":{"menu":[{"link":"Left 1 IMS","href":"http:\/\/www.imsglobal.org"},{"link":"Left 2 SAK","href":"http:\/\/www.sakiaproject.org"}]},"right":{"menu":[{"link":"Right 2 About","href":"about.php"},{"link":"Right 1 Settings","href":"settings.php"}]}}';
        // echo(json_encode($set));
        $this->assertEquals($expected,json_encode($set));

        $export_str = $set->export(false);
        $expected = '{"home":{"link":"Home","href":"http:\/\/www.tsugi.org\/"},"left":[{"link":"Left 1 IMS","href":"http:\/\/www.imsglobal.org"},{"link":"Left 2 SAK","href":"http:\/\/www.sakiaproject.org"}],"right":[{"link":"Right 2 About","href":"about.php"},{"link":"Right 1 Settings","href":"settings.php"}]}';
        $this->assertEquals($expected,$export_str);

        $newset = \Tsugi\UI\MenuSet::import($export_str);
        // print_r($newset);
        $export_str = $newset->export(false);
        $this->assertEquals($expected,$export_str);
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
        // print_r($menu);
        // echo(json_encode($menu->menu, JSON_PRETTY_PRINT));

    }

    public function testMenuOutput() {
        global $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');
        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('Sakai', 'http://www.sakaiproject.org/')
            ->addLink('Apereo', 'http://www.apereo.org');

        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Home','http://www.tsugi.org/')
            ->addLeft('Left 1 IMS', 'http://www.imsglobal.org')
            ->addLeft('Left 2 Open', $submenu)
            ->addRight('Right 1 Settings', 'settings.php')
            ->addRight('Right 2 About', 'about.php');

        // Test the rendering
        $O = new \Tsugi\UI\Output();
        $menu_txt = $O->menuNav($set);
        // echo($menu_txt);
        // Add an outer tag because of the <script> at the end
        $menu_txt = "<outer>".$menu_txt."</outer>";
        $menu_xml = new SimpleXMLElement($menu_txt);
        $this->assertEquals($menu_xml->nav->div->div[0]->a['href'].'','http://www.tsugi.org/');
        $this->assertEquals($menu_xml->nav->div->div[1]['class'].'','navbar-collapse collapse');
        $this->assertEquals($menu_xml->nav->div->div[1]->ul->li[1]->ul->li->a['href'].'','http://www.sakaiproject.org/');
        // echo(json_encode($set,JSON_PRETTY_PRINT));
    }

}
