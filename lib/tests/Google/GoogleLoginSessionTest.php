<?php

use \Tsugi\Controllers\Login;
use \Tsugi\Services\Login\GoogleLoginService;

class GoogleLoginSessionTest extends \PHPUnit\Framework\TestCase
{
    private $originalSession;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? array();
        $_SESSION = array();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
    }

    public function testLoginReadsRedirectThenResetsSessionToEmpty()
    {
        $_SESSION = array(
            'login_return' => 'https://local.dj4e.com/lessons',
            'manifest_id' => 290,
            'id' => 306,
            'context_id' => 9470,
            'admin' => 'yes',
            'GOOGLE_STATE' => 'abc',
            'lti' => array('manifest_id' => 290, 'user_id' => 306),
        );

        $loginReturn = Login::peekReturnUrl();
        GoogleLoginService::resetSessionToEmpty();

        $this->assertSame('https://local.dj4e.com/lessons', $loginReturn);
        $this->assertSame(array(), $_SESSION);
    }

    public function testResetSessionToEmptyDropsABlankRedirect()
    {
        $_SESSION = array(
            'login_return' => '',
            'manifest_id' => 291,
        );

        $loginReturn = Login::peekReturnUrl();
        GoogleLoginService::resetSessionToEmpty();

        $this->assertNull($loginReturn);
        $this->assertSame(array(), $_SESSION);
    }
}
