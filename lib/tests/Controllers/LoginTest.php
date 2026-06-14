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
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
        
        // Save original session state
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
        global $CFG;
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
    
    /**
     * Test redirect callback logic with login_return in session
     */
    public function testRedirectCallbackWithLoginReturn() {
        global $CFG;
        
        $_SESSION['login_return'] = 'http://example.com/return';
        
        // Simulate the redirect callback function from Login::get() lines 33-44
        $redirect_callback = function($result) {
            global $CFG;
            if ( isset($_SESSION['login_return']) ) {
                $url = $_SESSION['login_return'];
                unset($_SESSION['login_return']);
                return $url;
            } else if ( $result->did_insert ) {
                return $CFG->wwwroot . '/profile.php';
            } else {
                return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            }
        };
        
        $result = new \stdClass();
        $result->did_insert = false;
        
        $redirect_url = $redirect_callback($result);
        
        $this->assertEquals('http://example.com/return', $redirect_url,
            'Redirect URL should be login_return from session');
        $this->assertFalse(isset($_SESSION['login_return']),
            'login_return should be unset after use');
    }
    
    /**
     * Test redirect callback logic with did_insert = true
     */
    public function testRedirectCallbackWithDidInsert() {
        global $CFG;
        
        unset($_SESSION['login_return']);
        
        $redirect_callback = function($result) {
            global $CFG;
            if ( isset($_SESSION['login_return']) ) {
                $url = $_SESSION['login_return'];
                unset($_SESSION['login_return']);
                return $url;
            } else if ( $result->did_insert ) {
                return $CFG->wwwroot . '/profile.php';
            } else {
                return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            }
        };
        
        $result = new \stdClass();
        $result->did_insert = true;
        
        $redirect_url = $redirect_callback($result);
        
        $this->assertEquals($CFG->wwwroot . '/profile.php', $redirect_url,
            'Redirect URL should be /profile.php when did_insert is true');
    }
    
    /**
     * Test redirect callback logic default case
     */
    public function testRedirectCallbackDefault() {
        global $CFG;
        
        unset($_SESSION['login_return']);
        
        $redirect_callback = function($result) {
            global $CFG;
            if ( isset($_SESSION['login_return']) ) {
                $url = $_SESSION['login_return'];
                unset($_SESSION['login_return']);
                return $url;
            } else if ( $result->did_insert ) {
                return $CFG->wwwroot . '/profile.php';
            } else {
                return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            }
        };
        
        $result = new \stdClass();
        $result->did_insert = false;
        
        $redirect_url = $redirect_callback($result);
        
        $this->assertEquals($CFG->apphome, $redirect_url,
            'Redirect URL should be apphome when no login_return and did_insert is false');
    }
    
    /**
     * Test redirect callback logic default case without apphome
     */
    public function testRedirectCallbackDefaultWithoutApphome() {
        global $CFG;
        
        unset($_SESSION['login_return']);
        $original_apphome = $CFG->apphome ?? null;
        unset($CFG->apphome);
        
        $redirect_callback = function($result) {
            global $CFG;
            if ( isset($_SESSION['login_return']) ) {
                $url = $_SESSION['login_return'];
                unset($_SESSION['login_return']);
                return $url;
            } else if ( $result->did_insert ) {
                return $CFG->wwwroot . '/profile.php';
            } else {
                return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            }
        };
        
        $result = new \stdClass();
        $result->did_insert = false;
        
        $redirect_url = $redirect_callback($result);
        
        $this->assertEquals($CFG->wwwroot, $redirect_url,
            'Redirect URL should be wwwroot when apphome is not set');
        
        // Restore apphome
        if ($original_apphome !== null) {
            $CFG->apphome = $original_apphome;
        }
    }
}
