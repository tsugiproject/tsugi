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
        $this->assertContains('/files/replace/{id}', $uris);
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

    public function testCautionPhraseIsExact()
    {
        $this->assertTrue(Files::cautionPhraseAccepted('I am sure'));
        $this->assertTrue(Files::cautionPhraseAccepted('  I am sure  '));
        $this->assertFalse(Files::cautionPhraseAccepted('i am sure'));
        $this->assertFalse(Files::cautionPhraseAccepted('yes'));
    }

    public function testCautionPageNamesTheKindAndRequiresThePhrase()
    {
        $html = Files::cautionPageHtml(
            'week.zip',
            'ZIP',
            '/files/Student/week.zip',
            '<input type="hidden" name="CSRF_TOKEN" value="tok">',
            ''
        );
        $this->assertStringContainsString('This ZIP file (week.zip) can contain dangerous information.', $html);
        $this->assertStringContainsString('Are you sure that you want to open or download this file?', $html);
        $this->assertStringContainsString('You can paste this text into an AI or a search engine to get a more detailed explanation.', $html);
        $this->assertStringContainsString('Type I am sure to continue.', $html);
        $this->assertStringContainsString('name="confirm_phrase"', $html);
        $this->assertStringContainsString('Open or download', $html);
    }

    public function testUploadedFileHrefsOpenInANewTab()
    {
        $this->assertTrue(FileRepository::isUploadedFileHref('/files/Student/notes.html'));
        $this->assertTrue(FileRepository::isUploadedFileHref('https://lms.example.com/courses/12/files/download/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
        $this->assertTrue(FileRepository::isUploadedFileHref('/files/lesson.zip'));
        $this->assertFalse(FileRepository::isUploadedFileHref('/files'));
        $this->assertFalse(FileRepository::isUploadedFileHref('/files/analytics'));
        $this->assertFalse(FileRepository::isUploadedFileHref('/files/replace/4'));
        $this->assertFalse(FileRepository::isUploadedFileHref('/pages/notes'));

        $html = '<p><a href="/files/Student/notes.html">Notes</a> <a href="/pages/home">Home</a></p>';
        $out = FileRepository::forceFileAnchorsNewTab($html);
        $this->assertStringContainsString('href="/files/Student/notes.html" target="_blank" rel="noopener noreferrer"', $out);
        $this->assertStringContainsString('<a href="/pages/home">Home</a>', $out);
    }
}
