<?php

use Tsugi\Services\Admin\AdminService;

class AdminServiceNaturalKeyTest extends \PHPUnit\Framework\TestCase
{
    public function testAsciiWithinTheLimitIsAccepted() {
        $this->assertNull(AdminService::naturalKeyProblem('subject_key', 'abc'));
        $this->assertNull(AdminService::naturalKeyProblem('subject_key', null));
        $this->assertNull(AdminService::naturalKeyProblem('subject_key', 'ab c'));
        $this->assertNull(AdminService::naturalKeyProblem('user_key', null));
        $this->assertNull(AdminService::naturalKeyProblem('service_key', str_repeat('a', 1024)));
        $this->assertNull(AdminService::naturalKeyProblem('profile_key', 'googlemail:ada@example.edu'));
    }

    public function testNullIsRefusedWhenTheColumnRequiresAValue() {
        $this->assertSame('profile_key has no value.', AdminService::naturalKeyProblem('profile_key', null));
        $this->assertSame('service_key has no value.', AdminService::naturalKeyProblem('service_key', null));
    }

    public function testACharacterOutsideAsciiIsRefused() {
        $this->assertSame(
            'user_key has a character outside ASCII.',
            AdminService::naturalKeyProblem('user_key', "caf\u{e9}")
        );
    }

    public function testAValuePastTheLimitIsRefused() {
        $this->assertSame(
            'subject_key is 256 characters and the limit is 255.',
            AdminService::naturalKeyProblem('subject_key', str_repeat('a', 256))
        );
        $this->assertNull(AdminService::naturalKeyProblem('subject_key', str_repeat('a', 255)));
        $this->assertSame(
            'user_key is 513 characters and the limit is 512.',
            AdminService::naturalKeyProblem('user_key', str_repeat('b', 513))
        );
        $this->assertSame(
            'service_key is 1025 characters and the limit is 1024.',
            AdminService::naturalKeyProblem('service_key', str_repeat('c', 1025))
        );
    }

    public function testANonStringIsRefused() {
        $this->assertSame('user_key is not text.', AdminService::naturalKeyProblem('user_key', 12));
    }

    public function testLeadingAndTrailingSpacesAreRefused() {
        $this->assertSame('subject_key has a leading space.', AdminService::naturalKeyProblem('subject_key', ' abc'));
        $this->assertSame('user_key has a trailing space.', AdminService::naturalKeyProblem('user_key', 'googlemail:ada@example.edu '));
        $this->assertSame(
            'service_key has a leading space and has a trailing space.',
            AdminService::naturalKeyProblem('service_key', ' https://lms.example/grades ')
        );
    }

    public function testNonPrintingCharactersAreRefused() {
        $this->assertSame(
            'subject_key contains a non-printing character.',
            AdminService::naturalKeyProblem('subject_key', "user-1\n")
        );
        $this->assertSame(
            'profile_key contains a non-printing character.',
            AdminService::naturalKeyProblem('profile_key', "googlemail:ada@example.edu\t")
        );
        $this->assertSame(
            'service_key contains a non-printing character.',
            AdminService::naturalKeyProblem('service_key', "https://lms.example/\x07grades")
        );
        $this->assertSame(
            'user_key has a trailing space and contains a non-printing character.',
            AdminService::naturalKeyProblem('user_key', "googlemail:ada@example.edu \n")
        );
    }
}
