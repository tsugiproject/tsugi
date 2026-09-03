<?php

require_once "src/Controllers/Files.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use \Tsugi\Controllers\Files;
use \Tsugi\Lumen\Application;

class FilesControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $mockLaunch;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
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
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/files', Files::ROUTE);
        $this->assertEquals('Student', Files::STUDENT_FILES_FOLDER);
        $this->assertEquals('Public', Files::PUBLIC_FOLDER);
        $this->assertEquals('Private', Files::PRIVATE_FOLDER);
    }

    public function testRoutesRegistersSha256Download()
    {
        Files::routes($this->mockApp);
        $uris = array();
        foreach ($this->mockApp->router->getRoutes() as $route) {
            $uris[] = $route['uri'];
        }

        $this->assertContains('/files', $uris);
        $this->assertContains('/files/json', $uris);
        $this->assertContains('/files/download/{sha256}', $uris);
        $this->assertContains('/files/upload', $uris);
        $this->assertContains('/files/mkdir', $uris);
        $this->assertNotContains('/files/download/{id}', $uris);
    }
}
