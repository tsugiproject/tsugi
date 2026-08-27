<?php

require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

use \Tsugi\Controllers\Courses;
use \Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

class CoursesControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $mockLaunch;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';
        $CFG->dirroot = dirname(__DIR__, 3);

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
        }

        if (!function_exists('isLoggedIn')) {
            require_once dirname(__DIR__, 2) . '/include/lms_lib.php';
        }
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }

        $this->mockLaunch = new \stdClass();
        $this->mockLaunch->output = new \stdClass();
        $this->mockLaunch->output->buffer = true;

        $this->mockApp = new Application($this->mockLaunch);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
    }

    private function routeUris(): array
    {
        Courses::routes($this->mockApp);
        $uris = [];
        foreach ($this->mockApp->router->getRoutes() as $route) {
            $uris[] = $route['uri'];
        }
        return $uris;
    }

    public function testRoutesRegistersCorrectRoutes()
    {
        $uris = $this->routeUris();

        $this->assertContains('/courses/json', $uris);
        $this->assertContains('/courses', $uris);
        $this->assertContains('/courses/{id:\d+}', $uris);
        $this->assertContains('/courses/{id:\d+}/{rest:.*}', $uris);
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/courses', Courses::ROUTE, 'ROUTE constant should be /courses');
    }

    public function testIsGoogleLoginSessionTrue()
    {
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $this->assertTrue(Courses::isGoogleLoginSession());
    }

    public function testIsGoogleLoginSessionFalseWhenLtiPost()
    {
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['lti_post'] = array('context_id' => 'lms-course');
        $this->assertFalse(Courses::isGoogleLoginSession());
    }

    public function testIsGoogleLoginSessionFalseWhenOtherKey()
    {
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        $this->assertFalse(Courses::isGoogleLoginSession());
    }

    public function testIsGoogleLoginSessionFromLtiBlob()
    {
        $_SESSION['lti'] = array('key_key' => 'google.com');
        $this->assertTrue(Courses::isGoogleLoginSession());
    }

    public function testGateRequiresLogin()
    {
        $response = Courses::gateResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('logged in', $response->getContent());
    }

    public function testGateRefusesLtiLaunch()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $response = Courses::gateResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('LTI launch', $response->getContent());
    }

    public function testGateAllowsGoogleLogin()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertNull(Courses::gateResponse());
    }

    public function testEnsureActiveContextNoOpWhenSame()
    {
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertTrue(Courses::ensureActiveContext(42));
    }

    public function testWireLaunchConnectionSetsPdoxOnTsugiLaunch()
    {
        global $TSUGI_LAUNCH, $LAUNCH, $OUTPUT, $CONTEXT, $PDOX;
        $save = array($TSUGI_LAUNCH ?? null, $LAUNCH ?? null, $OUTPUT ?? null, $CONTEXT ?? null, $PDOX ?? null);
        $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        $LAUNCH = new \Tsugi\Core\Launch();
        $CONTEXT = new \Tsugi\Core\Context();
        $OUTPUT = new \Tsugi\UI\Output();
        $PDOX = new \stdClass();
        Courses::wireLaunchConnection();
        $this->assertSame($PDOX, $TSUGI_LAUNCH->pdox);
        $this->assertSame($TSUGI_LAUNCH, $LAUNCH);
        $this->assertSame($TSUGI_LAUNCH, $CONTEXT->launch);
        $this->assertSame($TSUGI_LAUNCH, $OUTPUT->launch);
        [$TSUGI_LAUNCH, $LAUNCH, $OUTPUT, $CONTEXT, $PDOX] = $save;
    }

    public function testToolPathPrefixOffByDefault()
    {
        global $CFG;
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertSame('', Courses::toolPathPrefix());
    }

    public function testToolPathPrefixWhenEnabled()
    {
        global $CFG;
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['email'] = 'you@example.com';
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $CFG->setExtension('courses_in_urls', true);
        $this->assertSame('/courses/42', Courses::toolPathPrefix());
    }

    public function testToolPathPrefixEmailAllowlist()
    {
        global $CFG;
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['email'] = 'you@example.com';
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $CFG->setExtension('courses_in_urls', array('other@example.com'));
        $this->assertSame('', Courses::toolPathPrefix());

        $CFG->setExtension('courses_in_urls', array('you@example.com'));
        $this->assertSame('/courses/42', Courses::toolPathPrefix());
    }

    public function testToolPathPrefixEmptyForLtiLaunch()
    {
        global $CFG;
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $CFG->setExtension('courses_in_urls', true);
        $this->assertSame('', Courses::toolPathPrefix());
    }

    public function testInnerRequestPathInfo()
    {
        $request = Request::create('/courses/42/announcements/manage', 'GET', array('x' => '1'));
        $inner = Courses::innerRequest($request, 'announcements/manage');
        $this->assertSame('/announcements/manage', $inner->getPathInfo());
        $this->assertSame('GET', $inner->getMethod());
        $this->assertSame('1', $inner->query->get('x'));
    }

    public function testIdentitySnapshotResetAfterContextChange()
    {
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 1;
        _tsugiResetIdentitySnapshot();
        $this->assertSame(1, currentContextId());

        $_SESSION['context_id'] = 99;
        $this->assertSame(1, currentContextId(), 'snapshot must stick until reset');

        _tsugiResetIdentitySnapshot();
        $this->assertSame(99, currentContextId());
    }
}
