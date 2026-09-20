<?php

require_once "src/Controllers/Catalog.php";
require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

use \Tsugi\Controllers\Catalog;
use \Tsugi\Lumen\Application;

class CatalogControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
        $CFG->dirroot = dirname(__DIR__, 3);

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require $autoloaderPath;
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

        $this->mockApp = new Application((object) array('output' => (object) array('buffer' => true)));
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

    public function testRouteConstant()
    {
        $this->assertSame('/catalog', Catalog::ROUTE);
    }

    public function testRoutesRegister()
    {
        Catalog::routes($this->mockApp);
        $uris = array();
        foreach ( $this->mockApp->router->getRoutes() as $route ) {
            $uris[] = $route['uri'];
        }
        $this->assertContains('/catalog', $uris);
        $this->assertContains('/catalog/{id:\d+}', $uris);
        $this->assertContains('/catalog/{id:\d+}/image/{kind}', $uris);
        $this->assertContains('/catalog/{id:\d+}/enrol', $uris);
    }

    public function testShowCourseCatalogOffByDefault()
    {
        $this->assertFalse(Catalog::showCourseCatalog());
        global $CFG;
        $CFG->show_course_catalog = true;
        $this->assertTrue(Catalog::showCourseCatalog());
    }

    public function testCatalogUrl()
    {
        $this->assertSame('http://localhost/tsugi/catalog', Catalog::catalogUrl());
    }

    public function testCoursesWidgetTagOmitsCatalogWhenOff()
    {
        $html = Catalog::coursesWidgetTag();
        $this->assertStringContainsString('tsugi-courses', $html);
        $this->assertStringNotContainsString('catalog-url', $html);
    }

    public function testCoursesWidgetTagIncludesCatalogWhenOn()
    {
        global $CFG;
        $CFG->show_course_catalog = true;
        $html = Catalog::coursesWidgetTag();
        $this->assertStringContainsString('catalog-url="http://localhost/tsugi/catalog"', $html);
    }

    public function testIsSiteHomeLinkMatchesGetHomeUrl()
    {
        global $CFG;
        $CFG->apphome = 'http://localhost/app';
        $this->assertTrue(Catalog::isSiteHomeLink('http://localhost/app'));
        $this->assertTrue(Catalog::isSiteHomeLink('http://localhost/app/'));
        $this->assertFalse(Catalog::isSiteHomeLink('http://localhost/tsugi'));
        $this->assertFalse(Catalog::isSiteHomeLink('https://other.example.com'));
    }

    public function testIsSiteHomeLinkHonorsHomePath()
    {
        global $CFG;
        $CFG->apphome = 'http://localhost/app';
        $CFG->home_path = 'https://example.com/home';
        $this->assertTrue(Catalog::isSiteHomeLink('https://example.com/home/'));
        $this->assertFalse(Catalog::isSiteHomeLink('http://localhost/app'));
    }

    public function testMarkHomeEnrolledOnlyForGoogleSession()
    {
        $rows = array(
            array('catalog_id' => 1, 'external_url' => 'http://localhost/app', 'enrolled' => false),
            array('catalog_id' => 2, 'external_url' => 'https://other.example.com', 'enrolled' => false),
            array('catalog_id' => 3, 'context_id' => 9, 'enrolled' => true),
        );
        $out = Catalog::markHomeEnrolled($rows);
        $this->assertFalse($out[0]['enrolled']);

        $_SESSION['id'] = 1;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $out = Catalog::markHomeEnrolled($rows);
        $this->assertTrue($out[0]['enrolled']);
        $this->assertFalse($out[1]['enrolled']);
        $this->assertTrue($out[2]['enrolled']);
    }

    public function testImageResponseCachesPublishedPubliclyAndUnpublishedPrivately()
    {
        $method = new \ReflectionMethod(Catalog::class, 'imageResponse');
        $method->setAccessible(true);
        $row = array('bytes' => 'jpeg-bytes', 'mime' => 'image/jpeg', 'updated_at' => '2026-01-01 00:00:00');

        $published = $method->invoke(null, $row, 'hero', 7, true);
        $this->assertSame(200, $published->getStatusCode());
        $this->assertStringContainsString('public', $published->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', $published->headers->get('Cache-Control'));

        $draft = $method->invoke(null, $row, 'hero', 7, false);
        $this->assertSame(200, $draft->getStatusCode());
        $this->assertStringContainsString('private', $draft->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $draft->headers->get('Cache-Control'));

        $missing = $method->invoke(null, null, 'hero', 7, false);
        $this->assertSame(404, $missing->getStatusCode());
        $this->assertStringContainsString('private', $missing->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $missing->headers->get('Cache-Control'));
    }
}
