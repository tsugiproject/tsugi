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

use Tsugi\Controllers\Courses;
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

    public function testDefaultHasExitCourseThenLogout()
    {
        $doc = CourseNav::defaultDocument();
        $this->assertSame('exit_course', $doc['items'][0]['id']);
        $this->assertTrue($doc['items'][0]['dropdown']);
        $this->assertSame('logout', $doc['items'][1]['id']);
        $this->assertTrue($doc['items'][1]['dropdown']);
    }

    public function testDefaultOmitsExitCourseWithoutAppHome()
    {
        global $CFG;
        $CFG->apphome = false;
        $doc = CourseNav::defaultDocument();
        $this->assertSame('logout', $doc['items'][0]['id']);
        $this->assertArrayNotHasKey('exit_course', CourseNav::catalogById());
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

    public function testCoursesWidgetIsCataloguedAsWidgetOnly()
    {
        $entry = CourseNav::catalogById()['courses_widget'];
        $this->assertSame('widget', $entry['kind']);
        $this->assertSame('Sites widget', $entry['label']);
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
        $this->assertSame('settings', $rows[count($rows)-2]['id']);
        $this->assertSame('chrome', $rows[count($rows)-2]['kind']);
        $this->assertSame('exit_course', $rows[count($rows)-3]['id']);
        $this->assertTrue($rows[count($rows)-3]['dropdown']);
        $this->assertTrue($rows[count($rows)-1]['dropdown']);
        $this->assertSame('home', $rows[0]['id']);
        $this->assertSame('chrome', $rows[0]['kind']);
        $this->assertSame('lessons', $rows[1]['id']);
    }

    public function testEmptyItemsIsNotCoercedToDefault()
    {
        $doc = CourseNav::documentFromJson('{"items":[]}');
        $this->assertSame(array('items' => array()), $doc);
    }

    public function testMissingJsonUsesDefault()
    {
        $doc = CourseNav::documentFromJson(null);
        $this->assertSame('exit_course', $doc['items'][0]['id']);
        $this->assertSame('logout', $doc['items'][1]['id']);
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
    }

    public function testCompileInjectsHomeAndAvatar()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $set = CourseNav::compile(CourseNav::defaultDocument(), 42);
        $this->assertSame('Home', $set->home->link);
        $this->assertStringContainsString('/courses/42/home', $set->home->href);
        $this->assertNotFalse($set->right);
        $this->assertSame('Jane', $set->right->menu[count($set->right->menu)-1]->link);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $this->assertIsArray($dropdown);
        $labels = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertContains('Exit course', $labels);
        $this->assertContains('Logout', $labels);
        $this->assertLessThan(
            array_search('Logout', $labels, true),
            array_search('Exit course', $labels, true)
        );
        $this->assertSame('http://localhost/app', $this->dropdownHref($set, 'Exit course'));
        $this->assertNotContains('Settings', $labels);
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
