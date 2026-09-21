<?php

require_once "src/Controllers/Settings.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

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

    public function testShowInMenuFalseWithoutManifest()
    {
        $this->assertFalse(Settings::showInMenu());
        $_SESSION['id'] = 1;
        $_SESSION['context_id'] = 1;
        $_SESSION['instructor'] = true;
        $this->assertFalse(Settings::showInMenu());
    }
}
