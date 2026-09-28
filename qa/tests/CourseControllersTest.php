<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\WebDriverBy;

/**
 * Instructor walk through course controllers that DemoCourseTest does not open:
 * Files (folder file counts), Announcements, Discussions, Grades, Assignments,
 * Calendar, the Settings delete confirmation, and Quiz1 sample/publish/view.
 */
final class CourseControllersTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testInstructorUsesCourseControllers(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];
        $title = $course['title'];

        $this->uploadFileAndSeeFolderCount($client, $home);
        $this->createAnnouncement($client, $home);
        $this->addDiscussion($client, $home, $title);
        $this->openGradeBook($client, $home, $title);
        $this->openAssignments($client, $home, $title);
        $this->openCalendar($client, $home);
        $this->confirmDeleteStaysDisabledUntilTyped($client, $home, $title);
        $this->createPublishAndViewSampleQuiz($client, $home);
        $this->captureScreenshot($client, 'course-controllers');
    }

    private function uploadFileAndSeeFolderCount(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/files');
        $this->waitForPageText($client, 'Create folder');
        $this->waitForPageText($client, '0 files');

        $folder = 'Panther Notes';
        $driver->findElement(WebDriverBy::id('folder_name'))->sendKeys($folder);
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create folder')]"))->click();
        $this->waitForPageText($client, 'Folder created');
        $driver->findElement(WebDriverBy::linkText($folder))->click();
        $this->waitForPageText($client, 'This folder is empty');

        $path = tempnam(sys_get_temp_dir(), 'panther-note');
        if ($path === false) {
            $this->fail('Could not create a temp file to upload.');
        }
        $upload = $path.'.txt';
        if (!rename($path, $upload)) {
            $upload = $path;
        }
        file_put_contents($upload, "Hello from the Panther files controller.\n");
        try {
            $driver->findElement(WebDriverBy::id('uploads'))->sendKeys($upload);
            $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Upload')]"))->click();
            $this->waitForPageText($client, 'File uploaded');
            $this->waitForPageText($client, basename($upload));
        } finally {
            @unlink($upload);
        }

        $driver->get($courseHome.'/files');
        $this->waitForPageText($client, $folder);
        $row = (string) $driver->executeScript(
            'var name = arguments[0];
             var row = Array.from(document.querySelectorAll("tbody tr")).find(function (tr) {
                 return (tr.innerText || "").indexOf(name) !== -1;
             });
             return row ? row.innerText : "";',
            [$folder]
        );
        $this->assertStringContainsString('1 file', $row, 'Folder row should count the uploaded file.');
    }

    private function createAnnouncement(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $title = 'Panther Announcement '.date('His');
        $driver->get($courseHome.'/announcements/add');
        $this->waitForPageText($client, 'Add New Announcement');
        $driver->findElement(WebDriverBy::id('title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('text'))->sendKeys('Posted from the announcements controller.');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create Announcement')]"))->click();
        $this->waitForPageText($client, 'Announcement created successfully');
        $this->waitForPageText($client, $title);

        $driver->get($courseHome.'/announcements');
        $this->waitForPageText($client, $title);
    }

    private function addDiscussion(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/discussions');
        $this->waitForPageText($client, 'Discussions:');
        $this->waitForPageText($client, $courseTitle);
        $this->waitForPageText($client, 'No discussions yet.');

        $title = 'Panther Discussion '.date('His');
        $driver->get($courseHome.'/discussions/add');
        $this->waitForPageText($client, 'Add discussion');
        $driver->findElement(WebDriverBy::id('discussion_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Add discussion')]"))->click();
        $this->waitForPageText($client, 'Discussion added.');
        $this->waitForPageText($client, $title);
    }

    private function openGradeBook(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $client->getWebDriver()->get($courseHome.'/grades');
        $this->waitForPageText($client, 'Grade Book');
        $this->waitForPageText($client, 'Class: '.$courseTitle);
    }

    private function openAssignments(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $client->getWebDriver()->get($courseHome.'/assignments');
        $this->waitForPageText($client, $courseTitle);
        $this->waitForPageText($client, 'Manage due dates');
    }

    private function openCalendar(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $client->getWebDriver()->get($courseHome.'/calendar');
        $this->waitForPageText($client, 'Assignment due dates');
    }

    private function confirmDeleteStaysDisabledUntilTyped(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/delete');
        $this->waitForPageText($client, 'Delete this course');
        $this->waitForPageText($client, 'Delete stays disabled until the domain, course title, and member count all match exactly.');

        $page = $client->getPageSource();
        $domain = $this->labeledValue($page, 'Type the domain name of this site');
        $members = $this->labeledValue($page, 'Type the number of members in this course');
        $this->assertSame($courseTitle, $this->labeledValue($page, 'Type the title of this course'));

        $submit = $driver->findElement(WebDriverBy::cssSelector('button.delete-submit'));
        $this->assertFalse($submit->isEnabled(), 'Delete course should start disabled.');

        $driver->findElement(WebDriverBy::id('delete_domain_input'))->sendKeys('not-this-site.example');
        $driver->findElement(WebDriverBy::id('delete_title_input'))->sendKeys($courseTitle);
        $driver->findElement(WebDriverBy::id('delete_members_input'))->sendKeys($members);
        usleep(300000);
        $this->assertFalse(
            $driver->findElement(WebDriverBy::cssSelector('button.delete-submit'))->isEnabled(),
            'A wrong domain should keep Delete course disabled.'
        );

        $domainField = $driver->findElement(WebDriverBy::id('delete_domain_input'));
        $domainField->clear();
        $domainField->sendKeys($domain);
        $this->waitUntil(
            function () use ($driver): bool {
                return $driver->findElement(WebDriverBy::cssSelector('button.delete-submit'))->isEnabled();
            },
            'Delete course stayed disabled after the domain, title, and member count matched.'
        );
    }

    private function createPublishAndViewSampleQuiz(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/quiz1');
        $this->waitForPageText($client, 'Create sample quiz (all question types)');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create sample quiz')]"))->click();
        $this->waitForPageText($client, 'QTI Export Test', 30);
        $this->waitForPageText($client, 'Sample quiz created.');

        $driver->findElement(WebDriverBy::xpath("//button[normalize-space()='Publish']"))->click();
        $this->waitForPageText($client, 'Quiz published.');

        $opened = (bool) $driver->executeScript(
            'var a = Array.from(document.querySelectorAll("a")).find(function (el) {
                return /\\/view$/.test(el.getAttribute("href") || "");
            });
            if (!a) { return false; }
            window.location.href = a.href;
            return true;'
        );
        $this->assertTrue($opened, 'Quiz list did not include a View link after publish.');
        $this->waitForPageText($client, 'Submit quiz');

        $printed = (bool) $driver->executeScript(
            'var a = document.querySelector("a[href*=\\"print=yes\\"]");
             if (!a) { return false; }
             window.location.href = a.href;
             return true;'
        );
        $this->assertTrue($printed, 'Quiz view did not include a Print link.');
        $this->waitForPageText($client, 'quiz1-printing');
        $this->waitForPageText($client, 'Name:');
    }

    private function labeledValue(string $page, string $prefix): string
    {
        $pattern = '/'.preg_quote($prefix, '/').' \(([^)]+)\)/';
        if (!preg_match($pattern, $page, $match)) {
            $this->fail('Could not read "'.$prefix.'" from the delete page.');
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function waitUntil(callable $ready, string $message, int $timeoutSeconds = 10): void
    {
        $deadline = microtime(true) + $timeoutSeconds;
        while (microtime(true) < $deadline) {
            if ($ready()) {
                return;
            }
            usleep(200000);
        }
        $this->fail($message);
    }
}
