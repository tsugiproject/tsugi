<?php

require_once "src/Controllers/Files.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use Tsugi\Controllers\Files;
use Tsugi\Lumen\Application;
use Tsugi\Services\Files\FileRepository;

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
        $this->assertEquals(FileRepository::HREF_PREFIX, Files::ROUTE);
        $this->assertEquals('Student', FileRepository::STUDENT_FILES_FOLDER);
        $this->assertEquals('Public', FileRepository::PUBLIC_FOLDER);
        $this->assertEquals('Private', FileRepository::PRIVATE_FOLDER);
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

    public function testEnsureLinkRejectsInvalidContextWithoutLmsUtilGlobals()
    {
        $this->assertFalse(function_exists('lmsEnsureAnalyticsLink'));
        $this->assertFalse(FileRepository::ensureLink(0));
        $this->assertFalse(FileRepository::ensureLink(-3));
    }

    public function testLessonsFilePickerItem()
    {
        $sha = '8c2f4d0123456789abcdef0123456789abcdef0123456789abcdef0123456789';
        $item = FileRepository::lessonsFilePickerItem(array(
            'file_sha256' => $sha,
            'file_name' => 'week-one.pdf',
            'contenttype' => 'application/pdf',
        ), 'Student');
        $this->assertSame($sha, $item['sha256']);
        $this->assertSame('week-one.pdf', $item['filename']);
        $this->assertSame('week-one.pdf', $item['title']);
        $this->assertSame('application/pdf', $item['content_type']);
        $this->assertSame('Student', $item['folder']);
        $this->assertSame('Student/week-one.pdf', $item['path']);
        $this->assertSame('/files/Student/week-one.pdf', $item['href']);
    }
}
