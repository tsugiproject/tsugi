<?php

require_once "src/Core/SessionTrait.php";
require_once "src/UI/Output.php";
require_once "src/Core/Launch.php";
use \Tsugi\UI\Output;

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
        $suppressed = $OUTPUT->session_get(Output::SUPPRESS_SITE_NAV, false);
        $this->assertFalse($suppressed);

        // Suppress site nav
        $OUTPUT->suppressSiteNav();
        $suppressed = $OUTPUT->session_get(Output::SUPPRESS_SITE_NAV, false);
        $this->assertTrue($suppressed);

        // Enable site nav
        $OUTPUT->enableSiteNav();
        $suppressed = $OUTPUT->session_get(Output::SUPPRESS_SITE_NAV, false);
        $this->assertFalse($suppressed);
    }

    /**
     * Test suppressSiteNav constant is defined
     */
    public function testSuppressSiteNavConstant() {
        $this->assertEquals('TSUGI_OUTPUT_SUPPRESS_SITE_NAV', Output::SUPPRESS_SITE_NAV);
    }

}
