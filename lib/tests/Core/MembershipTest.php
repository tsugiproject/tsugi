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
     * isInstructor() must match isset($role) && $role != 0 used for User::$instructor in LTIX::buildLaunch.
     *
     * @dataProvider isInstructorProvider
     */
    public function testIsInstructorMatchesUserInstructorRule($assignRole, $roleValue, $expected) {
        $m = new Membership();
        if ( $assignRole ) {
            $m->role = $roleValue;
        }
        $ltiRole = $assignRole ? $roleValue : null;
        $userInstructor = isset($ltiRole) && $ltiRole != 0;
        $this->assertSame($expected, $userInstructor, 'Sanity: provider expectation matches User rule');
        $this->assertSame($expected, $m->isInstructor());
    }

    public static function isInstructorProvider() {
        return [
            'role not set (null property)' => [false, null, false],
            'learner zero' => [true, 0, false],
            'learner constant' => [true, LTIX::ROLE_LEARNER, false],
            'instructor' => [true, LTIX::ROLE_INSTRUCTOR, true],
            'administrator' => [true, LTIX::ROLE_ADMINISTRATOR, true],
            'non-zero int' => [true, 1, true],
        ];
    }

    public function testIdPreserved() {
        $m = new Membership();
        $m->id = 42;
        $this->assertSame(42, $m->id);
        $this->assertFalse($m->isInstructor());
    }
}
