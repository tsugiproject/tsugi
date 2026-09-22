<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/UI/Lessons.php";
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

    /**
     * Test renderAssignments() with items array
     */
    public function testRenderAssignmentsWithItemsArray() {
        global $_SERVER, $_SESSION;
        $originalServer = $_SERVER ?? null;
        $originalSession = $_SESSION ?? null;
        
        $_SERVER['REQUEST_URI'] = '/test/path';
        $_SESSION = [];
        
        $lessons = new class extends \Tsugi\UI\Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $lessons->lessons = new \stdClass();
        $lessons->lessons->title = 'Test Course';
        $lessons->lessons->modules = [
            (object)[
                'title' => 'Module 1',
                'anchor' => 'mod1',
                'items' => [
                    (object)['type' => 'lti', 'title' => 'Assignment 1', 'resource_link_id' => 'rlid1'],
                    (object)['type' => 'lti', 'title' => 'Assignment 2', 'resource_link_id' => 'rlid2'],
                    (object)['type' => 'video', 'title' => 'Video 1'] // Should be skipped
                ]
            ],
            (object)[
                'title' => 'Module 2',
                'anchor' => 'mod2',
                'items' => [
                    (object)['type' => 'discussion', 'title' => 'Discussion 1', 'resource_link_id' => 'rlid3'] // Should be skipped
                ]
            ]
        ];
        
        $allgrades = ['rlid1' => 0.9, 'rlid2' => 0.5];
        $alldates = [];
        
        $output = \Tsugi\Controllers\Assignments::renderAssignments($lessons, $allgrades, $alldates, true);
        
        // Verify assignments from items array are rendered
        $this->assertStringContainsString('Assignment 1', $output, 'Should render LTI assignments from items array');
        $this->assertStringContainsString('Assignment 2', $output, 'Should render multiple LTI assignments');
        $this->assertStringContainsString('Module 1', $output, 'Should render module title');
        
        // Verify non-LTI items are skipped
        $this->assertStringNotContainsString('Video 1', $output, 'Should not render non-LTI items');
        $this->assertStringNotContainsString('Discussion 1', $output, 'Should not render discussion items');
        
        // Restore $_SERVER and $_SESSION
        $_SERVER = $originalServer;
        $_SESSION = $originalSession;
    }
    
    /**
     * Test renderAssignments() - items array takes precedence over legacy lti array
     */

    /**
     * Test renderAssignments() - items array takes precedence over legacy lti array
     */
    public function testRenderAssignmentsItemsArrayPrecedence() {
        global $_SERVER, $_SESSION;
        $originalServer = $_SERVER ?? null;
        $originalSession = $_SESSION ?? null;
        
        $_SERVER['REQUEST_URI'] = '/test/path';
        $_SESSION = [];
        
        $lessons = new class extends \Tsugi\UI\Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $lessons->lessons = new \stdClass();
        $lessons->lessons->title = 'Test Course';
        $lessons->lessons->modules = [
            (object)[
                'title' => 'Module 1',
                'anchor' => 'mod1',
                'items' => [
                    (object)['type' => 'lti', 'title' => 'Assignment from items', 'resource_link_id' => 'rlid1']
                ],
                'lti' => [
                    (object)['title' => 'Assignment from legacy', 'resource_link_id' => 'rlid2']
                ]
            ]
        ];
        
        $allgrades = ['rlid1' => 0.9];
        $alldates = [];
        
        $output = \Tsugi\Controllers\Assignments::renderAssignments($lessons, $allgrades, $alldates, true);
        
        // Should only render assignment from items array
        $this->assertStringContainsString('Assignment from items', $output, 'Should render assignment from items array');
        $this->assertStringNotContainsString('Assignment from legacy', $output, 'Should NOT render assignment from legacy array when items array exists');
        
        // Restore $_SERVER and $_SESSION
        $_SERVER = $originalServer;
        $_SESSION = $originalSession;
    }

}
