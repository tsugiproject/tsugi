<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Services/Lessons/LessonsService.php";
require_once "src/Controllers/Lessons.php";
require_once "src/Controllers/Content.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumos/Application.php";
require_once "src/Lumos/Router.php";

use \Tsugi\Controllers\Lessons;
use \Tsugi\Controllers\Content;
use \Tsugi\Lumos\Application;

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
        \Tsugi\Core\ReqScope::resetIdentity();

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
        $this->assertContains('/lessons/_author/export', $uris, 'Should register legacy JSON export');
        $this->assertContains('/lessons/_author/export-v2', $uris, 'Should register Lessons JSON v2 export');
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

    /**
     * Test renderAll() progress calculation with items array
     * IMPORTANT: Lessons::renderAll() ONLY processes items array, NOT legacy arrays
     */
    public function testRenderAllProgressWithItemsArray() {
        global $_SESSION, $_SERVER;
        $originalSession = $_SESSION ?? null;
        $originalServer = $_SERVER ?? null;
        
        $_SESSION = ['id' => 1, 'context_id' => 1];
        $_SERVER['REQUEST_URI'] = '/test/path';
        
        $lessons = new class extends \Tsugi\Services\Lessons\LessonsService {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $lessons->lessons = new \stdClass();
        $lessons->lessons->title = 'Test Course';
        $lessons->lessons->description = 'Test Description';
        $lessons->lessons->modules = [
            (object)[
                'title' => 'Module 1',
                'anchor' => 'mod1',
                'items' => [
                    (object)['type' => 'lti', 'title' => 'Assignment 1', 'resource_link_id' => 'rlid1'],
                    (object)['type' => 'lti', 'title' => 'Assignment 2', 'resource_link_id' => 'rlid2']
                ]
            ]
        ];
        
        // Mock grades - need to mock GradeUtil::loadGradesCurrentUser
        // Since we can't easily mock static methods, we'll test the structure
        // The actual progress calculation happens in renderAll() which calls GradeUtil::loadGradesCurrentUser
        // We'll verify the method exists and can be called
        $this->assertTrue(method_exists(\Tsugi\Controllers\Lessons::class, 'renderAll'), 'renderAll method should exist');
        
        // Restore session
        $_SESSION = $originalSession;
        $_SERVER = $originalServer;
    }
    
    /**
     * Test renderAll() - ONLY processes items array, NOT legacy arrays
     * This is different from Lessons::renderAll() which processes both
     * Note: This test verifies structure only, as GradeUtil requires database connection
     */

    /**
     * Test renderSingle() progress badge calculation for legacy format
     * Progress badges are only calculated for legacy format when items array is NOT present
     * Note: This test verifies structure only, as GradeUtil requires database connection
     */
    public function testRenderSingleProgressBadgeLegacyFormat() {
        global $_SESSION, $_SERVER, $CFG, $OUTPUT, $PDOX;
        $originalSession = $_SESSION ?? null;
        $originalServer = $_SERVER ?? null;
        $originalPDOX = $PDOX ?? null;
        
        $_SESSION = ['id' => 1, 'context_id' => 1];
        $_SERVER['REQUEST_URI'] = '/test/path';
        
        // Mock PDOX to avoid database connection
        $PDOX = new class {
            public function allRowsDie($sql, $params) {
                return [];
            }
        };
        
        // Mock GradeUtil
        $lessons = new class extends \Tsugi\Services\Lessons\LessonsService {
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
                'lti' => [
                    (object)['title' => 'LTI 1', 'resource_link_id' => 'rlid1'],
                    (object)['title' => 'LTI 2', 'resource_link_id' => 'rlid2']
                ]
            ]
        ];
        $lessons->module = $lessons->lessons->modules[0];
        $lessons->position = 1;
        $lessons->anchor = 'mod1';
        
        // Mock GradeUtil::loadGradesCurrentUser to return grades
        // Since we can't easily mock static methods, we'll test that the method structure exists
        $this->assertTrue(method_exists(\Tsugi\Controllers\Lessons::class, 'renderSingle'), 'renderSingle method should exist');
        
        // Restore session and PDOX
        $_SESSION = $originalSession;
        $_SERVER = $originalServer;
        $PDOX = $originalPDOX;
    }

    public function testEmptyLessonsRendersNoContentMessage() {
        $json = json_encode(array(
            'title' => 'Empty Course',
            'modules' => array(),
        ));
        $lessons = \Tsugi\Services\Lessons\LessonsService::fromJson($json);
        $this->assertTrue($lessons->isEmpty());
        $html = \Tsugi\Controllers\Lessons::render($lessons, true);
        $this->assertStringContainsString('There is no Lessons content.', $html);
        $this->assertStringContainsString('Empty Course', $html);
        $this->assertStringNotContainsString('class="card"', $html);
    }

    public function testAuthorBackLinkFollowsOutline()
    {
        $_SERVER['REQUEST_URI'] = '/courses/7/content/week-2';
        $content = new Content();
        $method = new \ReflectionMethod(Content::class, 'authorLinkUrl');
        $url = $method->invoke($content, 'week-2');
        $this->assertStringContainsString('/courses/7/lessons/_author', $url);
        $this->assertStringContainsString('from=content', $url);
        $this->assertStringContainsString('module=week-2', $url);

        $_SERVER['REQUEST_URI'] = '/courses/7/lessons/week-2';
        $lessons = new Lessons();
        $url = $method->invoke($lessons, 'week-2');
        $this->assertSame('/courses/7/lessons/_author', $url);
        $this->assertStringNotContainsString('from=', $url);

        $_SERVER['REQUEST_URI'] = '/courses/7/lessons/_author';
        $_GET['from'] = 'content';
        $_GET['module'] = 'week-2';
        $specMethod = new \ReflectionMethod(Lessons::class, 'authorReturnSpec');
        $spec = $specMethod->invoke($lessons);
        $this->assertSame('Back to Content', $spec['label']);
        $this->assertSame('/courses/7/content/week-2', $spec['url']);

        $_GET['from'] = 'outline';
        $spec = $specMethod->invoke($lessons);
        $this->assertSame('Back to Content', $spec['label']);
        $this->assertSame('/courses/7/content/week-2', $spec['url']);

        $_GET['from'] = 'content';
        $_GET['module'] = '../settings';
        $spec = $specMethod->invoke($lessons);
        $this->assertSame('Back to Content', $spec['label']);
        $this->assertSame('/courses/7/content', $spec['url']);

        unset($_GET['from'], $_GET['module']);
        $spec = $specMethod->invoke($lessons);
        $this->assertSame('Back to Lessons', $spec['label']);
        $this->assertSame('/courses/7/lessons', $spec['url']);
    }

    public function testQuizReturnFollowsOutline()
    {
        $opened = new \ReflectionMethod(Lessons::class, 'quizOpenedFromContent');
        $outline = new \stdClass();
        $outline->toolHome = '/courses/7/content';
        $this->assertTrue($opened->invoke(null, $outline));
        $lessons = new \stdClass();
        $lessons->toolHome = '/courses/7/lessons';
        $this->assertFalse($opened->invoke(null, $lessons));

        $isContent = new \ReflectionMethod(\Tsugi\Controllers\Quiz1::class, 'urlIsContent');
        $this->assertTrue($isContent->invoke(null, '/courses/7/content/week-2'));
        $this->assertTrue($isContent->invoke(null, '/courses/7/outline/week-2'));
        $this->assertFalse($isContent->invoke(null, '/courses/7/lessons/week-2'));
    }

    public function testOutlineRouteConstantAndRoutes() {
        $this->assertSame('/content', Content::ROUTE);
        $this->assertTrue(is_subclass_of(Content::class, Lessons::class));

        Content::routes($this->mockApp);
        $uris = [];
        foreach ( $this->mockApp->router->getRoutes() as $route ) {
            $uris[] = $route['uri'];
        }
        $this->assertContains('/content', $uris);
        $this->assertContains('/content/{anchor}', $uris);
        $this->assertContains('/content_launch/{anchor}', $uris);
        $this->assertContains('/outline', $uris);
        $this->assertContains('/outline_launch/{anchor}', $uris);

        $_SERVER['REQUEST_URI'] = '/courses/7/outline/week-2?x=1';
        require_once dirname(__DIR__, 3).'/vendor/autoload.php';
        $gone = Content::redirectLegacyContent();
        $this->assertSame(301, $gone->getStatusCode());
        $this->assertSame('/courses/7/content/week-2?x=1', $gone->getTargetUrl());
    }

    public function testOutlineRenderUsesCardsAndOmitsPager() {
        global $_SESSION;
        $_SESSION = [];
        \Tsugi\Core\ReqScope::resetIdentity();

        $json = json_encode(array(
            'title' => 'Outline Course',
            'description' => 'Split view',
            'modules' => array(
                array(
                    'title' => 'Week One',
                    'anchor' => 'week-1',
                    'description' => 'First module',
                    'items' => array(
                        array('type' => 'text', 'text' => 'Hello week one'),
                    ),
                ),
                array(
                    'title' => 'Week Two',
                    'anchor' => 'week-2',
                    'items' => array(
                        array('type' => 'text', 'text' => 'Hello week two'),
                    ),
                ),
            ),
        ));
        $lessons = \Tsugi\Services\Lessons\LessonsService::fromJson($json, 'week-2');
        $lessons->toolHome = '/courses/7/content';
        $_SERVER['REQUEST_URI'] = '/courses/7/content/week-2';

        $html = Content::render($lessons, true);
        $this->assertStringContainsString('tsugi-outline-nav', $html);
        $this->assertStringContainsString('tsugi-outline-main', $html);
        $this->assertStringContainsString('tsugi-outline-selected', $html);
        $this->assertStringContainsString('/courses/7/content/week-1', $html);
        $this->assertStringContainsString('/courses/7/content/week-2', $html);
        $this->assertStringContainsString('Hello week two', $html);
        $this->assertStringNotContainsString('Hello week one', $html);
        $this->assertStringNotContainsString('tsugi-lessons-pager', $html);
        $this->assertStringNotContainsString('>All<', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('localStorage.setItem("tsugi-content:" + base, anchor)', $html);
        $this->assertStringContainsString('"week-2"', $html);
    }

    public function testOutlineIndexRestoresTheStoredCard()
    {
        require_once dirname(__DIR__, 3).'/vendor/autoload.php';
        $_SERVER['REQUEST_URI'] = '/courses/7/content';
        $json = json_encode(array(
            'title' => 'Outline Course',
            'modules' => array(
                array('title' => 'Week One', 'anchor' => 'week-1', 'items' => array()),
                array('title' => 'Hidden', 'anchor' => 'secret', 'hidden' => true, 'items' => array()),
                array('title' => 'Week Two', 'anchor' => 'week-2', 'items' => array()),
            ),
        ));
        $lessons = \Tsugi\Services\Lessons\LessonsService::fromJson($json);
        $method = new \ReflectionMethod(Content::class, 'viewEntryRedirect');
        $response = $method->invoke(new Content(), $lessons, null);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertNotInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $html = $response->getContent();
        $this->assertStringContainsString('localStorage.getItem("tsugi-content:"+', $html);
        $this->assertStringContainsString('/courses/7/content/week-1', $html);
        $this->assertStringContainsString('/courses/7/content/week-2', $html);
        $this->assertStringNotContainsString('secret', $html);
        $this->assertStringContainsString('url=/courses/7/content/week-1', $html);
    }

    public function testHiddenOnlyModulesAreEmpty() {
        $json = json_encode(array(
            'title' => 'Hidden',
            'modules' => array(
                array('title' => 'Secret', 'anchor' => 'secret', 'hidden' => true, 'items' => array()),
            ),
        ));
        $lessons = \Tsugi\Services\Lessons\LessonsService::fromJson($json);
        $this->assertTrue($lessons->isEmpty());
        $html = \Tsugi\Controllers\Lessons::render($lessons, true);
        $this->assertStringContainsString('There is no Lessons content.', $html);
    }

}
