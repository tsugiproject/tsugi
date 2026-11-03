<?php

if (!function_exists('__')) {
    function __($s) {
        return $s;
    }
}

use Tsugi\Core\Context;
use Tsugi\Core\ReqScope;

class HomeMainPageProbe extends \Tsugi\Controllers\Home {
    public $instructor = false;

    protected function isInstructor() {
        return $this->instructor;
    }

    public function mainPage() {
        return $this->mainPageForHome();
    }
}

class HomeControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalPDOX;
    private $originalRequestUri;
    private $originalSession;

    protected function setUp(): void
    {
        global $CFG, $PDOX;

        $this->originalCFG = $CFG ?? null;
        $this->originalPDOX = $PDOX ?? null;
        $this->originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost/tsugi');
        $CFG->dbprefix = 'test_';
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/tsugi';

        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION = array();
        ReqScope::reset();

        $PDOX = new class {
            public $calls = 0;
            public $lastSql = '';
            public $row = false;
            public function rowDie($sql, $params = array()) {
                $this->calls++;
                $this->lastSql = $sql;
                return $this->row;
            }
        };
    }

    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPDOX;
        if ( $this->originalRequestUri === null ) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
        }
        $_SESSION = $this->originalSession;
        ReqScope::reset();
    }

    private function setContext($id) {
        ReqScope::reset();
        ReqScope::provision(0, 0, null, ReqScope::ORIGIN_SITE);
        $context = new Context();
        $context->id = $id;
        ReqScope::current()->context = $context;
    }

    private function renderHome(array $vars) {
        extract($vars, EXTR_SKIP);
        ob_start();
        include __DIR__ . '/../../src/Controllers/templates/Home/index.inc.php';
        return ob_get_clean();
    }

    public function testDefaultHomeKeepsPlaceholderCopy() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'main_page' => null,
        ));
        $this->assertStringContainsString('Django for Everybody', $html);
        $this->assertStringContainsString('This home page feature is under construction.', $html);
        $this->assertStringNotContainsString('page-content', $html);
    }

    public function testMainPageReplacesPlaceholderCopy() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'main_page' => array(
                'title' => 'Welcome',
                'body_html' => '<p>Start with the syllabus.</p>',
                'published' => 1,
                'edit_url' => '/courses/42/pages/edit/7',
            ),
        ));
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('<p>Start with the syllabus.</p>', $html);
        $this->assertStringContainsString('/courses/42/pages/edit/7', $html);
        $this->assertStringNotContainsString('under construction', $html);
        $this->assertStringNotContainsString('not published', $html);
    }

    public function testUnpublishedMainPageWarnsInstructors() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'main_page' => array(
                'title' => 'Draft home',
                'body_html' => '<p>Not yet.</p>',
                'published' => 0,
                'edit_url' => '/courses/42/pages/edit/8',
            ),
        ));
        $this->assertStringContainsString('Draft home', $html);
        $this->assertStringContainsString('This page is not published.', $html);
        $this->assertStringNotContainsString('under construction', $html);
    }

    public function testNoContextSkipsTheMainPageLookup() {
        global $PDOX;
        $probe = new HomeMainPageProbe();
        $this->assertNull($probe->mainPage());
        $this->assertSame(0, $PDOX->calls);
    }

    public function testInstructorSeesDraftMainPageOnHome() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = array(
            'page_id' => 7,
            'title' => 'Welcome',
            'body' => '<p>Welcome home</p><img src="$IMS-CC-FILEBASE$files/hello.png" alt="hi">',
            'published' => 0,
            'is_main' => 1,
        );
        $probe = new HomeMainPageProbe();
        $probe->instructor = true;
        $page = $probe->mainPage();

        $this->assertIsArray($page);
        $this->assertSame('Welcome', $page['title']);
        $this->assertSame(0, $page['published']);
        $this->assertSame('/courses/42/pages/edit/7', $page['edit_url']);
        $this->assertStringContainsString('Welcome home', $page['body_html']);
        $this->assertStringContainsString('files/hello.png', $page['body_html']);
        $this->assertStringNotContainsString('$IMS-CC-FILEBASE$', $page['body_html']);
        $this->assertStringContainsString('is_main = 1', $PDOX->lastSql);
        $this->assertStringNotContainsString('published = 1', $PDOX->lastSql);
    }

    public function testStudentHomeRequiresAPublishedMainPage() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = false;
        $probe = new HomeMainPageProbe();
        $probe->instructor = false;
        $this->assertNull($probe->mainPage());
        $this->assertStringContainsString('is_main = 1', $PDOX->lastSql);
        $this->assertStringContainsString('published = 1', $PDOX->lastSql);
    }

    public function testEmptyMainPageLeavesTheDefaultHome() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = array(
            'page_id' => 9,
            'title' => '   ',
            'body' => '',
            'published' => 1,
            'is_main' => 1,
        );
        $probe = new HomeMainPageProbe();
        $probe->instructor = true;
        $this->assertNull($probe->mainPage());
    }
}
