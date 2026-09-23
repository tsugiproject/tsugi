<?php

require_once "src/Controllers/Settings.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

if (!function_exists('currentContextId')) {
    function currentContextId() {
        return 0;
    }
}
if (!function_exists('loggedInUserId')) {
    function loggedInUserId() {
        return 0;
    }
}
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return !empty($_SESSION['id']);
    }
}
if (!function_exists('__')) {
    function __($s) {
        return $s;
    }
}

use \Tsugi\Controllers\Courses;
use \Tsugi\Controllers\Settings;
use \Tsugi\Lumen\Application;

class SettingsControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $originalServer;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $this->originalServer = $_SERVER;
        $_SESSION = array();

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';
        $CFG->dirroot = dirname(__DIR__, 3);

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
        }

        $mockLaunch = new \stdClass();
        $mockLaunch->output = new \stdClass();
        $mockLaunch->output->buffer = true;
        $this->mockApp = new Application($mockLaunch);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
    }

    public function testRouteConstant()
    {
        $this->assertSame('/settings', Settings::ROUTE);
        $this->assertSame('Settings', Settings::NAME);
    }

    public function testRoutesRegistersSiteAndCoursePages()
    {
        Settings::routes($this->mockApp);
        $uris = array();
        foreach ($this->mockApp->router->getRoutes() as $route) {
            $uris[] = $route['uri'];
        }
        $this->assertContains('/settings', $uris);
        $this->assertContains('/settings/key', $uris);
        $this->assertContains('/settings/export', $uris);
        $this->assertContains('/settings/import', $uris);
        $this->assertContains('/settings/navigation', $uris);
        $this->assertContains('/settings/images', $uris);
        $this->assertContains('/settings/export/download', $uris);
    }

    public function testIsCourseRouteUsesRequestUriNotSession()
    {
        $_SERVER['REQUEST_URI'] = '/settings';
        $this->assertFalse(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/settings/key';
        $this->assertFalse(Settings::isCourseRoute());

        $_SESSION['context_id'] = 42;
        $_SERVER['REQUEST_URI'] = '/settings';
        $this->assertFalse(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/courses/42/settings';
        $this->assertTrue(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/courses/42/settings/export';
        $this->assertTrue(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/courses/42/settings/import';
        $this->assertTrue(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/courses/42/settings/images';
        $this->assertTrue(Settings::isCourseRoute());

        $_SERVER['REQUEST_URI'] = '/tsugi/courses/7/settings/export/download?PHPSESSID=abc';
        $this->assertTrue(Settings::isCourseRoute());
    }

    public function testSelectedExportAnchorsMatchesCurrentModules()
    {
        $l = (object) array(
            'lessons' => (object) array(
                'modules' => array(
                    (object) array('title' => 'Week 1', 'anchor' => 'w1'),
                    (object) array('title' => 'Week 2', 'anchor' => 'w2'),
                ),
            ),
        );
        $this->assertFalse(Settings::selectedExportAnchors($l, false));
        $this->assertFalse(Settings::selectedExportAnchors($l, ''));
        $this->assertFalse(Settings::selectedExportAnchors($l, 'missing'));
        $this->assertSame(array('w1'), Settings::selectedExportAnchors($l, 'w1'));
        $this->assertSame(array('w1', 'w2'), Settings::selectedExportAnchors($l, ' w2, missing, w1 '));
    }

    public function testSelectedImportModulesRequiresAMatch()
    {
        $described = array(
            array('key' => 'M_one', 'title' => 'Week 1'),
            array('key' => 'M_two', 'title' => 'Week 2'),
        );
        $this->assertFalse(Settings::selectedImportModules($described, false));
        $this->assertFalse(Settings::selectedImportModules($described, ''));
        $this->assertFalse(Settings::selectedImportModules($described, 'missing'));
        $this->assertSame(array('M_one'), Settings::selectedImportModules($described, 'M_one'));
        $this->assertSame(array('M_one', 'M_two'), Settings::selectedImportModules($described, 'M_two, M_one'));
    }

    public function testCourseHeadingTitleUsesSessionThenFallback()
    {
        $this->assertSame('Settings', Settings::courseHeadingTitle());
        $_SESSION['context_title'] = '  Django for Everybody  ';
        $this->assertSame('Django for Everybody', Settings::courseHeadingTitle());
        unset($_SESSION['context_title']);
        $_SESSION['lti'] = array('context_title' => 'From LTI');
        $this->assertSame('From LTI', Settings::courseHeadingTitle());
    }

    public function testImportReplaceContentDefaultsToAdd()
    {
        $this->assertFalse(Settings::importReplaceContent(''));
        $this->assertFalse(Settings::importReplaceContent('add'));
        $this->assertFalse(Settings::importReplaceContent('delete'));
        $this->assertFalse(Settings::importReplaceContent(null));
        $this->assertTrue(Settings::importReplaceContent('replace'));
    }

    public function testImportSiteDomainFromApphome()
    {
        global $CFG;
        $CFG->apphome = 'https://local.dj4e.com';
        $CFG->wwwroot = 'https://local.dj4e.com/tsugi';
        $this->assertSame('local.dj4e.com', Settings::importSiteDomain());
        $CFG->apphome = 'https://www.dj4e.com/';
        $this->assertSame('www.dj4e.com', Settings::importSiteDomain());
    }

    public function testImportReplaceDomainConfirmed()
    {
        global $CFG;
        $CFG->apphome = 'https://local.dj4e.com';
        $this->assertTrue(Settings::importReplaceDomainConfirmed('local.dj4e.com'));
        $this->assertTrue(Settings::importReplaceDomainConfirmed('LOCAL.DJ4E.COM'));
        $this->assertTrue(Settings::importReplaceDomainConfirmed('https://local.dj4e.com/'));
        $this->assertFalse(Settings::importReplaceDomainConfirmed(''));
        $this->assertFalse(Settings::importReplaceDomainConfirmed('dj4e.com'));
        $this->assertFalse(Settings::importReplaceDomainConfirmed('www.dj4e.com'));
        $this->assertFalse(Settings::importReplaceDomainConfirmed(null));
    }

    public function testImportReplaceTitleConfirmed()
    {
        $this->assertFalse(Settings::importReplaceTitleConfirmed('Settings'));
        $this->assertFalse(Settings::importReplaceTitleConfirmed(''));
        $_SESSION['context_title'] = 'Django for Everybody';
        $this->assertSame('Django for Everybody', Settings::importReplaceCourseTitle());
        $this->assertTrue(Settings::importReplaceTitleConfirmed('Django for Everybody'));
        $this->assertTrue(Settings::importReplaceTitleConfirmed('  django   for   everybody  '));
        $this->assertFalse(Settings::importReplaceTitleConfirmed('Python for Everybody'));
        $this->assertFalse(Settings::importReplaceTitleConfirmed(null));
        $_SESSION['context_title'] = 'Économie';
        $this->assertTrue(Settings::importReplaceTitleConfirmed('économie'));
        $this->assertTrue(Settings::importReplaceTitleConfirmed('ÉCONOMIE'));
    }

    public function testImportReplaceMembersMatch()
    {
        $this->assertNull(Settings::importReplaceMemberCount());
        $this->assertFalse(Settings::importReplaceMembersConfirmed('40'));
        $this->assertTrue(Settings::importReplaceMembersMatch('40', 40));
        $this->assertTrue(Settings::importReplaceMembersMatch(' 40 ', 40));
        $this->assertTrue(Settings::importReplaceMembersMatch('1,234', 1234));
        $this->assertTrue(Settings::importReplaceMembersMatch('0', 0));
        $this->assertFalse(Settings::importReplaceMembersMatch('', 0));
        $this->assertFalse(Settings::importReplaceMembersMatch('40', 41));
        $this->assertFalse(Settings::importReplaceMembersMatch('forty', 40));
        $this->assertFalse(Settings::importReplaceMembersMatch(null, 40));
    }

    public function testDeleteCourseInputErrorUsesImportChecks()
    {
        global $CFG;
        $CFG->apphome = 'https://local.dj4e.com';
        $this->assertNotNull(Settings::deleteCourseInputError('', 'Django', '1'));
        $_SESSION['context_title'] = 'Django for Everybody';
        $titleError = Settings::deleteCourseInputError('local.dj4e.com', 'nope', '1');
        $this->assertIsString($titleError);
        $this->assertStringContainsString('title', $titleError);
        $memberError = Settings::deleteCourseInputError('local.dj4e.com', 'Django for Everybody', '1');
        $this->assertIsString($memberError);
        $this->assertStringContainsString('members', $memberError);
    }

    public function testSiteHomeCourseDeleteBlocked()
    {
        $this->assertFalse(Settings::siteHomeCourseDeleteBlocked(4));
        $_SESSION[Courses::SESSION_SITE_CONTEXT_ID] = 9;
        $this->assertTrue(Settings::siteHomeCourseDeleteBlocked(9));
        $this->assertFalse(Settings::siteHomeCourseDeleteBlocked(4));
    }

    public function testShowInMenuFalseWithoutManifest()
    {
        $this->assertFalse(Settings::showInMenu());
        $_SESSION['id'] = 1;
        $_SESSION['context_id'] = 1;
        $_SESSION['instructor'] = true;
        $this->assertFalse(Settings::showInMenu());
    }
}
