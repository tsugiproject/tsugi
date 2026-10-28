<?php

use \Tsugi\Google\GoogleClassroom;
use \Tsugi\Services\Google\ClassroomQueue;

class ClassroomTokenTest extends \PHPUnit\Framework\TestCase
{
    public function testRefreshFailureIsNotStored()
    {
        $previous = json_encode(array(
            'access_token' => 'old-access',
            'refresh_token' => 'keep-me',
        ));
        $this->assertNull(GoogleClassroom::storedToken($previous, array(
            'error' => 'invalid_grant',
            'error_description' => 'Token has been expired or revoked.',
        )));
        $this->assertNull(GoogleClassroom::storedToken($previous, false));
    }

    public function testRefreshKeepsThePreviousRefreshToken()
    {
        $previous = json_encode(array(
            'access_token' => 'old-access',
            'refresh_token' => 'keep-me',
        ));
        $stored = GoogleClassroom::storedToken($previous, array(
            'access_token' => 'new-access',
            'expires_in' => 3600,
        ));
        $decoded = json_decode($stored, true);
        $this->assertSame('new-access', $decoded['access_token']);
        $this->assertSame('keep-me', $decoded['refresh_token']);
    }

    public function testATokenWithNoRefreshTokenIsRejected()
    {
        $this->assertNull(GoogleClassroom::storedToken(null, array(
            'access_token' => 'only-access',
        )));
    }

    public function testRefreshErrorTextOmitsTheToken()
    {
        $text = GoogleClassroom::refreshError(array(
            'error' => 'invalid_grant',
            'error_description' => 'revoked',
            'refresh_token' => 'secret-value',
        ));
        $this->assertSame('revoked', $text);
        $this->assertStringNotContainsString('secret-value', $text);
    }

    public function testScrubRemovesRefreshTokens()
    {
        $text = ClassroomQueue::scrub('{"refresh_token":"abc","access_token":"def"} Bearer xyz');
        $this->assertStringNotContainsString('abc', $text);
        $this->assertStringNotContainsString('def', $text);
        $this->assertStringNotContainsString('xyz', $text);
    }
}
