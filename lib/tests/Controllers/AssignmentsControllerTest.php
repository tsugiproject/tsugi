<?php

require_once "src/Controllers/Assignments.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Assignments;
use \Tsugi\Lumen\Application;

class AssignmentsControllerTest extends \PHPUnit\Framework\TestCase
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
     * Test that Assignments::routes() registers routes correctly
     */
    public function testRoutesRegistersCorrectRoutes()
    {
        // Register routes
        Assignments::routes($this->mockApp);
        
        // Get registered routes
        $routes = $this->mockApp->router->getRoutes();
        
        // Extract URIs from routes
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        
        // Should have /assignments and manage-due-dates routes
        $hasAssignmentsRoute = false;
        $hasManageDueDates = false;
        $hasAddLinkRows = false;
        $hasApplyWeekly = false;
        $hasToggleViewDueDates = false;
        foreach ($uris as $uri) {
            if (strpos($uri, '/assignments/toggle-view-due-dates') === 0) {
                $hasToggleViewDueDates = true;
            }
            if (strpos($uri, '/assignments/manage-due-dates/apply-weekly') === 0) {
                $hasApplyWeekly = true;
            }
            if (strpos($uri, '/assignments/manage-due-dates/add-link-rows') === 0) {
                $hasAddLinkRows = true;
            }
            if (strpos($uri, '/assignments/manage-due-dates') === 0) {
                $hasManageDueDates = true;
            }
            if ($uri === '/assignments' || $uri === '/assignments/') {
                $hasAssignmentsRoute = true;
            }
        }
        $this->assertTrue($hasAssignmentsRoute, 'Should register /assignments route');
        $this->assertTrue($hasManageDueDates, 'Should register /assignments/manage-due-dates route');
        $this->assertTrue($hasAddLinkRows, 'Should register /assignments/manage-due-dates/add-link-rows route');
        $this->assertTrue($hasApplyWeekly, 'Should register /assignments/manage-due-dates/apply-weekly route');
        $this->assertTrue($hasToggleViewDueDates, 'Should register /assignments/toggle-view-due-dates route');
    }
    
    /**
     * Test that ROUTE constant is correct
     */
    public function testRouteConstant()
    {
        $this->assertEquals('/assignments', Assignments::ROUTE, 'ROUTE constant should be /assignments');
    }
}
