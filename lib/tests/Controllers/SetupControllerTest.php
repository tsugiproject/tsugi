<?php

require_once "src/Controllers/Setup.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

use \Tsugi\Controllers\Setup;
use \Tsugi\Lumen\Application;

class SetupControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
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
    }

    public function testRouteConstant()
    {
        $this->assertSame('/setup', Setup::ROUTE);
        $this->assertSame('Setup', Setup::NAME);
    }

    public function testRoutesRegistersGetAndPost()
    {
        Setup::routes($this->mockApp);
        $uris = array();
        foreach ($this->mockApp->router->getRoutes() as $route) {
            $uris[] = $route['uri'];
        }
        $this->assertContains('/setup', $uris);
        $this->assertContains('/setup/export', $uris);
        $this->assertContains('/setup/export/download', $uris);
    }

    public function testShowInMenuFalseWithoutManifest()
    {
        $this->assertFalse(Setup::showInMenu());
        $_SESSION['id'] = 1;
        $_SESSION['context_id'] = 1;
        $_SESSION['instructor'] = true;
        $this->assertFalse(Setup::showInMenu());
    }
}
