<?php

require_once "src/Config/ConfigInfo.php";
require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Util/U.php";
require_once "src/UI/MenuSet.php";
require_once "src/UI/Menu.php";
require_once "src/UI/MenuEntry.php";
require_once "src/Services/CourseNav/CourseNav.php";
require_once "src/Controllers/Tool.php";
require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Catalog.php";
require_once "src/Core/ContextImages.php";

use Tsugi\Controllers\Courses;
use Tsugi\Core\ContextImages;
use Tsugi\Services\CourseNav\CourseNav;

if ( ! function_exists('isLoggedIn') ) {
    function isLoggedIn() {
        return ! empty($_SESSION['id']);
    }
}

class CourseNavTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $originalServer;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $this->originalServer = $_SERVER;
        $_SESSION = array();
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost/tsugi');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost/tsugi';
        if ( function_exists('_tsugiResetIdentitySnapshot') ) {
            _tsugiResetIdentitySnapshot();
        }
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        if ( function_exists('_tsugiResetIdentitySnapshot') ) {
            _tsugiResetIdentitySnapshot();
        }
    }

    public function testDefaultHasCoursesWidgetOnRightThenLogout()
    {
        $doc = CourseNav::defaultDocument();
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertSame(array('lessons', 'files', 'pages', 'quiz1', 'courses_widget', 'logout'), $ids);
        $this->assertTrue($doc['items'][0]['left']);
        $this->assertTrue($doc['items'][1]['left']);
        $this->assertTrue($doc['items'][2]['left']);
        $this->assertTrue($doc['items'][3]['left']);
        $this->assertTrue($doc['items'][4]['right']);
        $this->assertArrayNotHasKey('dropdown', $doc['items'][4]);
        $this->assertTrue($doc['items'][5]['dropdown']);
        $this->assertNotContains('exit_course', $ids);
    }

    public function testDefaultOmitsExitCourseWithoutAppHome()
    {
        global $CFG;
        $CFG->apphome = false;
        $doc = CourseNav::defaultDocument();
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertSame('courses_widget', $ids[count($ids)-2]);
        $this->assertSame('logout', $ids[count($ids)-1]);
        $this->assertArrayNotHasKey('exit_course', CourseNav::catalogById());
    }

    public function testCatalogKeepsExitCourseWhenHomePathSetWithoutAppHome()
    {
        global $CFG;
        $CFG->apphome = false;
        $CFG->home_path = $CFG->wwwroot;
        $doc = CourseNav::defaultDocument();
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertNotContains('exit_course', $ids);
        $this->assertArrayHasKey('exit_course', CourseNav::catalogById());
    }

    public function testNormalizeDropsUnknownAndLockedIds()
    {
        $doc = CourseNav::normalize(array(
            'items' => array(
                array('id' => 'home', 'left' => true),
                array('id' => 'settings', 'dropdown' => true),
                array('id' => 'nope', 'left' => true),
                array('id' => 'files', 'left' => true, 'dropdown' => true),
                array('id' => 'notifications_widget', 'dropdown' => true, 'right' => true),
            ),
        ));
        $this->assertCount(2, $doc['items']);
        $this->assertSame('files', $doc['items'][0]['id']);
        $this->assertTrue($doc['items'][0]['left']);
        $this->assertTrue($doc['items'][0]['dropdown']);
        $this->assertSame('notifications_widget', $doc['items'][1]['id']);
        $this->assertTrue($doc['items'][1]['right']);
        $this->assertArrayNotHasKey('dropdown', $doc['items'][1]);
    }

    public function testNormalizeKeepsInstructorFlag()
    {
        $doc = CourseNav::normalize(array(
            'items' => array(
                array('id' => 'files', 'left' => true, 'instructor' => true),
            ),
        ));
        $this->assertTrue($doc['items'][0]['instructor']);
    }

    public function testNormalizeKeepsCustomHomeLabel()
    {
        $doc = CourseNav::normalize(array(
            'home' => '  Course  ',
            'items' => array(
                array('id' => 'logout', 'dropdown' => true),
            ),
        ));
        $this->assertSame('Course', $doc['home']);
        $this->assertSame('logout', $doc['items'][0]['id']);
    }

    public function testNormalizeDropsDefaultAndUnsafeHomeLabel()
    {
        $plain = CourseNav::normalize(array(
            'home' => 'Home',
            'items' => array(),
        ));
        $this->assertArrayNotHasKey('home', $plain);

        $empty = CourseNav::normalize(array(
            'home' => "  \n  ",
            'items' => array(),
        ));
        $this->assertArrayNotHasKey('home', $empty);

        $stripped = CourseNav::normalize(array(
            'home' => '<b>Start</b>',
            'items' => array(),
        ));
        $this->assertSame('Start', $stripped['home']);

        $long = str_repeat('A', 50);
        $trimmed = CourseNav::normalize(array(
            'home' => $long,
            'items' => array(),
        ));
        $this->assertSame(str_repeat('A', 40), $trimmed['home']);
    }

    public function testCoursesWidgetIsCataloguedAsWidgetOnly()
    {
        $entry = CourseNav::catalogById()['courses_widget'];
        $this->assertSame('widget', $entry['kind']);
        $this->assertSame('Courses widget', $entry['label']);
        $this->assertSame('catalog', $entry['pin']);
        $doc = CourseNav::normalize(array(
            'items' => array(
                array('id' => 'courses_widget', 'dropdown' => true, 'right' => true, 'instructor' => true),
            ),
        ));
        $this->assertCount(1, $doc['items']);
        $this->assertSame('courses_widget', $doc['items'][0]['id']);
        $this->assertTrue($doc['items'][0]['right']);
        $this->assertTrue($doc['items'][0]['instructor']);
        $this->assertArrayNotHasKey('dropdown', $doc['items'][0]);
    }

    public function testCompileCoursesWidgetHonorsInstructorFlag()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Pat';
        $doc = array(
            'items' => array(
                array('id' => 'courses_widget', 'right' => true, 'instructor' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $this->assertFalse($this->rightMenuContains($set, 'tsugi-courses'));

        $_SESSION['isinstructor'] = true;
        $set = CourseNav::compile($doc, 42);
        $this->assertTrue($this->rightMenuContains($set, 'tsugi-courses'));
        $this->assertTrue($this->rightMenuContains($set, 'courses/json'));
    }

    public function testCompileCoursesWidgetCanSitOnTheLeft()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Pat';
        $doc = array(
            'items' => array(
                array('id' => 'courses_widget', 'left' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $this->assertNotFalse($set->left);
        $found = false;
        foreach ( $set->left->menu as $entry ) {
            if ( is_string($entry->link) && strpos($entry->link, 'tsugi-courses') !== false ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertFalse($this->rightMenuContains($set, 'tsugi-courses'));
    }

    /**
     * @param \Tsugi\UI\MenuSet $set
     */
    private function rightMenuContains($set, $needle)
    {
        if ( $set->right === false ) {
            return false;
        }
        foreach ( $set->right->menu as $entry ) {
            if ( is_string($entry->link) && strpos($entry->link, $needle) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \Tsugi\UI\MenuSet $set
     * @return string|null
     */
    private function dropdownHref($set, $label)
    {
        if ( $set->right === false ) {
            return null;
        }
        $last = $set->right->menu[count($set->right->menu)-1];
        if ( ! is_array($last->href) ) {
            return null;
        }
        foreach ( $last->href as $entry ) {
            if ( $entry->link === $label ) {
                return $entry->href;
            }
        }
        return null;
    }

    public function testEditorRowsPinsExitCourseThenLogout()
    {
        $rows = CourseNav::editorRows(CourseNav::defaultDocument());
        $this->assertSame('logout', $rows[count($rows)-1]['id']);
        $this->assertSame('login', $rows[count($rows)-2]['id']);
        $this->assertSame('settings', $rows[count($rows)-3]['id']);
        $this->assertSame('chrome', $rows[count($rows)-3]['kind']);
        $this->assertTrue($rows[count($rows)-1]['dropdown']);
        $this->assertStringContainsString('not logged in', $rows[count($rows)-2]['hint']);
        $this->assertStringContainsString('logged in', $rows[count($rows)-1]['hint']);
        $this->assertSame('home', $rows[0]['id']);
        $this->assertSame('chrome', $rows[0]['kind']);
        $this->assertTrue($rows[0]['rename']);
        $this->assertFalse($rows[0]['custom']);
        $this->assertSame('lessons', $rows[1]['id']);
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = $row['id'];
        }
        $sites = array_search('courses_widget', $ids, true);
        $exit = array_search('exit_course', $ids, true);
        $this->assertNotFalse($sites);
        $this->assertSame($sites + 1, $exit);
        $this->assertSame('catalog', $rows[$sites]['pin']);
        $this->assertSame('widget', $rows[$sites]['kind']);
        $this->assertTrue($rows[$sites]['right']);
        $this->assertFalse($rows[$exit]['dropdown']);
    }

    public function testEditorRowsKeepsSitesWidgetBesideExitEvenWhenEnabled()
    {
        $doc = array(
            'items' => array(
                array('id' => 'files', 'left' => true),
                array('id' => 'courses_widget', 'right' => true),
                array('id' => 'exit_course', 'dropdown' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $rows = CourseNav::editorRows($doc);
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = $row['id'];
        }
        $this->assertSame('files', $ids[1]);
        $sites = array_search('courses_widget', $ids, true);
        $exit = array_search('exit_course', $ids, true);
        $this->assertSame($sites + 1, $exit);
        $this->assertTrue($rows[$sites]['right']);
    }

    public function testCatalogOrder()
    {
        $ids = array();
        foreach ( CourseNav::catalog() as $row ) {
            $ids[] = $row['id'];
        }
        $this->assertSame(
            array(
                'lessons',
                'assignments',
                'announcements',
                'files',
                'pages',
                'discussions_widget',
                'discussions',
                'grades',
                'quiz1',
                'calendar_widget',
                'calendar',
                'map',
                'notifications',
                'notifications_widget',
                'profile',
                'analytics',
                'badges',
                'courses_widget',
                'exit_course',
                'login',
                'logout',
            ),
            $ids
        );
        $this->assertNotContains('topics', $ids);
    }

    public function testEmptyItemsIsNotCoercedToDefault()
    {
        $doc = CourseNav::documentFromJson('{"items":[]}');
        $this->assertSame(array('items' => array()), $doc);
    }

    public function testMissingJsonUsesDefault()
    {
        $doc = CourseNav::documentFromJson(null);
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertSame(array('lessons', 'files', 'pages', 'quiz1', 'courses_widget', 'logout'), $ids);
        $this->assertTrue($doc['items'][0]['left']);
        $this->assertTrue($doc['items'][4]['right']);
        $this->assertTrue($doc['items'][5]['dropdown']);
    }

    public function testFromPostHonorsOrderAndFlags()
    {
        $doc = CourseNav::fromPost(array(
            'nav_order' => array('files', 'logout', 'lessons'),
            'nav' => array(
                'files' => array('left' => '1', 'instructor' => '1'),
                'logout' => array('dropdown' => '1'),
            ),
        ));
        $this->assertSame('files', $doc['items'][0]['id']);
        $this->assertTrue($doc['items'][0]['instructor']);
        $this->assertSame('logout', $doc['items'][1]['id']);
        $this->assertCount(2, $doc['items']);
        $this->assertArrayNotHasKey('home', $doc);
    }

    public function testFromPostStoresCustomHomeLabel()
    {
        $doc = CourseNav::fromPost(array(
            'home' => 'Dashboard',
            'nav_order' => array('logout'),
            'nav' => array(
                'logout' => array('dropdown' => '1'),
            ),
        ));
        $this->assertSame('Dashboard', $doc['home']);
        $this->assertSame('logout', $doc['items'][0]['id']);

        $rows = CourseNav::editorRows($doc);
        $this->assertSame('Dashboard', $rows[0]['label']);
        $this->assertTrue($rows[0]['custom']);
    }

    public function testCompileInjectsHomeAndAvatar()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $set = CourseNav::compile(CourseNav::defaultDocument(), 42);
        $this->assertSame('Home', $set->home->link);
        $this->assertStringContainsString('/courses/42/home', $set->home->href);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertSame(array('Lessons', 'Files', 'Pages', 'Quizzes'), $left);
        $this->assertNotFalse($set->right);
        $this->assertStringContainsString('<img', $set->right->menu[count($set->right->menu)-1]->link);
        $this->assertStringContainsString('gravatar.com/avatar', $set->right->menu[count($set->right->menu)-1]->link);
        $_SESSION['avatar'] = 'https://example.com/jane.jpg';
        $set = CourseNav::compile(CourseNav::defaultDocument(), 42);
        $this->assertStringContainsString('https://example.com/jane.jpg', $set->right->menu[count($set->right->menu)-1]->link);
        unset($_SESSION['avatar']);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $this->assertIsArray($dropdown);
        $labels = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertNotContains('Exit course', $labels);
        $this->assertContains('Logout', $labels);
        $this->assertTrue($this->rightMenuContains($set, 'tsugi-courses'));
        $this->assertNotContains('Settings', $labels);
    }

    public function testCompileExitCourseUsesHomePath()
    {
        global $CFG;
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $CFG->home_path = 'https://example.org/portal/';
        $doc = array(
            'items' => array(
                array('id' => 'exit_course', 'dropdown' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $this->assertSame('https://example.org/portal', $this->dropdownHref($set, 'Exit course'));
    }

    public function testCompileUsesCustomHomeLabel()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $doc = CourseNav::defaultDocument();
        $doc['home'] = 'Start here';
        $set = CourseNav::compile($doc, 42);
        $this->assertSame('Start here', $set->home->link);
        $this->assertStringContainsString('/courses/42/home', $set->home->href);
    }

    public function testHomeBrandHtmlEscapesAndOmitsIconWithoutMetadata()
    {
        $this->assertSame('Home', CourseNav::homeBrandHtml(0, 'Home'));
        $this->assertSame('Home', CourseNav::homeBrandHtml(42, 'Home'));
        $this->assertSame('&lt;b&gt;X&lt;/b&gt;', CourseNav::homeBrandHtml(0, '<b>X</b>'));
    }

    public function testCompileHidesInstructorOnlyFromStudents()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Pat';
        $doc = array(
            'items' => array(
                array('id' => 'files', 'left' => true, 'instructor' => true),
                array('id' => 'lessons', 'left' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertContains('Lessons', $left);
        $this->assertNotContains('Files', $left);

        $_SESSION['isinstructor'] = true;
        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertContains('Files', $left);
        $this->assertContains('Lessons', $left);
    }

    public function testCompileShowsLoginOnlyWhenLoggedOut()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $doc = array(
            'items' => array(
                array('id' => 'login', 'left' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );

        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertContains('Login', $left);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $labels = array();
        if ( is_array($dropdown) ) {
            foreach ( $dropdown as $entry ) {
                $labels[] = $entry->link;
            }
        }
        $this->assertNotContains('Logout', $labels);

        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Pat';
        if ( function_exists('_tsugiResetIdentitySnapshot') ) {
            _tsugiResetIdentitySnapshot();
        }
        $set = CourseNav::compile($doc, 42);
        $left = array();
        if ( $set->left ) {
            foreach ( $set->left->menu as $entry ) {
                $left[] = $entry->link;
            }
        }
        $this->assertNotContains('Login', $left);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $labels = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertContains('Logout', $labels);
    }

    public function testIsCourseMountedRequest()
    {
        $_SERVER['REQUEST_URI'] = '/announcements';
        $this->assertFalse(Courses::isCourseMountedRequest());
        $_SERVER['REQUEST_URI'] = '/courses';
        $this->assertFalse(Courses::isCourseMountedRequest());
        $_SERVER['REQUEST_URI'] = '/courses/42';
        $this->assertTrue(Courses::isCourseMountedRequest());
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/7/files?x=1';
        $this->assertTrue(Courses::isCourseMountedRequest());
        $this->assertSame(7, Courses::courseIdFromRequest());
    }

    public function testCourseHomeUrlStaysInCoursePath()
    {
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/36';
        $url = Courses::courseHomeUrl(36);
        $this->assertStringContainsString('/courses/36/home', $url);
        $this->assertStringNotContainsString('://example.com/home', $url);
    }
}
