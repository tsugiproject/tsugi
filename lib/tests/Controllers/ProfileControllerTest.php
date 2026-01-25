<?php

require_once "src/Controllers/Profile.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Profile;
use \Tsugi\Lumen\Application;

class ProfileControllerTest extends \PHPUnit\Framework\TestCase
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
     * Test that Profile::routes() registers routes correctly
     */
    public function testRoutesRegistersCorrectRoutes()
    {
        // Register routes
        Profile::routes($this->mockApp);
        
        // Get registered routes
        $routes = $this->mockApp->router->getRoutes();
        
        // Extract URIs from routes
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Should have /profile route
        $hasProfileRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/profile') === 0) {
                $hasProfileRoute = true;
                break;
            }
        }
        $this->assertTrue($hasProfileRoute, 'Should register /profile route');
        
        // Should have redirect route
        $hasRedirectRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/tsugi_controllers_profile') === 0) {
                $hasRedirectRoute = true;
                break;
            }
        }
        $this->assertTrue($hasRedirectRoute, 'Should register redirect route');
    }
    
    /**
     * Test that ROUTE constant is correct
     */
    public function testRouteConstant()
    {
        $this->assertEquals('/profile', Profile::ROUTE, 'ROUTE constant should be /profile');
    }
    
    /**
     * Test that REDIRECT constant is correct
     */
    public function testRedirectConstant()
    {
        $this->assertEquals('tsugi_controllers_profile', Profile::REDIRECT, 
            'REDIRECT constant should be tsugi_controllers_profile');
    }
}
