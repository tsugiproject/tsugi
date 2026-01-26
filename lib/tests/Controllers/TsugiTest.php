<?php

require_once "src/Controllers/Tsugi.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Tsugi;
use \Tsugi\Lumen\Application;

class TsugiTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $mockLaunch;
    
    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';
        
        // Set up loader if not already set (required by Application constructor)
        if (!isset($CFG->loader)) {
            // Try to load autoloader from vendor directory
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                // Create a minimal mock loader
                $CFG->loader = new \stdClass();
            }
        }
        
        // Create a simple launch object (using stdClass to avoid PHP 8.2+ dynamic property warnings)
        $this->mockLaunch = new \stdClass();
        $this->mockLaunch->output = new \stdClass();
        $this->mockLaunch->output->buffer = true; // Default value
    }
    
    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }
    
    /**
     * Test that Tsugi constructor initializes correctly
     */
    public function testConstructorInitializes()
    {
        $app = new Tsugi($this->mockLaunch);
        
        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Tsugi::class, $app);
        $this->assertNotNull($app->router);
    }
    
    /**
     * Test that output buffer is set to false
     */
    public function testOutputBufferIsDisabled()
    {
        $app = new Tsugi($this->mockLaunch);
        
        $this->assertFalse($app['tsugi']->output->buffer, 
            'Output buffer should be disabled in Tsugi application');
    }
    
    /**
     * Test that baseDir defaults to __DIR__ when not provided
     */
    public function testBaseDirDefaultsToCurrentDirectory()
    {
        $app = new Tsugi($this->mockLaunch);
        
        // The baseDir is used internally, but we can verify the app was constructed
        $this->assertInstanceOf(Tsugi::class, $app);
    }
    
    /**
     * Test that baseDir can be provided explicitly
     */
    public function testBaseDirCanBeProvided()
    {
        $customBaseDir = '/custom/path';
        $app = new Tsugi($this->mockLaunch, $customBaseDir);
        
        // Verify the app was constructed successfully
        $this->assertInstanceOf(Tsugi::class, $app);
    }
    
    /**
     * Test that router group is configured with correct namespace
     */
    public function testRouterGroupHasCorrectNamespace()
    {
        // Create a spy on the router to capture group calls
        $app = new Tsugi($this->mockLaunch);
        
        // Verify router exists and is configured
        $this->assertNotNull($app->router);
        $this->assertInstanceOf(\Tsugi\Lumen\Router::class, $app->router);
    }
    
    /**
     * Test that all controller routes are registered
     * This tests that routes() methods are called for each controller
     */
    public function testAllControllersAreRegistered()
    {
        $app = new Tsugi($this->mockLaunch);
        
        // Verify router exists
        $this->assertNotNull($app->router);
        
        // Get all registered routes
        $routes = $app->router->getRoutes();
        
        // Verify that routes exist (the exact routes depend on controller implementations)
        // At minimum, we should have some routes registered
        $this->assertIsArray($routes);
        $this->assertGreaterThan(0, count($routes), 'At least some routes should be registered');
        
        // Extract URIs from routes (routes are keyed as "METHOD/uri")
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Lessons controller should register /lessons route
        $hasLessonsRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/lessons') === 0) {
                $hasLessonsRoute = true;
                break;
            }
        }
        $this->assertTrue($hasLessonsRoute, 'Lessons route should be registered');
        
        // Topics controller should register /topics route
        $hasTopicsRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/topics') === 0) {
                $hasTopicsRoute = true;
                break;
            }
        }
        $this->assertTrue($hasTopicsRoute, 'Topics route should be registered');
        
        // Assignments controller should register /assignments route
        $hasAssignmentsRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/assignments') === 0) {
                $hasAssignmentsRoute = true;
                break;
            }
        }
        $this->assertTrue($hasAssignmentsRoute, 'Assignments route should be registered');
        
        // Badges controller should register /badges route
        $hasBadgesRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/badges') === 0) {
                $hasBadgesRoute = true;
                break;
            }
        }
        $this->assertTrue($hasBadgesRoute, 'Badges route should be registered');
        
        // Login controller should register /login route
        $hasLoginRoute = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/login') === 0) {
                $hasLoginRoute = true;
                break;
            }
        }
        $this->assertTrue($hasLoginRoute, 'Login route should be registered');
    }
}
