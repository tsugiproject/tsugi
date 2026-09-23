<?php

require_once "src/Services/Courses/CourseDelete.php";

use Tsugi\Services\Courses\CourseDelete;

class CourseDeleteTest extends \PHPUnit\Framework\TestCase
{
    public function testDeleteRejectsMissingCourseId()
    {
        $this->expectException(\InvalidArgumentException::class);
        CourseDelete::delete(0);
    }
}
