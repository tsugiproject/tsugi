<?php

require_once "src/Controllers/Lessons.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Lessons;
use \Tsugi\Lumen\Application;

class LessonsControllerTest extends \PHPUnit\Framework\TestCase
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
        
        // Create a simple launch object (using stdClass to avoid PHP 8.2+ dynamic property warnings)
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
     * Test that Lessons::routes() registers routes correctly
     */
    public function testRoutesRegistersCorrectRoutes()
    {
        // Register routes
        Lessons::routes($this->mockApp);
        
        // Get registered routes
        $routes = $this->mockApp->router->getRoutes();
        
        // Check for expected routes
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Should have /lessons route
        $this->assertContains('/lessons', $uris, 'Should register /lessons route');
        
        // Note: Router normalizes trailing slashes, so /lessons/ becomes /lessons
        // Check that we have at least the base /lessons route
        $hasLessonsBase = false;
        foreach ($uris as $uri) {
            if ($uri === '/lessons' || $uri === '/lessons/') {
                $hasLessonsBase = true;
                break;
            }
        }
        $this->assertTrue($hasLessonsBase, 'Should register /lessons route (with or without trailing slash)');
        
        // Should have redirect route
        $this->assertContains('/tsugi_controllers_lessons', $uris, 'Should register redirect route');
    }
    
    /**
     * Test that ROUTE constant is correct
     */
    public function testRouteConstant()
    {
        $this->assertEquals('/lessons', Lessons::ROUTE, 'ROUTE constant should be /lessons');
    }
    
    /**
     * Test that REDIRECT constant is correct
     */
    public function testRedirectConstant()
    {
        $this->assertEquals('tsugi_controllers_lessons', Lessons::REDIRECT, 
            'REDIRECT constant should be tsugi_controllers_lessons');
    }
}
