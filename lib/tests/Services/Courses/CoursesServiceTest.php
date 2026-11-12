<?php

require_once __DIR__ . "/../../../src/Services/Service.php";
require_once __DIR__ . "/../../../src/Services/Courses/CoursesService.php";

use Tsugi\Services\Courses\CoursesService;

class CoursesServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testInheritsNothingUntilItOverridesPermissions()
    {
        $inherited = new class extends \Tsugi\Services\Service {
        };
        $this->assertSame([], $inherited::permissions());

        $declared = new \ReflectionMethod(CoursesService::class, 'permissions');
        $this->assertSame(CoursesService::class, $declared->getDeclaringClass()->getName());
    }

    public function testCourseCreateIsRequestable()
    {
        $permissions = CoursesService::permissions();

        $this->assertSame(['course.create'], array_keys($permissions));
        $this->assertSame('Create Courses', $permissions['course.create']['title']);
        $this->assertSame('Create new courses in Tsugi', $permissions['course.create']['description']);
        $this->assertTrue($permissions['course.create']['requestable']);
    }
}
