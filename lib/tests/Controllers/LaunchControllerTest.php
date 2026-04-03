<?php

require_once "src/Controllers/LaunchController.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\LaunchController;
use \Tsugi\Lumen\Application;

class LaunchControllerTest extends \PHPUnit\Framework\TestCase
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

    public function testRoutesRegistersLaunchRoute()
    {
        LaunchController::routes($this->mockApp);

        $routes = $this->mockApp->router->getRoutes();
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }

        $hasReturn = in_array('/launch/_launch_return', $uris, true) || in_array('/launch/_launch_return/', $uris, true);
        $hasLaunch = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/launch/{resource_link_id}') === 0) {
                $hasLaunch = true;
                break;
            }
        }
        $this->assertTrue($hasReturn, 'Should register /launch/_launch_return route');
        $this->assertTrue($hasLaunch, 'Should register /launch/{resource_link_id} route');
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/launch', LaunchController::ROUTE, 'ROUTE constant should be /launch');
    }
}
