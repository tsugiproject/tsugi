<?php

require_once "src/Core/SessionTrait.php";
require_once "src/UI/MenuEntry.php";
require_once "src/UI/Menu.php";
require_once "src/UI/MenuSet.php";
require_once "src/UI/Output.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Core/Launch.php";
require_once "tests/Mock/MockSession.php";


class MenuSetTest extends \PHPUnit\Framework\TestCase
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
        $expected = '{"home":[{"link":"Home","href":"http:\/\/www.tsugi.org\/","attr":false}],"left":false,"right":[{"link":"IMS","href":"http:\/\/www.imsglobal.org\/","attr":false}]}';
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
        $expected = '{"home":{"link":"Home","href":"http:\/\/www.tsugi.org\/","attr":false},"left":{"menu":[{"link":"Left 1 IMS","href":"http:\/\/www.imsglobal.org","attr":false},{"link":"Left 2 SAK","href":"http:\/\/www.sakiaproject.org","attr":false}]},"right":{"menu":[{"link":"Right 2 About","href":"about.php","attr":false},{"link":"Right 1 Settings","href":"settings.php","attr":false}]}}';
        // echo(json_encode($set));
        $this->assertEquals($expected,json_encode($set));

        $export_str = $set->export(false);
        $expected = '{"home":{"link":"Home","href":"http:\/\/www.tsugi.org\/","attr":false},"left":[{"link":"Left 1 IMS","href":"http:\/\/www.imsglobal.org","attr":false},{"link":"Left 2 SAK","href":"http:\/\/www.sakiaproject.org","attr":false}],"right":[{"link":"Right 2 About","href":"about.php","attr":false},{"link":"Right 1 Settings","href":"settings.php","attr":false}]}';
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
        $this->assertTrue(is_object($menu));
        $this->assertTrue(is_array($menu->menu));
        $this->assertTrue(is_object($menu->menu[2]));
        $this->assertEquals($menu->menu[2]->link,"More...");


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

    /**
     * Test addRight with push=false parameter and session export/import
     * 
     * When addRight is called with push=false, items should be appended (not prepended)
     * to the menu, maintaining the order they were added. This differs from the default
     * behavior where items are prepended (push=true).
     * 
     * Also tests the ability to export a menu to JSON, store it in session, and import
     * it back while preserving the order.
     */
    public function testAddRightWithPushFalseAndSessionExportImport() {
        global $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');
        
        // Create a MenuSet and add items to the right menu with push=false
        // This should append items in the order they're added (not prepend them)
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Home', 'http://www.tsugi.org/')
            ->addRight('First Item', 'first.php', false)  // push=false means append
            ->addRight('Second Item', 'second.php', false)
            ->addRight('Third Item', 'third.php', false);
        
        // Verify items are in the order they were added (not reversed)
        // With push=false, items should appear: First, Second, Third
        $this->assertNotNull($set->right);
        $this->assertTrue(is_object($set->right));
        $this->assertEquals('First Item', $set->right->menu[0]->link);
        $this->assertEquals('Second Item', $set->right->menu[1]->link);
        $this->assertEquals('Third Item', $set->right->menu[2]->link);
        
        // Export the menu to JSON
        $export_str = $set->export(false);
        $this->assertIsString($export_str);
        $this->assertStringContainsString('First Item', $export_str);
        $this->assertStringContainsString('Second Item', $export_str);
        $this->assertStringContainsString('Third Item', $export_str);
        
        // Import the menu back from JSON
        $imported_set = \Tsugi\UI\MenuSet::import($export_str);
        $this->assertNotNull($imported_set);
        $this->assertTrue(is_object($imported_set));
        
        // Verify the imported menu maintains the correct order
        $this->assertNotNull($imported_set->right);
        $this->assertTrue(is_object($imported_set->right));
        $this->assertEquals('First Item', $imported_set->right->menu[0]->link);
        $this->assertEquals('Second Item', $imported_set->right->menu[1]->link);
        $this->assertEquals('Third Item', $imported_set->right->menu[2]->link);
        
        // Verify export/import round-trip produces identical JSON
        $re_export_str = $imported_set->export(false);
        $this->assertEquals($export_str, $re_export_str);
        
        // Test storing in session and retrieving
        // Create a Launch object with a mock session for Output to use
        $launch = new \Tsugi\Core\Launch();
        $launch->session_object = new MockSession();
        
        $O = new \Tsugi\UI\Output();
        $O->launch = $launch;
        $O->topNavSession($set);
        
        // Retrieve from session
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        $session_export = $O->session_get($sess_key);
        $this->assertNotNull($session_export);
        $this->assertEquals($export_str, $session_export);
        
        // Import from session
        $session_set = \Tsugi\UI\MenuSet::import($session_export);
        $this->assertNotNull($session_set);
        $this->assertTrue(is_object($session_set));
        
        // Verify the session-imported menu maintains the correct order
        $this->assertNotNull($session_set->right);
        $this->assertTrue(is_object($session_set->right));
        $this->assertEquals('First Item', $session_set->right->menu[0]->link);
        $this->assertEquals('Second Item', $session_set->right->menu[1]->link);
        $this->assertEquals('Third Item', $session_set->right->menu[2]->link);
        
        // Verify session round-trip produces identical JSON
        $session_re_export = $session_set->export(false);
        $this->assertEquals($export_str, $session_re_export);
    }

    /**
     * Test import with attr as string
     */
    public function testImportWithAttrString() {
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":"class=\"test\" id=\"home\""},"left":false,"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertNotNull($set->home);
        $this->assertEquals('class="test" id="home"', $set->home->attr);
    }

    /**
     * Test import with attr as object
     */
    public function testImportWithAttrObject() {
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":{"class":"test","id":"home","data-value":"123"}},"left":false,"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertNotNull($set->home);
        // Should be converted to string format
        $expected_attr = 'class="test" id="home" data-value="123"';
        $this->assertEquals($expected_attr, $set->home->attr);
    }

    /**
     * Test import with attr as array
     */
    public function testImportWithAttrArray() {
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":{"class":"test","id":"home"}},"left":false,"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertNotNull($set->home);
        // Should be converted to string format
        $expected_attr = 'class="test" id="home"';
        $this->assertEquals($expected_attr, $set->home->attr);
    }

    /**
     * Test import with attr containing special characters (XSS protection)
     */
    public function testImportWithAttrSpecialCharacters() {
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":{"onclick":"alert(\'xss\')","class":"test"}},"left":false,"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertNotNull($set->home);
        // Should escape quotes properly
        $attr = $set->home->attr;
        $this->assertStringContainsString('onclick="alert(&#039;xss&#039;)"', $attr);
        $this->assertStringContainsString('class="test"', $attr);
    }

    /**
     * Test import with nested menu entries having attr as object
     */
    public function testImportWithNestedAttrObject() {
        // Use the same format as export produces - left/right are arrays, not objects
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":false},"left":[{"link":"Link 1","href":"link1.php","attr":{"class":"active","data-id":"1"}}],"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertNotNull($set->left);
        $this->assertTrue(is_object($set->left));
        $this->assertCount(1, $set->left->menu);
        $expected_attr = 'class="active" data-id="1"';
        $this->assertEquals($expected_attr, $set->left->menu[0]->attr);
    }

    /**
     * Test import/export round-trip with attr as object
     */
    public function testImportExportRoundTripWithAttrObject() {
        $json_str = '{"home":{"link":"Home","href":"http://example.com/","attr":{"class":"navbar-brand","id":"home-link"}},"left":false,"right":false}';
        $set = \Tsugi\UI\MenuSet::import($json_str);
        
        $this->assertNotNull($set);
        $this->assertEquals('class="navbar-brand" id="home-link"', $set->home->attr);
        
        // Export and re-import
        $export_str = $set->export(false);
        $reimported = \Tsugi\UI\MenuSet::import($export_str);
        
        $this->assertNotNull($reimported);
        // Note: export will serialize attr as string, so re-import will treat it as string
        $this->assertEquals('class="navbar-brand" id="home-link"', $reimported->home->attr);
    }

}
