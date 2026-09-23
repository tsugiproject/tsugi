<?php

require_once "src/Controllers/Catalog.php";
require_once "src/Controllers/Courses.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Core/ContextImages.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";
require_once "src/Util/U.php";

use \Tsugi\Controllers\Catalog;
use \Tsugi\Lumen\Application;

if ( ! function_exists('__') ) {
    function __($message) {
        return $message;
    }
}

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

    public function testMarkHomeEnrolledStarsSiteLoginCourse()
    {
        $_SESSION['id'] = 1;
        $_SESSION['oauth_consumer_key'] = 'google.com';
        $_SESSION['site_context_id'] = 9;
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
        $rows = array(
            array('catalog_id' => 1, 'context_id' => 9, 'enrolled' => false),
            array('catalog_id' => 2, 'context_id' => 12, 'enrolled' => false),
        );
        $out = Catalog::markHomeEnrolled($rows);
        $this->assertTrue($out[0]['enrolled']);
        $this->assertFalse($out[1]['enrolled']);
    }

    public function testPublicHrefSendsSiteHomeCourseToAppHome()
    {
        $row = array(
            'catalog_id' => 4,
            'context_id' => 9,
            'has_detail' => false,
            'new_window' => 1,
        );
        list($href, $blank) = Catalog::publicHref($row, 'http://localhost/tsugi/catalog', 'http://localhost/app', 9);
        $this->assertSame('http://localhost/app', $href);
        $this->assertFalse($blank);
    }

    public function testPublicHrefKeepsOtherCoursesOnCatalog()
    {
        $row = array('catalog_id' => 4, 'context_id' => 12, 'has_detail' => false);
        list($href, $blank) = Catalog::publicHref($row, 'http://localhost/tsugi/catalog', 'http://localhost/app', 9);
        $this->assertSame('http://localhost/tsugi/catalog/4', $href);
        $this->assertFalse($blank);
    }

    public function testPublicHrefSiteHomeWithDetailStaysOnCatalog()
    {
        $row = array('catalog_id' => 4, 'context_id' => 9, 'has_detail' => true);
        list($href, $blank) = Catalog::publicHref($row, 'http://localhost/tsugi/catalog', 'http://localhost/app', 9);
        $this->assertSame('http://localhost/tsugi/catalog/4', $href);
        $this->assertFalse($blank);
    }

    public function testEnterUrlSiteHomeIsAppHome()
    {
        $this->assertSame('http://localhost/app', Catalog::enterUrl(9, 'http://localhost/app', 9));
        $this->assertSame('', Catalog::enterUrl(0, 'http://localhost/app', 9));
        $other = Catalog::enterUrl(12, 'http://localhost/app', 9);
        $this->assertStringContainsString('/courses/12/home', $other);
    }

    public function testPartitionRowsSplitsEnrolledAndOther()
    {
        $rows = array(
            array('catalog_id' => 1, 'title' => 'Mine', 'enrolled' => true),
            array('catalog_id' => 2, 'title' => 'Other', 'enrolled' => false),
            array('catalog_id' => 3, 'title' => 'Also mine', 'enrolled' => 1),
            array('catalog_id' => 4, 'title' => 'No flag'),
        );
        list($enrolled, $other) = Catalog::partitionRows($rows);
        $this->assertSame(array(1, 3), array_column($enrolled, 'catalog_id'));
        $this->assertSame(array(2, 4), array_column($other, 'catalog_id'));
    }

    public function testPartitionRowsAllEnrolledOrAllOther()
    {
        list($enrolled, $other) = Catalog::partitionRows(array(
            array('catalog_id' => 1, 'enrolled' => true),
        ));
        $this->assertCount(1, $enrolled);
        $this->assertCount(0, $other);

        list($enrolled, $other) = Catalog::partitionRows(array(
            array('catalog_id' => 2),
        ));
        $this->assertCount(0, $enrolled);
        $this->assertCount(1, $other);
    }

    public function testListingTemplateTabsOnlyWhenBothListsHaveCourses()
    {
        $render = function(array $rows) {
            list($enrolled_rows, $other_rows) = Catalog::partitionRows($rows);
            ob_start();
            include dirname(__DIR__, 2) . '/src/Controllers/templates/Catalog/index.inc.php';
            return (string) ob_get_clean();
        };
        $enrolled = array(
            'catalog_id' => 1, 'title' => 'Mine', 'href' => '/catalog/1',
            'hero_url' => '', 'icon_url' => '', 'short_description' => '', 'enrolled' => true,
        );
        $other = array(
            'catalog_id' => 2, 'title' => 'Other', 'href' => '/catalog/2',
            'hero_url' => '', 'icon_url' => '', 'short_description' => '', 'enrolled' => false,
        );

        $both = $render(array($enrolled, $other));
        $this->assertStringContainsString('nav-tabs', $both);
        $this->assertStringContainsString('catalog-enrolled', $both);
        $this->assertStringContainsString('catalog-other', $both);
        $this->assertStringContainsString('Courses you are enrolled in', $both);
        $this->assertStringContainsString('Other courses', $both);

        $onlyMine = $render(array($enrolled));
        $this->assertStringNotContainsString('nav-tabs', $onlyMine);
        $this->assertStringContainsString('Courses you are enrolled in', $onlyMine);
        $this->assertStringNotContainsString('Other courses', $onlyMine);

        $onlyOther = $render(array($other));
        $this->assertStringNotContainsString('nav-tabs', $onlyOther);
        $this->assertStringNotContainsString('Courses you are enrolled in', $onlyOther);
        $this->assertStringContainsString('Other', $onlyOther);
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
