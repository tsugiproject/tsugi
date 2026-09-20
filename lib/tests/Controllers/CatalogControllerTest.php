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

        $this->mockApp = new Application((object) array('output' => (object) array('buffer' => true)));
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
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
        $CFG->setExtension('show_course_catalog', true);
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
        $CFG->setExtension('show_course_catalog', true);
        $html = Catalog::coursesWidgetTag();
        $this->assertStringContainsString('catalog-url="http://localhost/tsugi/catalog"', $html);
    }
}
