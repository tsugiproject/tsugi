<?php

require_once "src/Controllers/Login.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Controllers\Login;

class LoginTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    
    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
        
        $this->originalSession = $_SESSION ?? [];
        $_SESSION = [];
    }
    
    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
    }

    public function testLoginUrlUsesApphomeWhenSet() {
        $this->assertEquals('http://localhost/app/login', Login::loginUrl());
    }

    public function testLoginUrlUsesWwwrootWithoutApphome() {
        global $CFG;
        unset($CFG->apphome);
        $this->assertEquals('http://localhost/tsugi/login', Login::loginUrl());
    }

    public function testOauthRedirectUriUsesExplicitRedirect() {
        global $CFG;
        $CFG->google_login_redirect = 'https://local.py4e.com/login';
        $this->assertEquals('https://local.py4e.com/login', Login::oauthRedirectUri());
    }

    public function testOauthRedirectUriUsesLoginUrlWhenGoogleLoginNew() {
        global $CFG;
        unset($CFG->google_login_redirect);
        $CFG->google_login_new = true;
        $this->assertEquals('http://localhost/app/login', Login::oauthRedirectUri());
    }

    public function testOauthRedirectUriUsesLoginPhpWhenLegacy() {
        global $CFG;
        unset($CFG->google_login_redirect);
        unset($CFG->google_login_new);
        $this->assertEquals('http://localhost/tsugi/login.php', Login::oauthRedirectUri());
    }

    public function testSetReturnUrlAndTakeReturnUrl() {
        Login::setReturnUrl('http://example.com/return');
        $this->assertEquals('http://example.com/return', Login::peekReturnUrl());
        $this->assertEquals('http://example.com/return', Login::takeReturnUrl());
        $this->assertNull(Login::peekReturnUrl());
    }

    public function testDefaultHomeUrlUsesApphome() {
        $this->assertEquals('http://localhost/app', Login::defaultHomeUrl());
    }

    public function testDefaultHomeUrlUsesWwwrootWithoutApphome() {
        global $CFG;
        unset($CFG->apphome);
        $this->assertEquals('http://localhost/tsugi', Login::defaultHomeUrl());
    }

    public function testConfiguredReturnUrl() {
        global $CFG;
        $CFG->login_return_url = 'http://localhost/welcome';
        $this->assertEquals('http://localhost/welcome', Login::configuredReturnUrl());
    }

    public function testCancelUrlUsesSavedReturn() {
        Login::setReturnUrl('http://example.com/back');
        $this->assertEquals('http://example.com/back', Login::cancelUrl());
    }

    public function testCancelUrlFallsBackToHome() {
        $this->assertEquals('http://localhost/app', Login::cancelUrl());
    }

    public function testReturnAfterLoginUsesSessionReturn() {
        Login::setReturnUrl('http://example.com/return');
        $result = new \stdClass();
        $result->did_insert = false;
        $this->assertEquals('http://example.com/return', Login::returnAfterLogin($result));
        $this->assertNull(Login::peekReturnUrl());
    }

    public function testReturnAfterLoginUsesNewUserUrl() {
        $result = new \stdClass();
        $result->did_insert = true;
        $this->assertEquals('http://localhost/app/profile', Login::returnAfterLogin($result, 'http://localhost/app/profile'));
    }

    public function testReturnAfterLoginUsesConfiguredUrlForNewUser() {
        global $CFG;
        $CFG->login_return_url = 'http://localhost/welcome';
        $result = new \stdClass();
        $result->did_insert = true;
        $this->assertEquals('http://localhost/welcome', Login::returnAfterLogin($result));
    }

    public function testReturnAfterLoginDefaultHome() {
        $result = new \stdClass();
        $result->did_insert = false;
        $this->assertEquals('http://localhost/app', Login::returnAfterLogin($result));
    }

    public function testReturnAfterLoginNoFallbackHome() {
        $result = new \stdClass();
        $result->did_insert = false;
        $this->assertNull(Login::returnAfterLogin($result, null, false));
    }
}
