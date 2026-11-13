<?php

final class GoogleServicesTest extends \PHPUnit\Framework\TestCase
{
    public function testClassroomResourcesAndModelsLoadFromTrimmedVendor(): void
    {
        $service = new \Google_Service_Classroom(new \Google_Client());
        $this->assertInstanceOf(
            \Google\Service\Classroom\Resource\CoursesCourseWork::class,
            $service->courses_courseWork
        );
        $this->assertInstanceOf(
            \Google\Service\Classroom\Resource\CoursesCourseWorkStudentSubmissions::class,
            $service->courses_courseWork_studentSubmissions
        );

        $courseWork = new \Google_Service_Classroom_CourseWork(array(
            'title' => 'Assignment',
            'materials' => array(array('link' => array('url' => 'https://example.com/assignment'))),
            'dueDate' => array('year' => 2026, 'month' => 11, 'day' => 12),
            'dueTime' => array('hours' => 17, 'minutes' => 0),
        ));
        $this->assertSame('Assignment', $courseWork->getTitle());
        $this->assertSame('https://example.com/assignment', $courseWork->getMaterials()[0]->getLink()->getUrl());
        $this->assertSame(2026, $courseWork->getDueDate()->getYear());
        $this->assertSame(17, $courseWork->getDueTime()->getHours());

        $submission = new \Google_Service_Classroom_StudentSubmission(array('assignedGrade' => 90));
        $this->assertEquals(90, $submission->getAssignedGrade());
        $material = new \Google_Service_Classroom_CourseWorkMaterial(array('title' => 'Reading'));
        $this->assertSame('Reading', $material->getTitle());
        $course = new \Google_Service_Classroom_Course(array('name' => 'Test course'));
        $this->assertSame('Test course', $course->getName());
    }
}
