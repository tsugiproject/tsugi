<?php

require_once "src/Config/ConfigInfo.php";
require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Core/SessionTrait.php";
require_once "src/UI/MenuSet.php";
require_once "src/UI/Output.php";
require_once "src/Core/Launch.php";

use \Tsugi\UI\Output;

if ( ! function_exists('isLoggedIn') ) {
    function isLoggedIn() {
        return false;
    }
}

class OutputTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $OUTPUT = new Output();
        $this->assertTrue(is_object($OUTPUT));
    }

    /**
     * Test suppressSiteNav and enableSiteNav methods
     */
    public function testSuppressAndEnableSiteNav() {
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $launch = new \Tsugi\Core\Launch();
        $OUTPUT = new Output();
        $OUTPUT->launch = $launch;

        // Initially should not be suppressed
        $suppressed = $_SESSION[Output::SUPPRESS_SITE_NAV] ?? false;
        $this->assertFalse($suppressed);

        // Suppress site nav
        $OUTPUT->suppressSiteNav();
        $suppressed = $_SESSION[Output::SUPPRESS_SITE_NAV] ?? false;
        $this->assertTrue($suppressed);

        // Enable site nav
        $OUTPUT->enableSiteNav();
        $suppressed = $_SESSION[Output::SUPPRESS_SITE_NAV] ?? false;
        $this->assertFalse($suppressed);
    }

    /**
     * Test suppressSiteNav constant is defined
     */
    public function testSuppressSiteNavConstant() {
        $this->assertEquals('TSUGI_OUTPUT_SUPPRESS_SITE_NAV', Output::SUPPRESS_SITE_NAV);
    }

    /**
     * ConfigInfo defaults defaultmenu to false; defaultMenuSet() must still build a menu.
     */
    public function testDefaultMenuSetWhenDefaultmenuIsFalse() {
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $this->assertFalse($CFG->defaultmenu);

        $OUTPUT = new Output();
        $set = $OUTPUT->defaultMenuSet();

        $this->assertInstanceOf(\Tsugi\UI\MenuSet::class, $set);
        $this->assertNotFalse($set->home);
        $this->assertEquals('Test Site', $set->home->link);
        $this->assertEquals('http://example.com', $set->home->href);
    }

    public function testTopNavRefreshMenuCallback() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->refresh_menu_callback = function() {
            $set = new \Tsugi\UI\MenuSet();
            $set->setHome('Callback Home', 'http://example.com/callback');
            return $set;
        };

        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $menu_txt = $OUTPUT->topNav();

        $this->assertStringContainsString('Callback Home', $menu_txt);
        $this->assertInstanceOf(\Tsugi\UI\MenuSet::class, $CFG->defaultmenu);
    }

}
