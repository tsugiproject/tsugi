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

    public function pageFor($page_id) {
        return $this->pageForHome($page_id);
    }

    public function heroUrl() {
        return $this->courseHeroUrl();
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
            public $imageRow = false;
            public function rowDie($sql, $params = array()) {
                $this->calls++;
                $this->lastSql = $sql;
                if ( is_string($sql) && str_contains($sql, 'context_images') ) {
                    return $this->imageRow;
                }
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

    public function testDefaultHomeFloatsAPlaceholderHero() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'context_id' => 42,
            'hero_url' => '',
            'layout' => array('blocks' => array(
                array('type' => 'hero', 'place' => 'right'),
                array('type' => 'page', 'page_id' => 0),
                array('type' => 'file', 'path' => '', 'place' => 'inline'),
                array('type' => 'calendar', 'on' => false),
                array('type' => 'dues', 'on' => false),
            )),
            'can_configure' => false,
        ));
        $this->assertStringContainsString('Django for Everybody', $html);
        $this->assertStringContainsString('Please configure the home page', $html);
        $this->assertStringContainsString('tsugi-home-hero-right', $html);
        $this->assertStringContainsString('tsugi-home-hero-placeholder', $html);
        $this->assertStringNotContainsString('under construction', $html);
        $this->assertStringNotContainsString('page-content', $html);
        $this->assertStringNotContainsString('<img class="tsugi-home-hero"', $html);
        $this->assertStringNotContainsString('glyphicon-cog', $html);
    }

    public function testInstructorGearConfiguresOrderedBlocks() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'can_configure' => true,
            'save_url' => '/courses/42/home',
            'layout' => array('blocks' => array(
                array('type' => 'dues', 'on' => true),
                array('type' => 'hero', 'on' => false),
                array('type' => 'page', 'page_id' => 7),
                array('type' => 'calendar', 'on' => false),
            )),
            'pages' => array(
                array('page_id' => 7, 'title' => 'Welcome', 'published' => 1),
                array('page_id' => 8, 'title' => 'Draft', 'published' => 0),
            ),
        ));
        $this->assertStringContainsString('glyphicon-cog', $html);
        $this->assertStringContainsString('Include hero image', $html);
        $this->assertStringContainsString('Center', $html);
        $this->assertStringContainsString('Float right', $html);
        $this->assertStringContainsString('Float left', $html);
        $this->assertStringContainsString('Add page', $html);
        $this->assertStringContainsString('Show calendar', $html);
        $this->assertStringContainsString('Show due date list', $html);
        $this->assertStringContainsString('name="page_id[]"', $html);
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('Draft (draft)', $html);
        $dues = strpos($html, 'value="dues"');
        $hero = strpos($html, 'value="hero"');
        $page = strpos($html, 'value="page"');
        $calendar = strpos($html, 'value="calendar"');
        $this->assertNotFalse($dues);
        $this->assertLessThan($hero, $dues);
        $this->assertLessThan($page, $hero);
        $this->assertLessThan($calendar, $page);
    }

    public function testBlocksRenderInLayoutOrder() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'hero_url' => 'http://localhost/tsugi/courses/42/image/hero?v=1',
            'calendar_html' => '<p>MONTH-GRID</p>',
            'dues_html' => '<p>DUE-LIST</p>',
            'page_blocks' => array(
                7 => array(
                    'title' => 'Welcome',
                    'body_html' => '<p>Start with the syllabus.</p>',
                    'published' => 1,
                    'edit_url' => '/courses/42/pages/edit/7',
                ),
                9 => array(
                    'title' => 'Schedule',
                    'body_html' => '<p>Week one meets Monday.</p>',
                    'published' => 1,
                    'edit_url' => '',
                ),
            ),
            'layout' => array('blocks' => array(
                array('type' => 'dues', 'on' => true),
                array('type' => 'page', 'page_id' => 7),
                array('type' => 'page', 'page_id' => 9),
                array('type' => 'hero', 'place' => 'right'),
                array('type' => 'calendar', 'on' => true),
            )),
        ));
        $dues = strpos($html, 'DUE-LIST');
        $page = strpos($html, 'Start with the syllabus.');
        $second = strpos($html, 'Week one meets Monday.');
        $hero = strpos($html, 'class="tsugi-home-hero"');
        $month = strpos($html, 'MONTH-GRID');
        $this->assertNotFalse($dues);
        $this->assertNotFalse($page);
        $this->assertNotFalse($second);
        $this->assertNotFalse($hero);
        $this->assertNotFalse($month);
        $this->assertLessThan($page, $dues);
        $this->assertLessThan($second, $page);
        $this->assertLessThan($hero, $second);
        $this->assertLessThan($month, $hero);
        $this->assertStringContainsString('tsugi-home-hero-right', $html);
        $this->assertStringContainsString('float: right', $html);
        $this->assertStringContainsString('/courses/42/pages/edit/7', $html);
        $this->assertStringNotContainsString('under construction', $html);
        $this->assertStringNotContainsString('not published', $html);
    }

    public function testStudentNotesAppearWhenTheBlockIsOn() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'notes_save_url' => '/courses/42/home/notes',
            'student_note' => "Don't forget the midterm.",
            'layout' => array('blocks' => array(
                array('type' => 'notes', 'on' => true),
            )),
        ));
        $this->assertStringContainsString('Note to self', $html);
        $this->assertStringContainsString('name="student_note"', $html);
        $this->assertStringContainsString('maxlength="500"', $html);
        $this->assertStringContainsString('Don&#039;t forget the midterm.', $html);
        $this->assertStringContainsString('/courses/42/home/notes', $html);
        $this->assertStringNotContainsString('Please configure the home page', $html);
    }

    public function testHeroStaysOffUntilTheRadioIncludesIt() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'hero_url' => 'http://localhost/tsugi/courses/42/image/hero?v=1',
            'layout' => array('blocks' => array(
                array('type' => 'hero', 'on' => false),
            )),
        ));
        $this->assertStringNotContainsString('class="tsugi-home-hero"', $html);
        $this->assertStringContainsString('Please configure the home page', $html);
    }

    public function testMissingHeroUsesTheCatalogPlaceholder() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'can_configure' => true,
            'hero_url' => '',
            'context_id' => 42,
            'layout' => array('blocks' => array(
                array('type' => 'hero', 'place' => 'right'),
            )),
        ));
        $this->assertStringContainsString('tsugi-home-hero-right', $html);
        $this->assertStringContainsString('tsugi-home-hero-placeholder', $html);
        $this->assertStringContainsString('tsugi-course-card-placeholder-art', $html);
        $this->assertStringContainsString('Django for Everybody', $html);
        $this->assertStringContainsString('Please configure the home page', $html);
        $this->assertStringNotContainsString('does not have a hero image yet', $html);
        $this->assertStringNotContainsString('under construction', $html);
    }

    public function testImageFileFloatsAndOtherFilesOpenInANewTab() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'layout' => array('blocks' => array(
                array('type' => 'file', 'path' => 'Public/logo.png', 'place' => 'left'),
                array('type' => 'file', 'path' => 'Public/syllabus.pdf', 'place' => 'right'),
                array('type' => 'file', 'path' => 'Public/banner.png', 'place' => 'inline'),
            )),
            'file_blocks' => array(
                'Public/logo.png' => array(
                    'name' => 'logo.png',
                    'url' => '/courses/42/files/Public/logo.png',
                    'image' => true,
                ),
                'Public/syllabus.pdf' => array(
                    'name' => 'syllabus.pdf',
                    'url' => '/courses/42/files/Public/syllabus.pdf',
                    'image' => false,
                ),
                'Public/banner.png' => array(
                    'name' => 'banner.png',
                    'url' => '/courses/42/files/Public/banner.png',
                    'image' => true,
                ),
            ),
        ));
        $this->assertStringContainsString('class="tsugi-home-block tsugi-home-file-left"', $html);
        $this->assertStringContainsString('float: left', $html);
        $this->assertStringContainsString('src="/courses/42/files/Public/logo.png"', $html);
        $this->assertStringContainsString('class="tsugi-home-block tsugi-home-file-inline"', $html);
        $this->assertStringContainsString('href="/courses/42/files/Public/syllabus.pdf" target="_blank" rel="noopener noreferrer"', $html);
        $this->assertStringNotContainsString('src="/courses/42/files/Public/syllabus.pdf"', $html);
        $this->assertStringNotContainsString('under construction', $html);
    }

    public function testFilePickerShowsPlaceOnlyForImages() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'can_configure' => true,
            'save_url' => '/courses/42/home',
            'layout' => array('blocks' => array(
                array('type' => 'file', 'path' => 'Public/logo.png', 'place' => 'center'),
                array('type' => 'file', 'path' => 'Public/syllabus.pdf', 'place' => 'inline'),
            )),
            'files' => array(
                array('path' => 'Public/logo.png', 'name' => 'logo.png', 'image' => true),
                array('path' => 'Public/syllabus.pdf', 'name' => 'syllabus.pdf', 'image' => false),
            ),
        ));
        $this->assertStringContainsString('Add file', $html);
        $this->assertStringContainsString('name="file_path[]"', $html);
        $this->assertStringContainsString('name="file_place[]"', $html);
        $this->assertStringContainsString('data-image="1"', $html);
        $this->assertStringContainsString('Inline', $html);
        $this->assertMatchesRegularExpression(
            '/value="Public\/logo.png"[^>]*selected[\s\S]*?<fieldset class="tsugi-home-hero-choice tsugi-home-file-place">/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/value="Public\/syllabus.pdf"[^>]*selected[\s\S]*?<fieldset class="tsugi-home-hero-choice tsugi-home-file-place" hidden>/',
            $html
        );
    }

    public function testUnpublishedPageWarnsInstructors() {
        $html = $this->renderHome(array(
            'course_title' => 'Django for Everybody',
            'layout' => array('blocks' => array(
                array('type' => 'page', 'page_id' => 8),
            )),
            'page_blocks' => array(
                8 => array(
                    'title' => 'Draft home',
                    'body_html' => '<p>Not yet.</p>',
                    'published' => 0,
                    'edit_url' => '/courses/42/pages/edit/8',
                ),
            ),
        ));
        $this->assertStringContainsString('Draft home', $html);
        $this->assertStringContainsString('This page is not published.', $html);
        $this->assertStringNotContainsString('under construction', $html);
    }

    public function testCourseHeroUrlUsesMetadataNotTheBlob() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->imageRow = array(
            'hero_bytes' => 1200,
            'hero_updated_at' => '2026-09-18 01:02:03',
            'icon_bytes' => 0,
            'icon_updated_at' => null,
        );
        $probe = new HomeMainPageProbe();
        $url = $probe->heroUrl();
        $this->assertStringContainsString('/courses/42/image/hero', $url);
        $this->assertStringContainsString('v=', $url);
        $this->assertStringContainsString('context_images', $PDOX->lastSql);
        $this->assertStringContainsString('hero_bytes', $PDOX->lastSql);
        $this->assertDoesNotMatchRegularExpression('/\bhero\b/', $PDOX->lastSql);
    }

    public function testMissingHeroLeavesTheUrlEmpty() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->imageRow = false;
        $probe = new HomeMainPageProbe();
        $this->assertSame('', $probe->heroUrl());
    }

    public function testNoContextSkipsThePageLookup() {
        global $PDOX;
        $probe = new HomeMainPageProbe();
        $this->assertNull($probe->pageFor(7));
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
        $page = $probe->pageFor(7);

        $this->assertIsArray($page);
        $this->assertSame('Welcome', $page['title']);
        $this->assertSame(0, $page['published']);
        $this->assertSame('/courses/42/pages/edit/7', $page['edit_url']);
        $this->assertStringContainsString('Welcome home', $page['body_html']);
        $this->assertStringContainsString('files/hello.png', $page['body_html']);
        $this->assertStringNotContainsString('$IMS-CC-FILEBASE$', $page['body_html']);
        $this->assertStringContainsString('page_id = :PID', $PDOX->lastSql);
        $this->assertStringNotContainsString('is_main = 1', $PDOX->lastSql);
        $this->assertStringNotContainsString('published = 1', $PDOX->lastSql);
    }

    public function testRelativePageLinksResolveUnderPages() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = array(
            'page_id' => 7,
            'title' => 'Welcome',
            'body' => '<p><a href="next">Next</a>'
                .'<a href="./more">More</a>'
                .'<a href="../files/pic.png">Pic</a>'
                .'<a href="https://example.com/x">Abs</a>'
                .'<a href="/keep">Root</a>'
                .'<a href="#top">Top</a>'
                .'<a href="mailto:a@b.test">Mail</a></p>'
                .'<img srcset="small.png 1x, big.png 2x" alt="">'
                .'<img srcset="https://cdn.example.com/a.png 1x, /root.png 400w" alt="">',
            'published' => 1,
            'is_main' => 1,
        );
        $probe = new HomeMainPageProbe();
        $probe->instructor = true;
        $page = $probe->pageFor(7);

        $this->assertIsArray($page);
        $html = $page['body_html'];
        $this->assertStringContainsString('href="/courses/42/pages/next"', $html);
        $this->assertStringContainsString('href="/courses/42/pages/more"', $html);
        $this->assertStringContainsString('href="/courses/42/files/pic.png"', $html);
        $this->assertStringContainsString('href="https://example.com/x"', $html);
        $this->assertStringContainsString('href="/keep"', $html);
        $this->assertStringContainsString('href="#top"', $html);
        $this->assertStringContainsString('href="mailto:a@b.test"', $html);
        $this->assertStringContainsString('srcset="/courses/42/pages/small.png 1x, /courses/42/pages/big.png 2x"', $html);
        $this->assertStringContainsString('srcset="https://cdn.example.com/a.png 1x, /root.png 400w"', $html);
    }

    public function testStudentSkipsAnUnpublishedPage() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = array(
            'page_id' => 7,
            'title' => 'Welcome',
            'body' => '<p>Welcome home</p>',
            'published' => 0,
        );
        $probe = new HomeMainPageProbe();
        $probe->instructor = false;
        $this->assertNull($probe->pageFor(7));
        $this->assertStringContainsString('page_id = :PID', $PDOX->lastSql);
    }

    public function testEmptyPageLeavesTheDefaultHome() {
        global $PDOX;
        $this->setContext(42);
        $PDOX->row = array(
            'page_id' => 9,
            'title' => '   ',
            'body' => '',
            'published' => 1,
        );
        $probe = new HomeMainPageProbe();
        $probe->instructor = true;
        $this->assertNull($probe->pageFor(9));
    }
}
