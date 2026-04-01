<?php

require_once "src/Controllers/Calendar.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use Tsugi\Controllers\Calendar;
use Tsugi\Lumen\Application;

class CalendarControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $mockLaunch;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
        }

        $this->mockLaunch = new \stdClass();
        $this->mockLaunch->output = new \stdClass();
        $this->mockLaunch->output->buffer = true;

        $this->mockApp = new Application($this->mockLaunch);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testRoutesRegistersCalendarRoute()
    {
        Calendar::routes($this->mockApp);
        $routes = $this->mockApp->router->getRoutes();
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        $hasCal = false;
        $hasJson = false;
        foreach ($uris as $uri) {
            if ($uri === '/calendar' || $uri === '/calendar/') {
                $hasCal = true;
            }
            if ($uri === '/calendar/json' || $uri === '/calendar/json/') {
                $hasJson = true;
            }
        }
        $this->assertTrue($hasCal, 'Should register /calendar route');
        $this->assertTrue($hasJson, 'Should register /calendar/json route');
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/calendar', Calendar::ROUTE, 'ROUTE constant should be /calendar');
    }
}
