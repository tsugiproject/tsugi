<?php

use Tsugi\Core\LTIX;
use Tsugi\Core\Membership;

class MembershipTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $m = new Membership();
        $this->assertInstanceOf(Membership::class, $m);
        $this->assertFalse($m->launch);
        $this->assertNull($m->id);
        $this->assertNull($m->role);
        $this->assertFalse($m->isInstructor(), 'No role in session row => not instructor');
    }

    /**
     * isInstructor() is true when role is set and >= ROLE_INSTRUCTOR.
     *
     * @dataProvider isInstructorProvider
     */
    public function testIsInstructor($assignRole, $roleValue, $expected) {
        $m = new Membership();
        if ( $assignRole ) {
            $m->role = $roleValue;
        }
        $expectedFromLtix = $assignRole && $roleValue >= LTIX::ROLE_INSTRUCTOR;
        $this->assertSame($expected, $expectedFromLtix, 'Sanity: provider matches >= ROLE_INSTRUCTOR');
        $this->assertSame($expected, $m->isInstructor());
    }

    public static function isInstructorProvider() {
        return [
            'role not set (null property)' => [false, null, false],
            'learner zero' => [true, 0, false],
            'learner constant' => [true, LTIX::ROLE_LEARNER, false],
            'below instructor' => [true, 500, false],
            'instructor' => [true, LTIX::ROLE_INSTRUCTOR, true],
            'administrator' => [true, LTIX::ROLE_ADMINISTRATOR, true],
            'non-zero but below instructor' => [true, 1, false],
        ];
    }

    public function testIdPreserved() {
        $m = new Membership();
        $m->id = 42;
        $this->assertSame(42, $m->id);
        $this->assertFalse($m->isInstructor());
    }

}
