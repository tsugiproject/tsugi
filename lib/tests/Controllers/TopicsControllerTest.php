<?php

require_once "src/Controllers/Topics.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Topics;
use \Tsugi\Lumen\Application;

class TopicsControllerTest extends \PHPUnit\Framework\TestCase
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
     * Test that Topics::routes() registers routes correctly
     */
    public function testRoutesRegistersCorrectRoutes()
    {
        // Register routes
        Topics::routes($this->mockApp);
        
        // Get registered routes
        $routes = $this->mockApp->router->getRoutes();
        
        // Extract URIs from routes
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Should have /topics route
        $hasTopicsRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/topics') === 0) {
                $hasTopicsRoute = true;
                break;
            }
        }
        $this->assertTrue($hasTopicsRoute, 'Should register /topics route');
        
        // Should have redirect route
        $hasRedirectRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/tsugi_controllers_topics') === 0) {
                $hasRedirectRoute = true;
                break;
            }
        }
        $this->assertTrue($hasRedirectRoute, 'Should register redirect route');
        
        // Should have /topics_launch route
        $hasLaunchRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/topics_launch') === 0) {
                $hasLaunchRoute = true;
                break;
            }
        }
        $this->assertTrue($hasLaunchRoute, 'Should register /topics_launch route');
    }
    
    /**
     * Test that ROUTE constant is correct
     */
    public function testRouteConstant()
    {
        $this->assertEquals('/topics', Topics::ROUTE, 'ROUTE constant should be /topics');
    }
    
    /**
     * Test that REDIRECT constant is correct
     */
    public function testRedirectConstant()
    {
        $this->assertEquals('tsugi_controllers_topics', Topics::REDIRECT, 
            'REDIRECT constant should be tsugi_controllers_topics');
    }
}
