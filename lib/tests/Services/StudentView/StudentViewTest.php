<?php

use Tsugi\Core\ReqScope;
use Tsugi\Core\User;
use Tsugi\Services\StudentView\StudentView;

if ( ! function_exists('__') ) {
    function __($message, $textdomain = false) {
        return $message;
    }
}

class StudentViewTest extends \PHPUnit\Framework\TestCase
{
    private $originalSession;
    private $originalCFG;
    private $originalServer;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $this->originalCFG = $CFG ?? null;
        $this->originalServer = $_SERVER;
        $_SESSION = array();
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost/tsugi');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost/tsugi';
        ReqScope::resetIdentity();
    }

    protected function tearDown(): void
    {
        global $CFG;
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        $CFG = $this->originalCFG;
        ReqScope::resetIdentity();
    }

    public function testRosterIsTheFourNamedStudents()
    {
        $this->assertSame(
            array(
                'nancy-drew' => 'Nancy Drew',
                'sue-bruno' => 'Sue Bruno',
                'jane-ford' => 'Jane Ford',
                'scott-land' => 'Scott Land',
            ),
            StudentView::roster()
        );
        $this->assertSame('Nancy Drew', StudentView::nameFor('nancy-drew'));
        $this->assertSame('', StudentView::nameFor('test-student'));
        $this->assertSame(
            array('ND', 'SB', 'JF', 'SL'),
            array(
                StudentView::initials('Nancy Drew'),
                StudentView::initials('Sue Bruno'),
                StudentView::initials('Jane Ford'),
                StudentView::initials('Scott Land'),
            )
        );
        $faces = array(
            StudentView::avatarDataUri('Nancy Drew', 'nancy-drew'),
            StudentView::avatarDataUri('Sue Bruno', 'sue-bruno'),
            StudentView::avatarDataUri('Jane Ford', 'jane-ford'),
            StudentView::avatarDataUri('Scott Land', 'scott-land'),
        );
        $this->assertCount(4, array_unique($faces));
        $this->assertStringContainsString('SB', rawurldecode($faces[1]));
        $this->assertStringContainsString('linearGradient', rawurldecode($faces[1]));
    }

    public function testUserKeyIsLocalToTheCourse()
    {
        $this->assertSame('student-view:42:nancy-drew', StudentView::userKey(42, 'nancy-drew'));
        $this->assertNotSame(StudentView::userKey(42, 'nancy-drew'), StudentView::userKey(7, 'nancy-drew'));
    }

    public function testSwitchAndRestoreRoundTrip()
    {
        $_SESSION['id'] = 9;
        $_SESSION['user_id'] = 9;
        $_SESSION['user_key'] = 'google-subject';
        $_SESSION['email'] = 'instructor@umich.edu';
        $_SESSION['displayname'] = 'Dr. Chuck';
        $_SESSION['avatar'] = 'https://example.com/chuck.jpg';
        $_SESSION['isinstructor'] = true;
        $_SESSION['admin'] = 'yes';
        $_SESSION[User::SESSION_CREATE_COURSES] = 1;
        $_SESSION['lti'] = array(
            'user_id' => 9,
            'user_key' => 'google-subject',
            'email' => 'instructor@umich.edu',
            'displayname' => 'Dr. Chuck',
            'context_id' => 42,
            'role' => 1000,
        );

        $switched = StudentView::switchTo(array(
            'user_id' => 55,
            'user_key' => 'student-view:42:nancy-drew',
            'email' => 'nancy-drew@student-view.test',
            'name' => 'Nancy Drew',
            'membership_id' => 80,
            'context_id' => 42,
            'slug' => 'nancy-drew',
        ));
        $this->assertTrue($switched);
        $this->assertTrue(StudentView::isActive());
        $this->assertSame('Nancy Drew', StudentView::activeName());
        $this->assertSame(55, $_SESSION['id']);
        $this->assertSame('Nancy Drew', $_SESSION['displayname']);
        $avatar = rawurldecode($_SESSION['avatar']);
        $this->assertStringStartsWith('data:image/svg+xml,', $_SESSION['avatar']);
        $this->assertStringContainsString('ND', $avatar);
        $this->assertStringContainsString('linearGradient', $avatar);
        $this->assertSame($_SESSION['avatar'], $_SESSION['lti']['image']);
        $this->assertArrayNotHasKey('admin', $_SESSION);
        $this->assertFalse($_SESSION['isinstructor']);
        $this->assertSame(0, $_SESSION[User::SESSION_CREATE_COURSES]);
        $this->assertSame(55, $_SESSION['lti']['user_id']);
        $this->assertSame(42, $_SESSION['lti']['context_id']);
        $this->assertSame(0, $_SESSION['lti']['role']);
        $this->assertSame(' class="tsugi-student-view"', StudentView::bodyAttribute());
        $this->assertStringNotContainsString('You are logged into student view', StudentView::barHtml());
        $this->assertStringContainsString('Logged in as', StudentView::barHtml());
        $this->assertStringContainsString('Nancy Drew', StudentView::barHtml());
        $this->assertStringContainsString('Leave Student View', StudentView::barHtml());
        $this->assertStringContainsString('/courses/42/student/leave', StudentView::barHtml());

        $again = StudentView::switchTo(array(
            'user_id' => 56,
            'user_key' => 'student-view:42:sue-bruno',
            'email' => 'sue-bruno@student-view.test',
            'name' => 'Sue Bruno',
            'membership_id' => 81,
            'context_id' => 42,
            'slug' => 'sue-bruno',
        ));
        $this->assertFalse($again);
        $this->assertSame('Nancy Drew', StudentView::activeName());
        $this->assertSame(55, $_SESSION['id']);

        $this->assertTrue(StudentView::restoreInstructor());
        $this->assertFalse(StudentView::isActive());
        $this->assertSame('', StudentView::bodyAttribute());
        $this->assertSame('', StudentView::barHtml());
        $this->assertSame(9, $_SESSION['id']);
        $this->assertSame('Dr. Chuck', $_SESSION['displayname']);
        $this->assertSame('https://example.com/chuck.jpg', $_SESSION['avatar']);
        $this->assertSame('yes', $_SESSION['admin']);
        $this->assertTrue($_SESSION['isinstructor']);
        $this->assertSame(1, $_SESSION[User::SESSION_CREATE_COURSES]);
        $this->assertSame(9, $_SESSION['lti']['user_id']);
        $this->assertSame(1000, $_SESSION['lti']['role']);
        $this->assertSame(42, $_SESSION['lti']['context_id']);
        $this->assertFalse(StudentView::restoreInstructor());
    }
}
