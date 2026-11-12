<?php

require_once "src/Services/Service.php";
require_once "src/Services/Courses/CoursesService.php";

use Tsugi\Services\Courses\CoursesService;

class CoursesServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testPermissionsDefaultToEmpty()
    {
        $this->assertSame([], CoursesService::permissions());
    }
}
