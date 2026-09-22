<?php

require_once "src/Controllers/Login.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/UI/DemoLogin.php";

$rootAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
if ( file_exists($rootAutoload) ) {
    require_once $rootAutoload;
}

use \Tsugi\Controllers\Login;
use \Tsugi\Lumen\Application;
use \Tsugi\UI\DemoLogin;
use Symfony\Component\HttpFoundation\Response;

if ( ! function_exists('__') ) {
    function __($message) {
        return $message;
    }
}

class LoginTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $originalPost;
    private $originalMethod;
    
    protected function setUp(): void
    {
        global $CFG, $OUTPUT;
        $this->originalCFG = $CFG;
        $this->originalPost = $_POST ?? [];
        $this->originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $OUTPUT = null;
        
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
        $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloaderPath)) {
            $CFG->loader = require $autoloaderPath;
        } else {
            $CFG->loader = new \stdClass();
        }
        
        $this->originalSession = $_SESSION ?? [];
        $_SESSION = [];
    }
    
    protected function tearDown(): void
    {
        global $CFG, $OUTPUT;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        $_POST = $this->originalPost;
        if ( $this->originalMethod === null ) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalMethod;
        }
        $OUTPUT = null;
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

    public function testSimulateRoutesRegistered() {
        $launch = new \stdClass();
        $launch->output = new \stdClass();
        $launch->output->buffer = true;
        $app = new Application($launch);
        Login::routes($app);
        $uris = array();
        $methods = array();
        foreach ( $app->router->getRoutes() as $route ) {
            $uris[] = $route['uri'];
            $methods[$route['uri']][] = $route['method'];
        }
        $this->assertContains('/login/simulate', $uris);
        $this->assertContains('GET', $methods['/login/simulate']);
        $this->assertContains('POST', $methods['/login/simulate']);
    }

    public function testSimulateForbiddenWhenDisabled() {
        $login = new Login();
        $response = $login->simulate();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $response = $login->simulate();
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSimulateForbiddenWhenSecretUnset() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = false;
        $login = new Login();
        $response = $login->simulate();
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSimulateFormWhenEnabled() {
        global $CFG, $OUTPUT;
        $CFG->demo_login = true;
        $CFG->demo_secret = 's3cret';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $OUTPUT = new class {
            public function header() {}
            public function bodyStart() {}
            public function topNav() {}
            public function flashMessages() {}
            public function footerStart() {}
            public function footerEnd() {}
        };
        $login = new Login();
        ob_start();
        $login->simulate();
        $html = ob_get_clean();
        $this->assertStringContainsString('Instructor 01', $html);
        $this->assertStringContainsString('Instructor 05', $html);
        $this->assertStringContainsString('Student 01', $html);
        $this->assertStringContainsString('Student 10', $html);
        $this->assertStringContainsString('name="secret"', $html);
        $this->assertStringContainsString('name="persona"', $html);
        $this->assertStringContainsString('name="CSRF_TOKEN"', $html);
    }

    public function testSimulateBadSecretStaysOnForm() {
        global $CFG, $OUTPUT;
        $CFG->demo_login = true;
        $CFG->demo_secret = 's3cret';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $OUTPUT = new class {
            public function header() {}
            public function bodyStart() {}
            public function topNav() {}
            public function flashMessages() {}
            public function footerStart() {}
            public function footerEnd() {}
        };
        $_SESSION['CSRF_TOKEN'] = 'tokentoken';
        $_POST['CSRF_TOKEN'] = 'tokentoken';
        $_POST['persona'] = 'instructor-01';
        $_POST['secret'] = 'wrong';
        $login = new Login();
        ob_start();
        $result = $login->simulate();
        $html = ob_get_clean();
        $this->assertNotInstanceOf(Response::class, $result);
        $this->assertStringContainsString('name="secret"', $html);
        $this->assertFalse(DemoLogin::secretMatches('wrong'));
    }
}
