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
use Tsugi\Core\ReqScope;
use Tsugi\Services\CourseNav\CourseNav;

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
        \Tsugi\Core\ReqScope::resetIdentity();
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        \Tsugi\Core\ReqScope::resetIdentity();
    }

    public function testDefaultHasCoursesWidgetOnRightThenLogout()
    {
        $doc = CourseNav::defaultDocument();
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertSame(array('lessons', 'content', 'outline', 'files', 'pages', 'quiz1', 'courses_widget', 'logout'), $ids);
        $this->assertTrue($doc['items'][0]['left']);
        $this->assertTrue($doc['items'][1]['left']);
        $this->assertTrue($doc['items'][2]['left']);
        $this->assertTrue($doc['items'][3]['left']);
        $this->assertTrue($doc['items'][4]['left']);
        $this->assertTrue($doc['items'][5]['left']);
        $this->assertTrue($doc['items'][6]['right']);
        $this->assertArrayNotHasKey('dropdown', $doc['items'][6]);
        $this->assertTrue($doc['items'][7]['dropdown']);
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
        $_SESSION['oauth_consumer_key'] = 'google.com';
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
        $_SESSION['oauth_consumer_key'] = 'google.com';
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
     * @return list<string>
     */
    private function navLabels($set)
    {
        $labels = array();
        if ( $set->left !== false ) {
            foreach ( $set->left->menu as $entry ) {
                if ( is_string($entry->link) ) {
                    $labels[] = $entry->link;
                }
            }
        }
        if ( $set->right === false ) {
            return $labels;
        }
        $last = $set->right->menu[count($set->right->menu) - 1];
        if ( is_array($last->href) ) {
            foreach ( $last->href as $entry ) {
                if ( is_string($entry->link) ) {
                    $labels[] = $entry->link;
                }
            }
        }
        return $labels;
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
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $rows = CourseNav::editorRows(CourseNav::defaultDocument());
        $this->assertSame('logout', $rows[count($rows)-1]['id']);
        $this->assertSame('login', $rows[count($rows)-2]['id']);
        $this->assertSame('settings', $rows[count($rows)-3]['id']);
        $this->assertSame('student', $rows[count($rows)-4]['id']);
        $this->assertSame('Student View', $rows[count($rows)-4]['label']);
        $this->assertSame('roster', $rows[count($rows)-5]['id']);
        $this->assertSame('chrome', $rows[count($rows)-3]['kind']);
        $this->assertSame('chrome', $rows[count($rows)-4]['kind']);
        $this->assertStringContainsString('before Settings', $rows[count($rows)-4]['hint']);
        $this->assertTrue($rows[count($rows)-1]['dropdown']);
        $this->assertStringContainsString('not logged in', $rows[count($rows)-2]['hint']);
        $this->assertStringContainsString('logged in', $rows[count($rows)-1]['hint']);
        $this->assertSame('home', $rows[0]['id']);
        $this->assertSame('chrome', $rows[0]['kind']);
        $this->assertTrue($rows[0]['rename']);
        $this->assertFalse($rows[0]['custom']);
        $this->assertSame('lessons', $rows[1]['id']);
        $this->assertSame('Lessons', $rows[1]['label']);
        $this->assertSame('Card View', $rows[1]['settings_label']);
        $this->assertSame('content', $rows[2]['id']);
        $this->assertSame('Content', $rows[2]['label']);
        $this->assertSame('Content', $rows[2]['settings_label']);
        $this->assertSame('outline', $rows[3]['id']);
        $this->assertSame('Outline', $rows[3]['label']);
        $this->assertSame('Outline', $rows[3]['settings_label']);
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

    public function testEditorRowsPutsContentImmediatelyAfterLessons()
    {
        $doc = array(
            'items' => array(
                array('id' => 'files', 'left' => true),
                array('id' => 'lessons', 'left' => true),
                array('id' => 'content', 'right' => true),
            ),
        );
        $rows = CourseNav::editorRows($doc);
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = $row['id'];
        }
        $lessons = array_search('lessons', $ids, true);
        $content = array_search('content', $ids, true);
        $this->assertNotFalse($lessons);
        $this->assertSame($lessons + 1, $content);
        $this->assertSame('Lessons', $rows[$lessons]['label']);
        $this->assertSame('Card View', $rows[$lessons]['settings_label']);
        $this->assertSame('Content', $rows[$content]['label']);
        $this->assertSame('Content', $rows[$content]['settings_label']);
        $this->assertTrue($rows[$content]['right']);
        $outline = array_search('outline', $ids, true);
        $this->assertNotFalse($outline);
        $this->assertSame($content + 1, $outline);
        $this->assertSame('Outline', $rows[$outline]['label']);
        $this->assertSame('Outline', $rows[$outline]['settings_label']);
    }

    public function testStoredOutlineNavIdStaysOutline()
    {
        $doc = CourseNav::normalize(array(
            'items' => array(
                array('id' => 'outline', 'left' => true),
            ),
        ));
        $this->assertSame('outline', $doc['items'][0]['id']);
        $this->assertTrue($doc['items'][0]['left']);
    }

    public function testEditorRowsKeepsSitesWidgetBesideExitEvenWhenEnabled()
    {
        $_SESSION['oauth_consumer_key'] = 'google.com';
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
                'content',
                'outline',
                'assignments',
                'announcements',
                'files',
                'pages',
                'discussions_widget',
                'discussions',
                'grades',
                'badges',
                'quiz1',
                'calendar_widget',
                'calendar',
                'map',
                'notifications',
                'notifications_widget',
                'profile',
                'analytics',
                'courses_widget',
                'exit_course',
                'leave_course',
                'roster',
                'login',
                'logout',
            ),
            $ids
        );
        $this->assertNotContains('topics', $ids);
        $this->assertContains('badges', $ids);
        $this->assertTrue(CourseNav::catalogById()['roster']['instructor_locked']);
    }

    public function testStoredBadgesLinkIsKept()
    {
        $doc = CourseNav::normalize(array(
            'items' => array(
                array('id' => 'badges', 'left' => true),
                array('id' => 'lessons', 'left' => true),
            ),
        ));
        $ids = array();
        foreach ( $doc['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertContains('badges', $ids);
        $this->assertContains('lessons', $ids);
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
        $this->assertSame(array('lessons', 'content', 'outline', 'files', 'pages', 'quiz1', 'courses_widget', 'logout'), $ids);
        $this->assertTrue($doc['items'][0]['left']);
        $this->assertTrue($doc['items'][6]['right']);
        $this->assertTrue($doc['items'][7]['dropdown']);
    }

    public function testFromPostStoresCourseViewsInOnePlace()
    {
        $doc = CourseNav::fromPost(array(
            'nav_order' => array('course_views', 'files', 'logout'),
            'nav' => array(
                'course_views' => array('right' => '1', 'dropdown' => '1'),
                'files' => array('left' => '1'),
                'logout' => array('dropdown' => '1'),
            ),
            'view_default' => array(
                'lessons' => '1',
            ),
            'view_available' => array(
                'lessons' => '1',
                'outline' => '1',
            ),
            'view_test' => array(
                'content' => '1',
                'outline' => '1',
            ),
        ));
        $this->assertSame(array('lessons', 'content', 'outline', 'files', 'logout'), array_column($doc['items'], 'id'));
        $this->assertTrue($doc['items'][0]['right']);
        $this->assertTrue($doc['items'][0]['dropdown']);
        $this->assertTrue($doc['items'][0]['default']);
        $this->assertTrue($doc['items'][0]['available']);
        $this->assertArrayNotHasKey('instructor', $doc['items'][0]);
        $this->assertTrue($doc['items'][1]['test']);
        $this->assertArrayNotHasKey('default', $doc['items'][1]);
        $this->assertArrayNotHasKey('available', $doc['items'][1]);
        $this->assertTrue($doc['items'][2]['right']);
        $this->assertTrue($doc['items'][2]['available']);
        $this->assertTrue($doc['items'][2]['test']);
        $this->assertArrayNotHasKey('default', $doc['items'][2]);
    }

    public function testMissingDefaultUsesCardView()
    {
        $doc = CourseNav::fromPost(array(
            'nav_order' => array('course_views'),
            'nav' => array(
                'course_views' => array('left' => '1'),
            ),
            'view_available' => array(
                'content' => '1',
            ),
            'view_test' => array(
                'outline' => '1',
            ),
        ));
        $this->assertSame(array('lessons', 'content', 'outline'), array_column($doc['items'], 'id'));
        $this->assertTrue($doc['items'][0]['default']);
        $this->assertTrue($doc['items'][0]['left']);

        $shown = CourseNav::applyContentView(array(
            'items' => array(
                array('id' => 'content', 'left' => true, 'available' => true),
                array('id' => 'files', 'left' => true),
            ),
        ), '');
        $this->assertSame(array('lessons', 'files'), array_column($shown['items'], 'id'));
        $this->assertTrue($shown['items'][0]['default']);
    }

    public function testApplyContentViewKeepsTheChosenReading()
    {
        $doc = array(
            'items' => array(
                array('id' => 'lessons', 'left' => true),
                array('id' => 'content', 'left' => true),
                array('id' => 'outline', 'left' => true, 'instructor' => true),
                array('id' => 'files', 'left' => true),
            ),
        );
        $same = CourseNav::applyContentView($doc, '');
        $this->assertSame(array('lessons', 'files'), array_column($same['items'], 'id'));

        $picked = CourseNav::applyContentView($doc, 'content');
        $this->assertSame(array('content', 'files'), array_column($picked['items'], 'id'));
        $this->assertSame('Lessons', $picked['items'][0]['nav_label']);

        $missing = CourseNav::applyContentView($doc, 'outline');
        $this->assertSame(array('lessons', 'files'), array_column($missing['items'], 'id'));
    }

    public function testApplyContentViewShowsDefaultsUntilPicked()
    {
        $doc = array(
            'items' => array(
                array('id' => 'lessons', 'left' => true, 'default' => true, 'available' => true),
                array('id' => 'content', 'left' => true, 'available' => true),
                array('id' => 'outline', 'left' => true, 'instructor' => true, 'available' => true),
                array('id' => 'files', 'left' => true),
            ),
        );
        $shown = CourseNav::applyContentView($doc, '');
        $this->assertSame(array('lessons', 'files'), array_column($shown['items'], 'id'));

        $picked = CourseNav::applyContentView($doc, 'content');
        $this->assertSame(array('content', 'files'), array_column($picked['items'], 'id'));
        $this->assertSame('Lessons', $picked['items'][0]['nav_label']);
        $this->assertSame(array('lessons', 'content'), CourseNav::studentContentViews($doc));
    }

    public function testStudentContentViewsSkipsInstructorOnly()
    {
        $views = CourseNav::studentContentViews(array(
            'items' => array(
                array('id' => 'lessons', 'left' => true),
                array('id' => 'content', 'left' => true, 'instructor' => true),
                array('id' => 'outline', 'dropdown' => true),
            ),
        ));
        $this->assertSame(array('lessons', 'outline'), $views);
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

    public function testNonGoogleLoginHidesMapAndProfile()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $_SESSION['lti_post'] = array('oauth_nonce' => 'plus');
        $_SESSION['oauth_consumer_key'] = 'sakai.plus';
        $doc = array(
            'items' => array(
                array('id' => 'map', 'left' => true),
                array('id' => 'profile', 'dropdown' => true),
                array('id' => 'courses_widget', 'right' => true),
                array('id' => 'lessons', 'left' => true),
            ),
        );
        $ids = array();
        foreach ( CourseNav::editorRows($doc) as $row ) {
            $ids[] = $row['id'];
        }
        $this->assertNotContains('map', $ids);
        $this->assertNotContains('profile', $ids);
        $this->assertNotContains('courses_widget', $ids);

        $set = CourseNav::compile($doc, 42);
        $labels = $this->navLabels($set);
        $this->assertNotContains('Map', $labels);
        $this->assertNotContains('Profile', $labels);
        $this->assertFalse($this->rightMenuContains($set, 'tsugi-courses'));

        $kept = CourseNav::retainSiteAccountItems($doc, array(
            'items' => array(
                array('id' => 'lessons', 'left' => true),
            ),
        ));
        $keptIds = array();
        foreach ( $kept['items'] as $item ) {
            $keptIds[] = $item['id'];
        }
        $this->assertSame(
            array('map', 'profile', 'courses_widget', 'lessons'),
            $keptIds
        );
    }

    public function testRetainSiteAccountItemsKeepsHiddenPositions()
    {
        $_SESSION['lti_post'] = array('oauth_nonce' => 'plus');
        $_SESSION['oauth_consumer_key'] = 'sakai.plus';
        $previous = array(
            'items' => array(
                array('id' => 'map', 'left' => true),
                array('id' => 'lessons', 'left' => true),
                array('id' => 'profile', 'dropdown' => true),
                array('id' => 'files', 'left' => true),
            ),
        );
        $posted = array(
            'items' => array(
                array('id' => 'files', 'left' => true),
                array('id' => 'lessons', 'left' => true),
            ),
        );
        $kept = CourseNav::retainSiteAccountItems($previous, $posted);
        $ids = array();
        foreach ( $kept['items'] as $item ) {
            $ids[] = $item['id'];
        }
        $this->assertSame(array('map', 'files', 'profile', 'lessons'), $ids);
    }

    public function testGoogleLoginOffersMapAndProfile()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $doc = array(
            'items' => array(
                array('id' => 'map', 'left' => true),
                array('id' => 'profile', 'dropdown' => true),
                array('id' => 'courses_widget', 'right' => true),
            ),
        );
        $ids = array();
        foreach ( CourseNav::editorRows($doc) as $row ) {
            $ids[] = $row['id'];
        }
        $this->assertContains('map', $ids);
        $this->assertContains('profile', $ids);
        $this->assertContains('courses_widget', $ids);

        $set = CourseNav::compile($doc, 42);
        $labels = $this->navLabels($set);
        $this->assertContains('Map', $labels);
        $this->assertContains('Profile', $labels);
        $this->assertTrue($this->rightMenuContains($set, 'tsugi-courses'));
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
        $this->assertFalse($this->rightMenuContains($set, 'tsugi-courses'));
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

    public function testAnonymousLessonViewKeepsOnlyTheAppHomeLink()
    {
        global $CFG;
        $_SERVER['REQUEST_URI'] = '/courses/42/lessons';
        $CFG->apphome = 'https://local.dj4e.com/';
        $CFG->servicename = 'DJ4E';
        ReqScope::beginAnonymousLessonView(42);
        try {
            $doc = CourseNav::defaultDocument();
            $doc['home'] = 'DJ Free';
            $doc['items'][] = array('id' => 'exit_course', 'dropdown' => true);
            $doc['items'][] = array('id' => 'pages', 'left' => true);
            $set = CourseNav::compile($doc, 42);
            $this->assertSame('DJ4E', $set->home->link);
            $this->assertSame('https://local.dj4e.com', $set->home->href);
            $this->assertStringNotContainsString('/courses/42', $set->home->href);
            $this->assertFalse($set->left);
            $this->assertFalse($set->right);
        } finally {
            ReqScope::endAnonymousLessonView();
            ReqScope::reset();
        }
    }

    public function testAnonymousLessonViewReturnsToTheCatalogWhenItIsOn()
    {
        global $CFG;
        $_SERVER['REQUEST_URI'] = '/courses/42/lessons';
        $CFG->apphome = 'https://local.dj4e.com/';
        $CFG->wwwroot = 'https://local.dj4e.com/tsugi';
        $CFG->servicename = 'DJ4E';
        $CFG->show_course_catalog = true;
        ReqScope::beginAnonymousLessonView(42);
        try {
            $doc = CourseNav::defaultDocument();
            $doc['home'] = 'DJ Free';
            $set = CourseNav::compile($doc, 42);
            $this->assertSame('Catalog', $set->home->link);
            $this->assertSame('https://local.dj4e.com/tsugi/catalog', $set->home->href);
            $this->assertFalse($set->left);
            $this->assertFalse($set->right);
        } finally {
            ReqScope::endAnonymousLessonView();
            ReqScope::reset();
        }
    }

    public function testAnonymousPageViewReturnsToLessons()
    {
        global $CFG;
        $_SERVER['REQUEST_URI'] = '/courses/42/pages/syllabus';
        $CFG->apphome = 'https://local.dj4e.com/';
        $CFG->wwwroot = 'https://local.dj4e.com/tsugi';
        $CFG->servicename = 'DJ4E';
        $CFG->show_course_catalog = true;
        ReqScope::beginAnonymousLessonView(42);
        try {
            $set = CourseNav::compile(CourseNav::defaultDocument(), 42);
            $this->assertSame('Lessons', $set->home->link);
            $this->assertSame('https://local.dj4e.com/tsugi/courses/42/lessons', $set->home->href);
            $this->assertFalse($set->left);
            $this->assertFalse($set->right);
        } finally {
            ReqScope::endAnonymousLessonView();
            ReqScope::reset();
        }
    }

    public function testCompileLeaveCourseClosesTheWindow()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $doc = array(
            'items' => array(
                array('id' => 'leave_course', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $found = null;
        foreach ( $dropdown as $entry ) {
            if ( $entry->link === 'Leave course' ) {
                $found = $entry;
            }
        }
        $this->assertNotNull($found);
        $this->assertSame('#', $found->href);
        $this->assertStringContainsString('window.close()', $found->attr);
    }

    public function testMenuTextStaysWithTheDefaultView()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $user = new \stdClass();
        $user->id = 3;
        $scope = new \stdClass();
        $scope->user = $user;
        $current = new \ReflectionProperty(ReqScope::class, 'current');
        $current->setValue(null, $scope);
        \Tsugi\Core\Cache::setContext('context_user_settings', 42, array(
            'user_id' => 3,
            'note' => '',
            'settings' => array('view' => 'content'),
        ));
        $doc = array(
            'items' => array(
                array('id' => 'lessons', 'left' => true, 'default' => true, 'available' => true),
                array('id' => 'content', 'left' => true, 'available' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $this->assertSame('Lessons', $set->left->menu[0]->link);
        $this->assertStringContainsString('/content', $set->left->menu[0]->href);

        \Tsugi\Core\Cache::setContext('context_user_settings', 42, array(
            'user_id' => 3,
            'note' => '',
            'settings' => array('view' => 'outline'),
        ));
        $ref = new \ReflectionProperty(\Tsugi\Services\ContextUser\ContextUserSettings::class, 'cache');
        $ref->setValue(null, array());
        $doc['items'][0]['default'] = false;
        $doc['items'][] = array('id' => 'outline', 'left' => true, 'available' => true);
        $doc['items'][1]['default'] = true;
        $set = CourseNav::compile($doc, 42);
        $this->assertSame('Content', $set->left->menu[0]->link);
        $this->assertStringContainsString('/outline', $set->left->menu[0]->href);
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

    public function testInstructorTestViewsSitInTheAvatarMenu()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Pat';
        $_SESSION['isinstructor'] = true;
        $doc = array(
            'items' => array(
                array('id' => 'lessons', 'left' => true, 'default' => true, 'available' => true, 'test' => true),
                array('id' => 'content', 'left' => true, 'available' => true, 'test' => true),
                array('id' => 'outline', 'left' => true, 'test' => true),
                array('id' => 'logout', 'dropdown' => true),
            ),
        );
        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertSame(array('Lessons'), $left);
        $labels = array();
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertContains('Content (test)', $labels);
        $this->assertContains('Outline (test)', $labels);
        $this->assertNotContains('Lessons (test)', $labels);
        $this->assertSame('http://localhost/courses/42/content', $this->dropdownHref($set, 'Content (test)'));

        unset($_SESSION['isinstructor']);
        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertSame(array('Lessons'), $left);
        $this->assertNotContains('Content (test)', $this->navLabels($set));
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
        \Tsugi\Core\ReqScope::resetIdentity();
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

    public function testCompileShowsRosterOnlyWhereAnInstructorPlacedIt()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 1;
        $_SESSION['displayname'] = 'Jane';
        $_SESSION['isinstructor'] = true;
        $set = CourseNav::compile(CourseNav::defaultDocument(), 42);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $labels = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertNotContains('Roster', $labels);

        $doc = CourseNav::fromPost(array(
            'nav_order' => array('roster', 'logout'),
            'nav' => array(
                'roster' => array('dropdown' => '1', 'left' => '1'),
                'logout' => array('dropdown' => '1'),
            ),
        ));
        $roster = $doc['items'][0];
        $this->assertSame('roster', $roster['id']);
        $this->assertTrue($roster['instructor']);
        $this->assertTrue($roster['left']);
        $this->assertTrue($roster['dropdown']);

        $set = CourseNav::compile($doc, 42);
        $left = array();
        foreach ( $set->left->menu as $entry ) {
            $left[] = $entry->link;
        }
        $this->assertContains('Roster', $left);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $labels = array();
        $hrefs = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
            $hrefs[] = $entry->href;
        }
        $this->assertContains('Roster', $labels);
        $matched = false;
        foreach ( $hrefs as $href ) {
            if ( is_string($href) && str_contains($href, '/courses/42/roster') ) {
                $matched = true;
            }
        }
        $this->assertTrue($matched);

        $_SESSION['isinstructor'] = false;
        unset($_SESSION['instructor']);
        $set = CourseNav::compile($doc, 42);
        $left = array();
        if ( $set->left ) {
            foreach ( $set->left->menu as $entry ) {
                $left[] = $entry->link;
            }
        }
        $this->assertNotContains('Roster', $left);
        $dropdown = $set->right->menu[count($set->right->menu)-1]->href;
        $labels = array();
        foreach ( $dropdown as $entry ) {
            $labels[] = $entry->link;
        }
        $this->assertNotContains('Roster', $labels);
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

    public function testHomeLabelFromShortTitleUsesFirstSixCharacters()
    {
        $this->assertSame('DJ Fre', CourseNav::homeLabelFromShortTitle('DJ Free'));
        $this->assertSame('DJ4E', CourseNav::homeLabelFromShortTitle('  DJ4E  '));
        $this->assertSame('日本語コース', CourseNav::homeLabelFromShortTitle('日本語コース名'));
        $this->assertNull(CourseNav::homeLabelFromShortTitle(''));
        $this->assertNull(CourseNav::homeLabelFromShortTitle('   '));
        $this->assertNull(CourseNav::homeLabelFromShortTitle(null));
        $this->assertNull(CourseNav::homeLabelFromShortTitle('Home'));
    }
}
