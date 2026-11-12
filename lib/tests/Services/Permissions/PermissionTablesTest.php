<?php

require_once __DIR__ . "/../../../src/Services/Service.php";
require_once __DIR__ . "/../../../src/Services/Courses/CoursesService.php";
require_once __DIR__ . "/../../../src/Services/Permission.php";
require_once __DIR__ . "/../../../src/Services/Permissions/PermissionTables.php";

use Tsugi\Services\Permissions\PermissionTables;

class PermissionTablesTest extends \PHPUnit\Framework\TestCase
{
    public function testActiveUntilRevokedOrExpired()
    {
        $now = strtotime('2026-11-12 12:00:00');

        $this->assertTrue(PermissionTables::isActive(array(
            'revoked_at' => null,
            'expires_at' => null,
        ), $now));

        $this->assertFalse(PermissionTables::isActive(array(
            'revoked_at' => '2026-11-01 00:00:00',
            'expires_at' => null,
        ), $now));

        $this->assertFalse(PermissionTables::isActive(array(
            'revoked_at' => null,
            'expires_at' => '2026-11-01 00:00:00',
        ), $now));

        $this->assertTrue(PermissionTables::isActive(array(
            'revoked_at' => null,
            'expires_at' => '2026-12-01 00:00:00',
        ), $now));
    }

    public function testParseExpires()
    {
        $this->assertSame(array('at' => null), PermissionTables::parseExpires(''));
        $this->assertSame(
            array('at' => '2026-12-01 15:30:00'),
            PermissionTables::parseExpires('2026-12-01T15:30')
        );
        $bad = PermissionTables::parseExpires('tomorrow-ish');
        $this->assertArrayHasKey('error', $bad);
    }

    public function testUserLabelIncludesEmail()
    {
        $this->assertSame(
            'Jane Instructor (jane@example.com) (7)',
            PermissionTables::userLabel(array(
                'displayname' => 'Jane Instructor',
                'email' => 'jane@example.com',
                'user_id' => 7,
            ))
        );
        $this->assertSame(
            'jane@example.com (7)',
            PermissionTables::userLabel(array(
                'displayname' => '',
                'email' => 'jane@example.com',
                'user_id' => 7,
            ))
        );
        $this->assertSame(
            'Jane Instructor (7)',
            PermissionTables::userLabel(array(
                'displayname' => 'Jane Instructor',
                'email' => '',
                'user_id' => 7,
            ))
        );
    }

    public function testAdminEmails()
    {
        global $CFG;
        $saved = (isset($CFG) && is_object($CFG)) ? $CFG : null;
        $CFG = new \stdClass();

        $CFG->adminemails = array(' Admin@Example.com ', '', 'admin@example.com', 'not-an-email');
        $this->assertSame(array('admin@example.com'), PermissionTables::adminEmails());

        $CFG->adminemails = false;
        $this->assertSame(array(), PermissionTables::adminEmails());

        $CFG->adminemails = 'one@example.com';
        $this->assertSame(array('one@example.com'), PermissionTables::adminEmails());

        if ( $saved === null ) {
            unset($GLOBALS['CFG']);
        } else {
            $CFG = $saved;
        }
    }

    public function testApplicationNoticeIncludesTheRequest()
    {
        global $CFG;
        $saved = (isset($CFG) && is_object($CFG)) ? $CFG : null;
        $CFG = new \stdClass();
        $CFG->servicename = 'Tsugi Test';
        $CFG->wwwroot = 'https://example.test/tsugi';

        $notice = PermissionTables::applicationNotice(
            array(
                'application_id' => 9,
                'user_id' => 7,
                'permission' => \Tsugi\Services\Courses\CoursesService::COURSE_CREATE,
                'justification' => 'I teach Django.',
            ),
            array(
                'user_id' => 7,
                'displayname' => 'Jane Instructor',
                'email' => 'jane@example.com',
            )
        );

        $this->assertSame('Instructor application from Jane Instructor', $notice['subject']);
        $revised = PermissionTables::applicationNotice(
            array(
                'application_id' => 9,
                'user_id' => 7,
                'permission' => \Tsugi\Services\Courses\CoursesService::COURSE_CREATE,
                'justification' => 'It is the Django course.',
                'response' => 'Which course?',
            ),
            array(
                'user_id' => 7,
                'displayname' => 'Jane Instructor',
                'email' => 'jane@example.com',
            ),
            true
        );
        $this->assertSame('Instructor application resubmitted by Jane Instructor', $revised['subject']);
        $this->assertStringContainsString('Which course?', $revised['message']);
        $this->assertStringContainsString('It is the Django course.', $revised['message']);
        $this->assertStringContainsString('jane@example.com', $notice['message']);
        $this->assertStringContainsString('I teach Django.', $notice['message']);
        $this->assertStringContainsString('course.create', $notice['message']);
        $this->assertStringContainsString('https://example.test/tsugi/admin/permissions/', $notice['message']);
        $this->assertSame($notice['message'], $notice['text']);

        if ( $saved === null ) {
            unset($GLOBALS['CFG']);
        } else {
            $CFG = $saved;
        }
    }

    public function testDecisionNotice()
    {
        global $CFG;
        $saved = (isset($CFG) && is_object($CFG)) ? $CFG : null;
        $CFG = new \stdClass();
        $CFG->wwwroot = 'https://example.test/tsugi';
        $code = \Tsugi\Services\Courses\CoursesService::COURSE_CREATE;

        $approved = PermissionTables::decisionNotice(
            array(
                'permission' => $code,
                'response' => 'Welcome aboard.',
            ),
            PermissionTables::STATUS_APPROVED
        );
        $this->assertSame('Instructor application approved', $approved['title']);
        $this->assertStringContainsString('Create Courses', $approved['text']);
        $this->assertStringContainsString('Welcome aboard.', $approved['text']);
        $this->assertSame('https://example.test/tsugi/instructor/', $approved['url']);

        $revision = PermissionTables::decisionNotice(
            array(
                'permission' => $code,
                'response' => 'Which course is this for?',
            ),
            PermissionTables::STATUS_REVISION
        );
        $this->assertSame('Instructor application sent back for revisions', $revision['title']);
        $this->assertStringContainsString('Which course is this for?', $revision['text']);
        $this->assertSame('https://example.test/tsugi/instructor/apply', $revision['url']);

        $denied = PermissionTables::decisionNotice(
            array(
                'permission' => $code,
                'response' => '',
            ),
            PermissionTables::STATUS_DENIED
        );
        $this->assertSame('Instructor application not approved', $denied['title']);
        $this->assertStringContainsString('not approved', $denied['text']);
        $this->assertSame('https://example.test/tsugi/instructor/apply', $denied['url']);

        if ( $saved === null ) {
            unset($GLOBALS['CFG']);
        } else {
            $CFG = $saved;
        }
    }
}
