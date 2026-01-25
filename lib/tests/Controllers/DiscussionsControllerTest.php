<?php

require_once "src/Controllers/Discussions.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Discussions;
use \Tsugi\Lumen\Application;

class DiscussionsControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $mockLaunch;
    private $mockApp;
    
    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';
        
        // Set up loader if not already set
        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
        }
        
        // Create a simple launch object
        $this->mockLaunch = new \stdClass();
        $this->mockLaunch->output = new \stdClass();
        $this->mockLaunch->output->buffer = true;
        
        // Create a mock application
        $this->mockApp = new Application($this->mockLaunch);
    }
    
    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }
    
    /**
     * Test that Discussions::routes() registers routes correctly
     */
    public function testRoutesRegistersCorrectRoutes()
    {
        // Register routes
        Discussions::routes($this->mockApp);
        
        // Get registered routes
        $routes = $this->mockApp->router->getRoutes();
        
        // Extract URIs from routes
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Should have /discussions route
        $hasDiscussionsRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/discussions') === 0) {
                $hasDiscussionsRoute = true;
                break;
            }
        }
        $this->assertTrue($hasDiscussionsRoute, 'Should register /discussions route');
        
        // Should have /discussions_launch route
        $hasLaunchRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/discussions_launch') === 0) {
                $hasLaunchRoute = true;
                break;
            }
        }
        $this->assertTrue($hasLaunchRoute, 'Should register /discussions_launch route');
    }
    
    /**
     * Test that ROUTE constant is correct
     */
    public function testRouteConstant()
    {
        $this->assertEquals('/discussions', Discussions::ROUTE, 'ROUTE constant should be /discussions');
    }
}
