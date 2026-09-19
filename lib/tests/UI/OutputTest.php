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
        return ! empty($_SESSION['id']);
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
        $this->assertFalse($this->menuSetHasLessons($set));
        $this->assertFalse($this->menuSetHasCoursesWidget($set));
    }

    public function testDefaultMenuSetIncludesCoursesWidgetWhenEnabled() {
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->wwwroot = 'http://example.com/tsugi';
        $CFG->setExtension('show_courses_widget', true);
        $_SESSION['id'] = 1;

        $OUTPUT = new Output();
        $set = $OUTPUT->defaultMenuSet();
        $this->assertTrue($this->menuSetHasCoursesWidget($set));

        unset($_SESSION['id']);
        $CFG->setExtension('show_courses_widget', false);
        $_SESSION['id'] = 1;
        $set = $OUTPUT->defaultMenuSet();
        $this->assertFalse($this->menuSetHasCoursesWidget($set));
        unset($_SESSION['id']);
    }

    public function testDefaultMenuSetIncludesLessonsWhenConfigured() {
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->lessons = '/tmp/lessons.json';
        $CFG->context_title = 'Web Applications for Everybody';

        $OUTPUT = new Output();
        $set = $OUTPUT->defaultMenuSet();
        $this->assertTrue($this->menuSetHasLessons($set));
    }

    public function testDefaultMenuSetOmitsLessonsWithoutContextTitle() {
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->lessons = '/tmp/lessons.json';

        $OUTPUT = new Output();
        $set = $OUTPUT->defaultMenuSet();
        $this->assertFalse($this->menuSetHasLessons($set));
    }

    public function testDefaultMenuSetIncludesAdminWhenAdminCookieSet() {
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->wwwroot = 'http://example.com/tsugi';
        $CFG->google_client_id = 'test-google-client';
        $_SESSION['id'] = 1;
        $_COOKIE['adminmenu'] = 'true';

        $OUTPUT = new Output();
        $set = $OUTPUT->defaultMenuSet();
        $this->assertTrue($this->rightDropdownHasLink($set, 'Admin'));

        $_COOKIE['adminmenu'] = 'false';
        $set = $OUTPUT->defaultMenuSet();
        $this->assertFalse($this->rightDropdownHasLink($set, 'Admin'));
        unset($_SESSION['id'], $_COOKIE['adminmenu']);
    }

    public function testTopNavOmitsSessionLessonsWhenNotConfigured() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';

        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Session Home', 'http://example.com/session');
        $set->addLeft('Lessons', 'http://example.com/lessons');
        $set->addLeft('Tools', 'http://example.com/tsugi/store');

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $OUTPUT->topNavSession($set);
        $menu_txt = $OUTPUT->topNav();

        $this->assertStringContainsString('Session Home', $menu_txt);
        $this->assertStringContainsString('Tools', $menu_txt);
        $this->assertStringNotContainsString('>Lessons<', $menu_txt);
        $this->assertStringNotContainsString('http://example.com/lessons', $menu_txt);
    }

    public function testTopNavKeepsSessionLessonsWhenConfigured() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->lessons = '/tmp/lessons.json';
        $CFG->context_title = 'Web Applications for Everybody';

        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Session Home', 'http://example.com/session');
        $set->addLeft('Lessons', 'http://example.com/lessons');

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $OUTPUT->topNavSession($set);
        $menu_txt = $OUTPUT->topNav();

        $this->assertStringContainsString('>Lessons<', $menu_txt);
        $this->assertStringContainsString('http://example.com/lessons', $menu_txt);
    }

    public function testTopNavShowsSessionLogoutOnTheRight() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';

        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Session Home', 'http://example.com/session');
        $set->addRight('Logout', 'http://example.com/logout');

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $OUTPUT->topNavSession($set);
        $menu_txt = $OUTPUT->topNav();

        $this->assertStringContainsString('navbar-right', $menu_txt);
        $this->assertStringContainsString('>Logout<', $menu_txt);
        $this->assertStringContainsString('http://example.com/logout', $menu_txt);
    }

    public function testAvatarMenuTriggerUsesGoogleAvatarWhenPresent() {
        $_SESSION['avatar'] = 'https://example.com/photo.jpg';
        $html = Output::avatarMenuTrigger();
        $this->assertStringContainsString('https://example.com/photo.jpg', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
        $this->assertStringContainsString('border-radius: 50%', $html);
        $this->assertStringContainsString('width: 2em', $html);
        $this->assertStringContainsString('height: 2em', $html);
        unset($_SESSION['avatar']);
    }

    public function testAvatarMenuTriggerFallsBackToFakeAvatar() {
        unset($_SESSION['avatar']);
        $html = Output::avatarMenuTrigger();
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('gravatar.com/avatar', $html);
        $this->assertStringContainsString('border-radius: 50%', $html);
        $this->assertStringContainsString('width: 2em', $html);
        $this->assertStringContainsString('height: 2em', $html);
    }

    private function menuSetHasLessons($set) {
        if ( ! $set->left ) {
            return false;
        }
        foreach ( $set->left->menu as $entry ) {
            if ( ($entry->link ?? '') === 'Lessons' ) {
                return true;
            }
            if ( is_string($entry->href ?? null) && preg_match('#/lessons/?$#', $entry->href) ) {
                return true;
            }
        }
        return false;
    }

    private function menuSetHasCoursesWidget($set) {
        if ( ! $set->right ) {
            return false;
        }
        foreach ( $set->right->menu as $entry ) {
            if ( is_string($entry->link ?? null) && strpos($entry->link, 'tsugi-courses') !== false ) {
                return true;
            }
        }
        return false;
    }

    private function rightDropdownHasLink($set, $label) {
        if ( ! $set->right ) {
            return false;
        }
        foreach ( $set->right->menu as $entry ) {
            if ( ! is_array($entry->href ?? null) ) {
                continue;
            }
            foreach ( $entry->href as $child ) {
                if ( ($child->link ?? '') === $label ) {
                    return true;
                }
            }
        }
        return false;
    }

    public function testTopNavTopMenuCallback() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->top_menu_callback = function() {
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

    public function testTopNavCourseMountedSkipsSiteCallback() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';
        $CFG->wwwroot = 'http://example.com/tsugi';
        $CFG->top_menu_callback = function() {
            $set = new \Tsugi\UI\MenuSet();
            $set->setHome('Callback Home', 'http://example.com/callback');
            return $set;
        };

        $prevUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $menu_txt = $OUTPUT->topNav();

        if ( $prevUri === null ) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $prevUri;
        }

        $this->assertStringContainsString('Home', $menu_txt);
        $this->assertStringNotContainsString('Callback Home', $menu_txt);
        $this->assertStringContainsString('/courses/42/home', $menu_txt);
    }

    public function testTopNavSessionOnCookieSessionPage() {
        if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
        global $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)), 'http://example.com/tsugi');
        $CFG->servicename = 'Test Site';
        $CFG->apphome = 'http://example.com';

        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Session Home', 'http://example.com/session');

        $OUTPUT = new Output();
        $OUTPUT->launch = new \Tsugi\Core\Launch();
        $OUTPUT->buffer = true;
        $OUTPUT->topNavSession($set);
        $menu_txt = $OUTPUT->topNav();

        $this->assertStringContainsString('Session Home', $menu_txt);
    }

    public function testNavbarAlignCssIsDesktopOnlyAndCoversLeftWidgets() {
        $css = Output::navbarAlignCss();
        $this->assertStringContainsString('@media (min-width: 768px)', $css);
        $this->assertStringContainsString('#tsugi_main_nav_bar', $css);
        $this->assertStringContainsString('navbar-main', $css);
        $this->assertStringContainsString('tsugi-wc-nav-item', $css);
        $this->assertStringContainsString('tsugi-course-nav-icon', $css);
        $this->assertStringNotContainsString('@media (max-width: 767px)', $css);
    }

}
