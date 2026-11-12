<?php

require_once __DIR__ . "/../../src/Services/Service.php";
require_once __DIR__ . "/../../src/Services/Courses/CoursesService.php";
require_once __DIR__ . "/../../src/Services/Permission.php";

use Tsugi\Services\Courses\CoursesService;
use Tsugi\Services\Permission;

class PermissionTest extends \PHPUnit\Framework\TestCase
{
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
}
