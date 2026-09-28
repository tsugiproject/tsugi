<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;

/**
 * Instructor walk through course controllers that DemoCourseTest does not open:
 * Files (folder file counts), Announcements, Discussions, Grades, Assignments,
 * Calendar, the Settings delete confirmation, and Quiz1 sample/publish/view.
 * A later test replaces a file, opens badges, notifications, class grades,
 * student progress, export, and course images, then saves a due date.
 * Another opens home, the catalog, tool analytics, import, and the map guard,
 * then marks an announcement read.
 * Another confirms an HTML file before opening it, then scores the sample quiz.
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

    public function testInstructorLinksPublishedQuizFromLessons(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];

        $this->createAndPublishSampleQuiz($client, $home);
        $this->authorLessonQuizLink($client, $home);
        $this->takeQuizFromLessons($client, $home);
        $this->captureScreenshot($client, 'quiz-from-lessons');
    }

    public function testInstructorSetsDueDateAndOpensMoreTools(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];
        $title = $course['title'];

        $this->replaceAnUploadedFile($client, $home);
        $this->openBadgesNotificationsGradesAndSettings($client, $home, $title);
        $due = $this->addGradedLtiAndSaveDueDate($client, $home);
        $this->seeDueOnCalendar($client, $home, $due);
        $this->captureScreenshot($client, 'due-date-and-tools');
    }

    public function testInstructorOpensHomeCatalogAnalyticsAndMarksAnnouncementRead(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];
        $title = $course['title'];

        $this->openHomeCatalogAnalyticsImportAndMap($client, $home, $title);
        $this->markAnnouncementRead($client, $home);
        $this->captureScreenshot($client, 'home-catalog-announcement');
    }

    public function testInstructorConfirmsHtmlFileAndScoresQuiz(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];

        $this->confirmBeforeOpeningHtml($client, $home);
        $this->scoreSampleQuiz($client, $home);
        $this->captureScreenshot($client, 'html-confirm-and-quiz-score');
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

    private function createAndPublishSampleQuiz(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/quiz1');
        $this->waitForPageText($client, 'Create sample quiz (all question types)');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create sample quiz')]"))->click();
        $this->waitForPageText($client, 'QTI Export Test', 30);
        $this->waitForPageText($client, 'Sample quiz created.');
        $driver->findElement(WebDriverBy::xpath("//button[normalize-space()='Publish']"))->click();
        $this->waitForPageText($client, 'Quiz published.');
    }

    private function authorLessonQuizLink(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons/_author');
        $this->waitForPageText($client, 'Add module');

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add module"]'))->click();
        $this->waitForPageText($client, 'Edit Module');
        $title = $driver->findElement(WebDriverBy::id('edit-module-title'));
        $title->clear();
        $title->sendKeys('Panther Quiz Module');
        $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-quiz-module');
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add item"]'))->click();
        $this->waitForPageText($client, 'Add Item');
        $driver->executeScript(
            "document.getElementById('edit-item-type').value = 'quiz'; updateItemForm();"
        );
        $this->waitForPageText($client, 'QTI Export Test');
        $driver->executeScript(
            "var sel = document.getElementById('edit-quiz-id');
             var opt = Array.from(sel.options).find(function (o) { return o.text.indexOf('QTI Export Test') !== -1; });
             if (!opt) { throw new Error('sample quiz option missing'); }
             sel.value = opt.value;
             if (typeof onQuizPicked === 'function') { onQuizPicked(); }"
        );
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();

        $driver->executeScript('saveChanges()');
        $this->acceptAlertContaining($driver, 'saved');
    }

    private function takeQuizFromLessons(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons');
        $this->waitForPageText($client, 'Panther Quiz Module');
        $driver->findElement(WebDriverBy::partialLinkText('Panther Quiz Module'))->click();
        $this->waitForPageText($client, 'QTI Export Test');
        $driver->findElement(WebDriverBy::linkText('QTI Export Test'))->click();
        $this->waitForPageText($client, 'Submit quiz');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Submit quiz')]"))->click();
        $this->waitForPageText($client, 'pending manual grading');
    }

    private function acceptAlertContaining(\Facebook\WebDriver\Remote\RemoteWebDriver $driver, string $needle): void
    {
        $deadline = microtime(true) + 15;
        while (microtime(true) < $deadline) {
            try {
                $alert = $driver->switchTo()->alert();
                $text = $alert->getText();
                $alert->accept();
                $this->assertStringContainsString($needle, strtolower($text));
                return;
            } catch (NoSuchAlertException $exception) {
                usleep(200000);
            }
        }
        $this->fail('Timed out waiting for an alert containing '.$needle);
    }

    private function replaceAnUploadedFile(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/files');
        $this->waitForPageText($client, 'Upload');

        $dir = sys_get_temp_dir();
        $original = $dir.'/panther-replace-'.getmypid().'.txt';
        $replacement = $dir.'/panther-replaced-'.getmypid().'.txt';
        file_put_contents($original, "first file\n");
        file_put_contents($replacement, "replaced file\n");
        try {
            $driver->findElement(WebDriverBy::id('uploads'))->sendKeys($original);
            $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Upload')]"))->click();
            $this->waitForPageText($client, 'File uploaded');
            $name = basename($original);
            $this->waitForPageText($client, $name);

            $driver->findElement(WebDriverBy::linkText('Replace'))->click();
            $this->waitForPageText($client, 'Replacing '.$name);
            $driver->findElement(WebDriverBy::id('replacement'))->sendKeys($replacement);
            $driver->findElement(WebDriverBy::xpath("//button[normalize-space()='Replace']"))->click();
            $this->waitForPageText($client, 'Replaced '.$name);
        } finally {
            @unlink($original);
            @unlink($replacement);
        }
    }

    private function openBadgesNotificationsGradesAndSettings(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $driver = $client->getWebDriver();

        $driver->get($courseHome.'/badges');
        $this->waitForPageText($client, $courseTitle);
        $this->waitForPageText($client, 'Badges Awarded');

        $driver->get($courseHome.'/notifications');
        $this->waitForPageText($client, 'Test Notification');

        $driver->get($courseHome.'/grades/class');
        $this->waitForPageText($client, 'Grade Book');
        $this->waitForPageText($client, 'Class: '.$courseTitle);
        $this->waitForPageText($client, 'View My Grades');

        $driver->get($courseHome.'/assignments/student-progress');
        $this->waitForPageText($client, 'Student Progress');

        $driver->get($courseHome.'/settings/export');
        $this->waitForPageText($client, 'Choose the LMS that will use this cartridge:');
        $this->waitForPageText($client, $courseTitle);

        $driver->get($courseHome.'/settings/images');
        $this->waitForPageText($client, '16×9 course image');
        $this->waitForPageText($client, 'Square course icon');
    }

    /**
     * @return array{title: string, date: string}
     */
    private function addGradedLtiAndSaveDueDate(\Symfony\Component\Panther\Client $client, string $courseHome): array
    {
        $driver = $client->getWebDriver();
        $itemTitle = 'Panther Due Item';
        $driver->get($courseHome.'/lessons/_author');
        $this->waitForPageText($client, 'Add module');

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add module"]'))->click();
        $this->waitForPageText($client, 'Edit Module');
        $module = $driver->findElement(WebDriverBy::id('edit-module-title'));
        $module->clear();
        $module->sendKeys('Panther Due Module');
        $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-due-module');
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();
        $this->waitForPageText($client, 'Panther Due Module');

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add item"]'))->click();
        $this->waitForPageText($client, 'Add Item');
        $driver->executeScript(
            "document.getElementById('edit-item-type').value = 'lti'; updateItemForm();"
        );
        $this->waitForPageText($client, 'Resource Link ID');
        $driver->executeScript(
            "document.getElementById('edit-title').value = arguments[0];
             document.getElementById('edit-launch').value = 'https://example.com/launch';
             document.getElementById('edit-resource-link-id').value = 'panther-due-1';",
            [$itemTitle]
        );
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();
        $driver->executeScript('saveChanges()');
        $this->acceptAlertContaining($driver, 'saved');

        $driver->get($courseHome.'/assignments/manage-due-dates');
        $this->waitForPageText($client, 'Manage due dates');
        $this->waitForPageText($client, $itemTitle);
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Add missing link rows')]"))->click();
        $this->waitForPageText($client, 'Added link rows.');

        $dueDate = date('Y-m-d');
        $driver->executeScript(
            'var input = document.querySelector("input[name=\\"end[]\\"]");
             if (!input) { return false; }
             input.value = arguments[0];
             return true;',
            [$dueDate]
        );
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Save due dates')]"))->click();
        $this->waitForPageText($client, 'Saved due dates.');

        return ['title' => $itemTitle, 'date' => $dueDate];
    }

    /**
     * @param array{title: string, date: string} $due
     */
    private function seeDueOnCalendar(\Symfony\Component\Panther\Client $client, string $courseHome, array $due): void
    {
        $year = substr($due['date'], 0, 4);
        $month = (string) (int) substr($due['date'], 5, 2);
        $client->getWebDriver()->get($courseHome.'/calendar?year='.$year.'&month='.$month);
        $this->waitForPageText($client, 'Assignment due dates');
        $this->waitForPageText($client, $due['title']);
    }

    private function confirmBeforeOpeningHtml(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/files');
        $this->waitForPageText($client, 'Upload');

        $path = sys_get_temp_dir().'/panther-caution-'.getmypid().'.html';
        $marker = 'Panther html body';
        file_put_contents($path, '<!DOCTYPE html><html><body><p>'.$marker.'</p></body></html>');
        try {
            $driver->findElement(WebDriverBy::id('uploads'))->sendKeys($path);
            $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Upload')]"))->click();
            $this->waitForPageText($client, 'File uploaded');
            $name = basename($path);
            $this->waitForPageText($client, $name);

            $opened = (bool) $driver->executeScript(
                'var name = arguments[0];
                 var a = Array.from(document.querySelectorAll("a")).find(function (el) {
                     return (el.textContent || "").indexOf(name) !== -1;
                 });
                 if (!a) { return false; }
                 window.location.href = a.href;
                 return true;',
                [$name]
            );
            $this->assertTrue($opened, 'Uploaded HTML file had no link.');
            $this->waitForPageText($client, 'Confirm file');
            $this->waitForPageText($client, 'Type I am sure to continue.');

            $phrase = $driver->findElement(WebDriverBy::id('confirm_phrase'));
            $phrase->sendKeys('not sure');
            $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Open or download')]"))->click();
            $this->waitForPageText($client, 'class="error"');

            $phrase = $driver->findElement(WebDriverBy::id('confirm_phrase'));
            $phrase->clear();
            $phrase->sendKeys('I am sure');
            $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Open or download')]"))->click();
            $this->waitForPageText($client, $marker);
        } finally {
            @unlink($path);
        }
    }

    private function scoreSampleQuiz(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $this->createAndPublishSampleQuiz($client, $courseHome);

        $opened = (bool) $driver->executeScript(
            'var a = Array.from(document.querySelectorAll("a")).find(function (el) {
                return /\\/link\\/\\d+$/.test(el.getAttribute("href") || "");
            });
            if (!a) { return false; }
            window.location.href = a.href;
            return true;'
        );
        $this->assertTrue($opened, 'Published quiz did not offer a Take link.');
        $this->waitForPageText($client, 'Submit quiz');
        $this->assertStringNotContainsString('This preview does not record a grade.', $client->getPageSource());

        $driver->executeScript(
            'function block(needle) {
                return Array.from(document.querySelectorAll(".quiz1-q")).find(function (b) {
                    return (b.innerText || "").indexOf(needle) !== -1;
                });
            }
            function choose(needle, answers) {
                var b = block(needle);
                if (!b) { throw new Error("missing question " + needle); }
                answers.forEach(function (answer) {
                    var label = Array.from(b.querySelectorAll("label.quiz1-choice")).find(function (l) {
                        var text = (l.innerText || "").replace(/\\s+/g, " ").trim();
                        return text === answer || text.indexOf(answer + " ") === 0;
                    });
                    if (!label || !label.querySelector("input")) { throw new Error("missing answer " + answer); }
                    label.querySelector("input").click();
                });
            }
            function fill(needle, value, selector) {
                var b = block(needle);
                if (!b) { throw new Error("missing question " + needle); }
                var el = b.querySelector(selector);
                if (!el) { throw new Error("missing field " + needle); }
                el.value = value;
            }
            choose("Which protocol is used for the web", ["HTTP"]);
            choose("Select all HTTP methods", ["GET", "POST"]);
            choose("HTML is a programming language", ["False"]);
            fill("Explain REST", "Representational State Transfer uses HTTP.", "textarea");
            fill("The default HTTP port", "80", "input[type=text]");
            fill("whose name contains", "JavaScript", "input[type=text]");'
        );
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Submit quiz')]"))->click();
        $this->waitForPageText($client, '<strong>6</strong>');
        $this->waitForPageText($client, 'pending manual grading');
        $this->waitForPageText($client, '>Correct<');

        $driver->get($courseHome.'/grades');
        $this->waitForPageText($client, 'QTI Export Test');
        $this->waitForPageText($client, '54.5');
    }

    private function openHomeCatalogAnalyticsImportAndMap(\Symfony\Component\Panther\Client $client, string $courseHome, string $courseTitle): void
    {
        $driver = $client->getWebDriver();

        $driver->get($courseHome.'/home');
        $this->waitForPageText($client, $courseTitle);
        $this->waitForPageText($client, 'This home page feature is under construction.');

        $driver->get($courseHome.'/catalog');
        $this->waitForPageText($client, 'Course catalog');

        $driver->get($courseHome.'/files/analytics');
        $this->waitForPageText($client, 'Analytics: Files');

        $driver->get($courseHome.'/grades/analytics');
        $this->waitForPageText($client, 'Analytics: Grade Book');

        $driver->get($courseHome.'/announcements/analytics');
        $this->waitForPageText($client, 'Analytics: Announcements');

        $driver->get($courseHome.'/settings/import');
        $this->waitForPageText($client, 'Upload an IMS Common Cartridge');
        $this->waitForPageText($client, 'Cartridge file');

        $driver->get($courseHome.'/map');
        $this->waitForPageText($client, 'Map showing user locations');
    }

    private function markAnnouncementRead(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $title = 'Panther Read '.date('His');
        $driver->get($courseHome.'/announcements/add');
        $this->waitForPageText($client, 'Add New Announcement');
        $driver->findElement(WebDriverBy::id('title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('text'))->sendKeys('Dismiss me from the announcements controller.');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create Announcement')]"))->click();
        $this->waitForPageText($client, 'Announcement created successfully');

        $driver->get($courseHome.'/announcements');
        $this->waitForPageText($client, $title);
        $driver->executeScript('document.querySelector("button.mark-read-btn").click();');
        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $seen = $driver->findElements(WebDriverBy::id('show-dismissed-btn'));
            if (count($seen) > 0 && str_contains($seen[0]->getText(), '(1)')) {
                return;
            }
            usleep(200000);
        }
        $error = (string) $driver->executeScript(
            'var e = document.getElementById("announcements-error"); return e ? e.textContent : "";'
        );
        $this->fail('Mark as Read did not show previously seen announcements. '.$error);
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
