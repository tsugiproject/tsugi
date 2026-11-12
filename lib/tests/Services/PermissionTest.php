<?php

require_once __DIR__ . "/../../src/Services/Service.php";
require_once __DIR__ . "/../../src/Services/Courses/CoursesService.php";
require_once __DIR__ . "/../../src/Services/Permission.php";

use Tsugi\Core\Cache;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Courses\CoursesService;
use Tsugi\Services\Permission;

class PermissionTest extends \PHPUnit\Framework\TestCase
{
    private $originalSession;

    protected function setUp(): void
    {
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();
        ReqScope::resetIdentity();
        Cache::clear('permissions');
    }

    protected function tearDown(): void
    {
        Cache::clear('permissions');
        $_SESSION = $this->originalSession;
        ReqScope::resetIdentity();
    }

    public function testAssembleReturnsCourseCreate()
    {
        $registry = Permission::assemble();

        $this->assertSame(CoursesService::permissions(), $registry);
        $this->assertSame($registry, Permission::registry());
    }

    public function testDuplicateCourseCreateFailsAssemble()
    {
        $duplicate = new class extends \Tsugi\Services\Service {
            public static function permissions(): array
            {
                return [
                    'course.create' => [
                        'title' => 'Create Courses',
                        'description' => 'Second declaration of course.create',
                        'requestable' => true,
                    ],
                ];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('course.create');
        Permission::assemble([
            CoursesService::class,
            $duplicate::class,
        ]);
    }

    public function testHasUsesTheSessionGrantSet()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 7, array(
            1 => array('course.create' => true),
        ), 300);

        $this->assertTrue(Permission::has('course.create'));
        $this->assertFalse(Permission::has('course.create', 2));
        $this->assertFalse(Permission::has('no.such'));
    }

    public function testCacheClearDropsTheSessionGrantSet()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 7, array(
            1 => array('course.create' => true),
        ), 300);

        $this->assertTrue(Permission::has('course.create'));
        Permission::cacheClear();
        $this->assertFalse(Permission::has('course.create'));
        $this->assertArrayNotHasKey('cache_permissions', $_SESSION);
    }

    public function testHasFreshReloadsBeforeAnswering()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 7, array(
            1 => array('course.create' => true),
        ), 300);

        $this->assertTrue(Permission::has('course.create'));
        $this->assertFalse(Permission::has('course.create', null, true));
        $this->assertArrayNotHasKey('cache_permissions', $_SESSION);
    }

    public function testCacheRefreshDropsAStaleGrantSet()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 7, array(
            1 => array('course.create' => true),
        ), 300);

        Permission::cacheRefresh();
        $this->assertFalse(Permission::has('course.create'));
        $this->assertArrayNotHasKey('cache_permissions', $_SESSION);
    }

    public function testHasMissesWhenTheSessionGrantSetIsStale()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 7, array(
            1 => array('course.create' => true),
        ), -1);

        $this->assertFalse(Permission::has('course.create'));
        $this->assertArrayNotHasKey('cache_permissions', $_SESSION);
    }

    public function testHasDoesNotKeepAnotherUsersGrants()
    {
        $_SESSION['id'] = 7;
        ReqScope::resetIdentity();
        Cache::set('permissions', 9, array(
            1 => array('course.create' => true),
        ), 300);

        $this->assertFalse(Permission::has('course.create'));
        $this->assertArrayNotHasKey('cache_permissions', $_SESSION);
    }
}
