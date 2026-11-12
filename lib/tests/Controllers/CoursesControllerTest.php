<?php

require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Core/ContextImages.php";
require_once "src/Lumos/Application.php";
require_once "src/Lumos/Router.php";
require_once "src/Util/U.php";

$rootAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
if ( file_exists($rootAutoload) ) {
    require_once $rootAutoload;
}

use \Tsugi\Controllers\Courses;
use \Tsugi\Services\Courses\CoursesService;
use \Tsugi\Core\Manifest;
use \Tsugi\Core\ReqScope;
use \Tsugi\Lumos\Application;
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

        \Tsugi\Core\ReqScope::reset();

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
        \Tsugi\Core\ReqScope::reset();
    }

    /**
     * isLoggedIn() reads the provisioned request, not the session id.
     */
    private function provisionLogin(int $userId): void
    {
        $user = new \stdClass();
        $user->id = $userId;
        $scope = new \stdClass();
        $scope->user = $user;
        $current = new \ReflectionProperty(ReqScope::class, 'current');
        $current->setValue(null, $scope);
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
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        $response = Courses::gateResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('LTI launch', $response->getContent());
    }

    public function testGateAllowsGoogleLogin()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        $this->assertNull(Courses::gateResponse());
    }

    public function testGateAllowsPlusLaunchOnItsOwnCourse()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'sakai.example.edu';
        $_SESSION['lti_post'] = array('context_id' => 'lms-course');
        $_SESSION['lti'] = array(
            'context_id' => 42,
            'link_id' => 3,
            'plus_course' => 1,
            'plus_launch_link_id' => 9,
            'plus_context_launch' => 1,
        );
        $saved = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/42/home';
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        try {
            $this->assertNull(Courses::gateResponse());
        } finally {
            if ( $saved === null ) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $saved;
            }
        }
    }

    public function testGateRefusesPlusLaunchOnAnotherCourse()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'sakai.example.edu';
        $_SESSION['lti_post'] = array('context_id' => 'lms-course');
        $_SESSION['lti'] = array(
            'context_id' => 42,
            'link_id' => 9,
            'plus_course' => 1,
            'plus_launch_link_id' => 9,
            'plus_context_launch' => 1,
        );
        $saved = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/99';
        \Tsugi\Core\ReqScope::resetIdentity();
        try {
            $response = Courses::gateResponse();
            $this->assertNotNull($response);
            $this->assertSame(403, $response->getStatusCode());
        } finally {
            if ( $saved === null ) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $saved;
            }
        }
    }

    public function testGateRefusesPlusCourseWithoutPlusLaunch()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'sakai.example.edu';
        $_SESSION['lti_post'] = array('context_id' => 'lms-course');
        $_SESSION['lti'] = array(
            'context_id' => 42,
            'link_id' => 3,
            'plus_course' => 1,
            'plus_launch_link_id' => 9,
        );
        $saved = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/42';
        \Tsugi\Core\ReqScope::resetIdentity();
        try {
            $response = Courses::gateResponse();
            $this->assertNotNull($response);
            $this->assertSame(403, $response->getStatusCode());
        } finally {
            if ( $saved === null ) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $saved;
            }
        }
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
        \Tsugi\Core\ReqScope::resetIdentity();
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
        \Tsugi\Core\ReqScope::resetIdentity();
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
        \Tsugi\Core\ReqScope::resetIdentity();
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
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->assertSame(1, ReqScope::currentContextIdLegacy());

        $_SESSION['context_id'] = 99;
        $this->assertSame(1, ReqScope::currentContextIdLegacy(), 'snapshot must stick until reset');

        \Tsugi\Core\ReqScope::resetIdentity();
        $this->assertSame(99, ReqScope::currentContextIdLegacy());
    }

    public function testCanCreateFalseWhenNotLoggedIn()
    {
        $this->assertFalse(CoursesService::canCreate());
    }

    public function testCanCreateTrueForSiteAdmin()
    {
        $_SESSION['id'] = 7;
        $_SESSION['admin'] = 'yes';
        $_SESSION['create_courses'] = 0;
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->assertTrue(CoursesService::canCreate());
    }

    public function testCanCreateIgnoresInstructorRoleAndUserFlag()
    {
        $_SESSION['id'] = 7;
        $_SESSION['instructor'] = true;
        $_SESSION['isinstructor'] = true;
        $_SESSION['create_courses'] = 1;
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->assertFalse(CoursesService::canCreate());
    }

    public function testCanCreateUsesTheCachedGrant()
    {
        $_SESSION['id'] = 7;
        \Tsugi\Core\ReqScope::resetIdentity();
        \Tsugi\Core\Cache::set('permissions', 7, array(
            1 => array(CoursesService::COURSE_CREATE => true),
        ), 300);
        $this->assertTrue(CoursesService::canCreate());
    }

    public function testCreateGateRefreshesBeforeAllowing()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        \Tsugi\Core\Cache::set('permissions', 7, array(
            1 => array(CoursesService::COURSE_CREATE => true),
        ), 300);
        $response = Courses::createGateResponse('/courses');
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCreateGateRefusesWithoutGrant()
    {
        $_SESSION['id'] = 7;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['create_courses'] = 1;
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        $response = Courses::createGateResponse('/courses');
        $this->assertNotNull($response);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCreateGateAllowsSiteAdmin()
    {
        global $CFG;
        $CFG->allow_course_create = true;
        $_SESSION['id'] = 7;
        $_SESSION['admin'] = 'yes';
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['create_courses'] = 0;
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->provisionLogin(7);
        $this->assertNull(Courses::createGateResponse('/courses'));
    }

    public function testTouchVisitedNoOpWhenNotLoggedIn()
    {
        $_SESSION = array();
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->expectNotToPerformAssertions();
        CoursesService::touchVisited(42);
        CoursesService::touchVisited(0);
    }

    public function testRestoreSiteLoginContextSkipsCourseMounted()
    {
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SESSION['id'] = 7;
        $_SESSION['context_id'] = 42;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['manifest_id'] = 99;
        $_SESSION['lti'] = array('context_id' => 42, 'manifest_id' => 99);
        \Tsugi\Core\ReqScope::resetIdentity();
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
        \Tsugi\Core\ReqScope::resetIdentity();
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
        \Tsugi\Core\ReqScope::resetIdentity();
        $this->assertTrue(Courses::restoreSiteLoginContext());
        $this->assertSame(0, Manifest::activeId());
        $this->assertSame(36, ReqScope::currentContextIdLegacy());
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
        \Tsugi\Core\ReqScope::resetIdentity();
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

    public function testReleaseUnmountedCourseKeepsLaunchRow()
    {
        if ( ! defined('COOKIE_SESSION') ) {
            define('COOKIE_SESSION', true);
        }
        $previous = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/grades';
        $_SESSION['context_id'] = 10;
        $_SESSION['context_title'] = 'Tsugi Plus 01';
        $_SESSION['context_key'] = 'abc';
        $_SESSION['isinstructor'] = true;
        $_SESSION['manifest_id'] = 299;
        $_SESSION['lti_post'] = array('user_id' => 'x');
        $_SESSION['lti'] = array(
            'context_id' => 10,
            'context_title' => 'Tsugi Plus 01',
            'manifest_id' => 299,
            'user_id' => 5,
        );

        $this->assertTrue(Courses::releaseUnmountedCourse());
        $this->assertArrayNotHasKey('context_id', $_SESSION);
        $this->assertArrayNotHasKey('context_title', $_SESSION);
        $this->assertArrayNotHasKey('isinstructor', $_SESSION);
        $this->assertSame(10, $_SESSION['lti']['context_id']);
        $this->assertSame('Tsugi Plus 01', $_SESSION['lti']['context_title']);
        $this->assertSame(5, $_SESSION['lti']['user_id']);
        $this->assertSame(0, Manifest::activeId());
        $this->assertFalse(\Tsugi\Core\LTIX::launchContextApplies($_SESSION['lti']));

        $_SERVER['REQUEST_URI'] = '/courses/10/lessons';
        $this->assertTrue(\Tsugi\Core\LTIX::launchContextApplies($_SESSION['lti']));
        $this->assertFalse(Courses::releaseUnmountedCourse());

        $_SERVER['REQUEST_URI'] = '/grades';
        $_SESSION['oauth_consumer_key'] = 'google.com';
        unset($_SESSION['lti_post']);
        $_SESSION['context_id'] = 36;
        $_SESSION['context_title'] = 'Site';
        $_SESSION['lti']['context_id'] = 36;
        $_SESSION['lti']['key_key'] = 'google.com';
        $this->assertFalse(Courses::releaseUnmountedCourse());
        $this->assertSame(36, $_SESSION['context_id']);
        $this->assertSame('Site', $_SESSION['context_title']);

        $_SERVER['REQUEST_URI'] = '/grades';
        unset($_SESSION['oauth_consumer_key']);
        $_SESSION['lti_post'] = array('user_id' => 'x');
        $_SESSION['context_id'] = 10;
        $_SESSION['context_title'] = 'Tsugi Plus 01';
        $_SESSION['lti']['context_id'] = 10;
        $_SESSION['lti']['context_title'] = 'Tsugi Plus 01';
        $_SESSION['lti']['plus_context_launch'] = 1;
        unset($_SESSION['lti']['key_key']);
        $this->assertFalse(Courses::releaseUnmountedCourse());
        $this->assertSame(10, $_SESSION['context_id']);
        $this->assertSame('Tsugi Plus 01', $_SESSION['context_title']);
        $this->assertSame(10, $_SESSION['lti']['context_id']);

        if ( $previous === null ) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $previous;
        }
    }

    public function testLoggedOutCourseUrlGoesToLogin()
    {
        $previous = $_SERVER['REQUEST_URI'] ?? null;
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $previousPort = $_SERVER['SERVER_PORT'] ?? null;
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        \Tsugi\Core\ReqScope::resetIdentity();
        try {
            $request = Request::create('/courses/42/home', 'GET');
            $response = Courses::enter($this->mockApp, $request, 42);
            $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('http://localhost/app/login', $response->headers->get('Location'));
            $this->assertSame('http://localhost/courses/42/home', \Tsugi\Controllers\Login::peekReturnUrl());
        } finally {
            if ( $previous === null ) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previous;
            }
            if ( $previousHost === null ) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
            if ( $previousPort === null ) {
                unset($_SERVER['SERVER_PORT']);
            } else {
                $_SERVER['SERVER_PORT'] = $previousPort;
            }
        }
    }

    public function testPlusLaunchWithErrorReturnSkipsLogin()
    {
        $_SESSION['lti'] = array(
            'plus_context_launch' => 1,
            'context_id' => 42,
        );
        $_SESSION['lti_post'] = array(
            'launch_presentation_return_url' => 'https://lms.example/return',
            'launch_presentation_error_return_url' => 'https://lms.example/error',
        );
        \Tsugi\Core\ReqScope::resetIdentity();
        $request = Request::create('/courses/42/home', 'GET');
        $response = Courses::enter($this->mockApp, $request, 42);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertSame(
            'https://lms.example/error?lti_errormsg=Must%20be%20logged%20in',
            $response->headers->get('Location')
        );
        $this->assertNull(\Tsugi\Controllers\Login::peekReturnUrl());
    }

    public function testLtiLaunchWithoutErrorReturnUsesTheLaunchReturn()
    {
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        $_SESSION['lti_post'] = array(
            'launch_presentation_return_url' => 'https://lms.example/done',
        );
        \Tsugi\Core\ReqScope::resetIdentity();
        $request = Request::create('/courses/42/home', 'GET');
        $response = Courses::enter($this->mockApp, $request, 42);
        $this->assertSame(
            'https://lms.example/done?lti_errormsg=Must%20be%20logged%20in',
            $response->headers->get('Location')
        );
    }

    public function testLti13JwtErrorReturnSkipsLogin()
    {
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        $_SESSION['lti_post'] = array('id_token' => 'jwt');
        $body = new \stdClass();
        $body->{\Tsugi\Util\LTI13::PRESENTATION_CLAIM} = (object) array(
            'return_url' => 'https://lms.example/return',
            'error_return_url' => 'https://lms.example/jwt-error',
        );
        $_SESSION['tsugi_jwt'] = (object) array('body' => $body);
        \Tsugi\Core\ReqScope::resetIdentity();
        $request = Request::create('/courses/42/home', 'GET');
        $response = Courses::enter($this->mockApp, $request, 42);
        $this->assertSame(
            'https://lms.example/jwt-error?lti_errormsg=Must%20be%20logged%20in',
            $response->headers->get('Location')
        );
    }

    public function testStaleJwtDoesNotOverrideTheCurrentLti11Return()
    {
        $previous = $_SERVER['REQUEST_URI'] ?? null;
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $previousPort = $_SERVER['SERVER_PORT'] ?? null;
        $_SERVER['REQUEST_URI'] = '/courses/42/home';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SESSION['oauth_consumer_key'] = 'canvas.example.edu';
        $_SESSION['lti_post'] = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'launch_presentation_return_url' => 'https://lms.example/current',
        );
        $body = new \stdClass();
        $body->{\Tsugi\Util\LTI13::PRESENTATION_CLAIM} = (object) array(
            'error_return_url' => 'https://old-platform.example/error',
        );
        $_SESSION['tsugi_jwt'] = (object) array('body' => $body);
        \Tsugi\Core\ReqScope::resetIdentity();
        try {
            $request = Request::create('/courses/42/home', 'GET');
            $response = Courses::enter($this->mockApp, $request, 42);
            $this->assertSame(
                'https://lms.example/current?lti_errormsg=Must%20be%20logged%20in',
                $response->headers->get('Location')
            );
        } finally {
            if ( $previous === null ) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previous;
            }
            if ( $previousHost === null ) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
            if ( $previousPort === null ) {
                unset($_SERVER['SERVER_PORT']);
            } else {
                $_SERVER['SERVER_PORT'] = $previousPort;
            }
        }
    }

    public function testPostedReturnUrlDoesNotReplaceTheStoredLaunch()
    {
        $_SESSION['lti'] = array(
            'plus_context_launch' => 1,
            'context_id' => 42,
        );
        $_SESSION['lti_post'] = array(
            'launch_presentation_error_return_url' => 'https://lms.example/error?stay=1#back',
        );
        $_POST['launch_presentation_error_return_url'] = 'https://evil.example/phish';
        \Tsugi\Core\ReqScope::resetIdentity();
        try {
            $request = Request::create('/courses/42/home', 'POST');
            $response = Courses::enter($this->mockApp, $request, 42);
            $this->assertSame(
                'https://lms.example/error?stay=1&lti_errormsg=Must%20be%20logged%20in#back',
                $response->headers->get('Location')
            );
        } finally {
            unset($_POST['launch_presentation_error_return_url']);
        }
    }

    public function testMembershipFailureSendsTheUserHome()
    {
        if ( ! function_exists('__') ) {
            require_once __DIR__.'/../../include/setup_i18n.php';
        }
        $method = new \ReflectionMethod(Courses::class, 'switchFailedResponse');
        $response = $method->invoke(null, 'You are not a member of that course.');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/app', $response->headers->get('Location'));
        $this->assertSame(
            'You are not a member of that course.',
            $_SESSION[\Tsugi\Util\U::FLASH_ERROR] ?? null
        );
    }

    public function testMembershipFailureOnAPlusLaunchUsesTheErrorReturn()
    {
        $_SESSION['lti'] = array(
            'plus_context_launch' => 1,
            'context_id' => 42,
        );
        $_SESSION['lti_post'] = array(
            'launch_presentation_error_return_url' => 'https://lms.example/error',
        );
        $method = new \ReflectionMethod(Courses::class, 'switchFailedResponse');
        $response = $method->invoke(null, 'You are not a member of that course.');
        $this->assertSame(
            'https://lms.example/error?lti_errormsg=You%20are%20not%20a%20member%20of%20that%20course.',
            $response->headers->get('Location')
        );
        $this->assertArrayNotHasKey(\Tsugi\Util\U::FLASH_ERROR, $_SESSION);
    }

    public function testCourseSwitchFailureRendersTheMessage()
    {
        if ( ! function_exists('__') ) {
            require_once __DIR__.'/../../include/setup_i18n.php';
        }
        $method = new \ReflectionMethod(Courses::class, 'switchFailedResponse');
        $response = $method->invoke(null, 'Course not found.');
        $this->assertSame(400, $response->getStatusCode());
        $body = $response->getContent();
        $this->assertStringContainsString('Cannot open this course', $body);
        $this->assertStringContainsString('Course not found.', $body);
    }

    public function testAnonymousLessonRestIsTheReadOnlyOutline()
    {
        $this->assertTrue(Courses::isAnonymousLessonRest('lessons'));
        $this->assertTrue(Courses::isAnonymousLessonRest('/lessons/'));
        $this->assertTrue(Courses::isAnonymousLessonRest('lessons/week-1'));
        $this->assertTrue(Courses::isAnonymousLessonRest('content'));
        $this->assertTrue(Courses::isAnonymousLessonRest('content/week-1'));
        $this->assertFalse(Courses::isAnonymousLessonRest('content/week-1/extra'));
        $this->assertFalse(Courses::isAnonymousLessonRest('content_launch/week-1'));
        $this->assertTrue(Courses::isAnonymousLessonRest('outline'));
        $this->assertTrue(Courses::isAnonymousLessonRest('outline/week-1'));
        $this->assertFalse(Courses::isAnonymousLessonRest('outline/week-1/extra'));
        $this->assertFalse(Courses::isAnonymousLessonRest('outline_launch/week-1'));
        $this->assertFalse(Courses::isAnonymousLessonRest('lessons/_author'));
        $this->assertFalse(Courses::isAnonymousLessonRest('lessons/_author/export'));
        $this->assertFalse(Courses::isAnonymousLessonRest('lessons/week-1/extra'));
        $this->assertFalse(Courses::isAnonymousLessonRest('lessons_launch/week-1'));
        $this->assertFalse(Courses::isAnonymousLessonRest('quiz1'));
        $this->assertFalse(Courses::isAnonymousLessonRest('pages/3'));
        $this->assertFalse(Courses::isAnonymousLessonRest(''));
        $this->assertFalse(Courses::isAnonymousLessonRest('files/Student/notes.pdf'));
        $this->assertFalse(Courses::isAnonymousPageRest('files/Student/notes.pdf'));
        $this->assertFalse(Courses::isAnonymousLessonRest('files/download/'.str_repeat('ab', 32)));
        $this->assertFalse(Courses::isAnonymousPageRest('files/download/'.str_repeat('ab', 32)));
        $this->assertTrue(Courses::isAnonymousPageRest('pages'));
        $this->assertTrue(Courses::isAnonymousPageRest('pages/syllabus'));
        $this->assertFalse(Courses::isAnonymousPageRest('pages/add'));
        $this->assertFalse(Courses::isAnonymousPageRest('pages/edit/4'));
        $this->assertFalse(Courses::isAnonymousPageRest('pages/manage'));
        $this->assertFalse(Courses::isAnonymousPageRest('pages/json'));
        $this->assertFalse(Courses::isAnonymousPageRest('pages/analytics'));
        $this->assertFalse(Courses::isAnonymousPageRest('quiz1'));
    }

    public function testAnonymousPreviewDoesNotOpenFileUrls()
    {
        $method = new \ReflectionMethod(Courses::class, 'anonymousContentRequest');
        $sha = str_repeat('ab', 32);
        $paths = array(
            'files/Student/notes.pdf',
            'files/Public/syllabus.pdf',
            'files/download/'.$sha,
            'files/Imported/week.zip',
        );
        foreach ( $paths as $path ) {
            $get = Request::create('/'.$path, 'GET');
            $post = Request::create('/'.$path, 'POST');
            $this->assertFalse($method->invoke(null, $get, $path), $path);
            $this->assertFalse($method->invoke(null, $post, $path), $path);
        }
        $lesson = Request::create('/lessons', 'GET');
        $this->assertTrue($method->invoke(null, $lesson, 'lessons'));
    }
}
