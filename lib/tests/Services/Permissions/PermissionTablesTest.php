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
}
