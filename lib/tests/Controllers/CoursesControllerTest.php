<?php

require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Core/ContextImages.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

use \Tsugi\Controllers\Courses;
use \Tsugi\Core\Manifest;
use \Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $this->assertContains('/courses/create', $uris);
        $this->assertContains('/courses', $uris);
        $this->assertContains('/courses/{id:\d+}', $uris);
        $this->assertContains('/courses/{id:\d+}/image/{kind}', $uris);
        $this->assertContains('/courses/{id:\d+}/{rest:.*}', $uris);
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/courses', Courses::ROUTE, 'ROUTE constant should be /courses');
        $this->assertSame(5, Courses::FLYOUT_LIMIT);
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
        global $PDOX;
        $savePdox = $PDOX ?? null;
        $PDOX = new class {
            public function rowDie($sql, $params = array()) {
                return array('context_id' => 42, 'deleted' => 0, 'manifest_id' => 0);
            }
        };
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertTrue(Courses::ensureActiveContext(42));
        $PDOX = $savePdox;
    }

    public function testEnsureActiveContextRejectsDeletedCourse()
    {
        global $PDOX;
        $savePdox = $PDOX ?? null;
        $PDOX = new class {
            public $lastSql;

            public function rowDie($sql, $params = array()) {
                $this->lastSql = $sql;
                return false;
            }
        };
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $this->assertSame('Course not found.', Courses::ensureActiveContext(42));
        $this->assertStringContainsString('(deleted IS NULL OR deleted = 0)', $PDOX->lastSql);
        $PDOX = $savePdox;
    }

    public function testEnsureActiveContextHydratesManifestWhenSameContext()
    {
        global $PDOX, $TSUGI_LAUNCH;
        $savePdox = $PDOX ?? null;
        $saveLaunch = $TSUGI_LAUNCH ?? null;
        $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        $PDOX = new class {
            public function rowDie($sql, $params = array()) {
                return array('manifest_id' => 77);
            }
        };
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertTrue(Courses::ensureActiveContext(42));
        $this->assertSame(77, Manifest::activeId());
        $PDOX = $savePdox;
        $TSUGI_LAUNCH = $saveLaunch;
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

    public function testToolPathPrefixFollowsRequestNotConfig()
    {
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $_SERVER['REQUEST_URI'] = '/announcements';
        $this->assertSame('', Courses::toolPathPrefix());
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $this->assertSame('/courses/42', Courses::toolPathPrefix());
    }

    public function testShowCoursesWidgetOffByDefault()
    {
        $this->assertFalse(Courses::showCoursesWidget());
        global $CFG;
        $CFG->show_courses_widget = true;
        $this->assertTrue(Courses::showCoursesWidget());
    }

    public function testInnerRequestPathInfo()
    {
        $request = Request::create('/courses/42/announcements/manage', 'GET', array('x' => '1'));
        $inner = Courses::innerRequest($request, 'announcements/manage');
        $this->assertSame('/announcements/manage', $inner->getPathInfo());
        $this->assertSame('GET', $inner->getMethod());
        $this->assertSame('1', $inner->query->get('x'));
    }

    public function testInnerRequestDropsEmptyFileFields()
    {
        $empty = array(
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        );
        $request = Request::create(
            '/courses/42/settings/images',
            'POST',
            array('image_action' => 'save_hero'),
            array(),
            array('uploaded_file' => $empty)
        );
        $this->assertNull($request->files->get('uploaded_file'));
        $inner = Courses::innerRequest($request, 'settings/images');
        $this->assertSame('/settings/images', $inner->getPathInfo());
        $this->assertSame('POST', $inner->getMethod());
        $this->assertSame('save_hero', $inner->request->get('image_action'));
        $this->assertNull($inner->files->get('uploaded_file'));
    }

    public function testInnerRequestKeepsUploadedFile()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, 'jpeg-bytes');
        $file = new UploadedFile($tmp, 'photo.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);
        $request = Request::create(
            '/courses/42/settings/images',
            'POST',
            array('image_action' => 'save_icon'),
            array(),
            array('uploaded_file' => $file)
        );
        $inner = Courses::innerRequest($request, 'settings/images');
        $this->assertSame('/settings/images', $inner->getPathInfo());
        $kept = $inner->files->get('uploaded_file');
        $this->assertInstanceOf(UploadedFile::class, $kept);
        $this->assertSame('photo.jpg', $kept->getClientOriginalName());
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

    public function testCanCreateFalseWhenNotLoggedIn()
    {
        $this->assertFalse(Courses::canCreate());
    }

    public function testCanCreateTrueForSiteAdmin()
    {
        $_SESSION['id'] = 7;
        $_SESSION['admin'] = 'yes';
        $_SESSION['create_courses'] = 0;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertTrue(Courses::canCreate());
    }

    public function testCanCreateUsesUserFlagNotInstructorSession()
    {
        $_SESSION['id'] = 7;
        $_SESSION['instructor'] = true;
        $_SESSION['isinstructor'] = true;
        $_SESSION['create_courses'] = 0;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertFalse(Courses::canCreate());

        $_SESSION['create_courses'] = 1;
        $this->assertTrue(Courses::canCreate());
    }

    public function testCreateGateRefusesWithoutFlag()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['create_courses'] = 0;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $response = Courses::createGateResponse('/courses');
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCreateGateAllowsFlag()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['create_courses'] = 1;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertNull(Courses::createGateResponse('/courses'));
    }

    public function testTouchVisitedNoOpWhenNotLoggedIn()
    {
        $_SESSION = array();
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->expectNotToPerformAssertions();
        Courses::touchVisited(42);
        Courses::touchVisited(0);
    }

    public function testRestoreSiteLoginContextSkipsCourseMounted()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array('context_id' => 42, 'manifest_id' => 99);
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertFalse(Courses::restoreSiteLoginContext());
        $this->assertSame(99, Manifest::activeId());
    }

    public function testRestoreSiteLoginContextSkipsCartridgeUpload()
    {
        $_SERVER['REQUEST_URI'] = '/tsugi/lib/src/Controllers/util/upload/?context=42';
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array('context_id' => 42, 'manifest_id' => 99);
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertFalse(Courses::restoreSiteLoginContext());
        $this->assertSame(99, Manifest::activeId());
    }

    public function testRestoreSiteLoginContextClearsManifestOnSiteUrl()
    {
        $_SERVER['REQUEST_URI'] = '/announcements';
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 36;
        $_SESSION[Courses::SESSION_SITE_CONTEXT_ID] = 36;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array('context_id' => 36, 'manifest_id' => 99);
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertTrue(Courses::restoreSiteLoginContext());
        $this->assertSame(0, Manifest::activeId());
        $this->assertSame(36, currentContextId());
    }

    public function testRestoreSiteLoginContextSkipsLtiLaunch()
    {
        $_SERVER['REQUEST_URI'] = '/announcements';
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        $_SESSION['lti_post'] = array('user_id' => 'x');
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array('context_id' => 42, 'manifest_id' => 99);
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $this->assertFalse(Courses::restoreSiteLoginContext());
        $this->assertSame(99, Manifest::activeId());
    }

    public function testMembershipHrefSendsSiteCourseToAppHome()
    {
        $this->assertSame(
            'http://localhost/app',
            Courses::membershipHref(28, 'http://localhost/tsugi/courses', 28, 'http://localhost/app')
        );
        $this->assertSame(
            'http://localhost/tsugi/courses/12',
            Courses::membershipHref(12, 'http://localhost/tsugi/courses', 28, 'http://localhost/app')
        );
    }

    public function testSiteLoginRedirectTargetsAppHome()
    {
        $_SESSION['site_context_id'] = 28;
        $response = Courses::siteLoginRedirect(28);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertSame('http://localhost/app', $response->getTargetUrl());
        $this->assertNull(Courses::siteLoginRedirect(12));
    }

    public function testWithImageUrlsUsesMetadataNotBlobs()
    {
        $rows = Courses::withImageUrls(array(
            array(
                'context_id' => 7,
                'title' => 'Django',
                'hero_bytes' => 1200,
                'hero_updated_at' => '2026-09-18 01:02:03',
                'icon_bytes' => 0,
                'icon_updated_at' => null,
            ),
            array(
                'context_id' => 8,
                'title' => 'No images',
            ),
        ));
        $this->assertCount(2, $rows);
        $this->assertArrayNotHasKey('hero_bytes', $rows[0]);
        $this->assertArrayNotHasKey('icon_bytes', $rows[0]);
        $this->assertStringContainsString('/courses/7/image/hero', $rows[0]['hero_url']);
        $this->assertStringContainsString('v=', $rows[0]['hero_url']);
        $this->assertSame('', $rows[0]['icon_url']);
        $this->assertSame('', $rows[1]['hero_url']);
        $this->assertSame('', $rows[1]['icon_url']);
        $this->assertSame('Django', $rows[0]['title']);
    }

    public function testReleaseContextAfterDeleteClearsDeletedSiteCourse()
    {
        $_SESSION['id'] = 7;
        $_SESSION[Courses::SESSION_SITE_CONTEXT_ID] = 9;
        $_SESSION['context_id'] = 9;
        $_SESSION['context_title'] = 'Gone';
        $_SESSION['context_key'] = 'course:abc';
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array(
            'context_id' => 9,
            'context_title' => 'Gone',
            'manifest_id' => 99,
        );
        Courses::releaseContextAfterDelete(9);
        $this->assertArrayNotHasKey('context_id', $_SESSION);
        $this->assertArrayNotHasKey(Courses::SESSION_SITE_CONTEXT_ID, $_SESSION);
        $this->assertArrayNotHasKey('manifest_id', $_SESSION);
        $this->assertArrayNotHasKey('context_title', $_SESSION);
        $this->assertArrayNotHasKey('context_id', $_SESSION['lti']);
        $this->assertArrayNotHasKey('manifest_id', $_SESSION['lti']);
    }
}
