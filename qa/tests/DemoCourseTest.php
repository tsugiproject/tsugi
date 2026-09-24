<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

final class DemoCourseTest extends TsugiPantherTestCase
{
    private string $lastWatchedUrl = '';

    public function testInstructorDemoLoginCreatesCourse(): void
    {
        $secret = getenv('TSUGI_DEMO_SECRET');
        $this->assertNotFalse($secret, 'TSUGI_DEMO_SECRET must be set.');
        $this->assertNotSame('', $secret, 'TSUGI_DEMO_SECRET must not be empty.');

        $adminPw = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($adminPw, 'TSUGI_ADMIN_PW must be set.');
        $this->assertNotSame('', $adminPw, 'TSUGI_ADMIN_PW must not be empty.');

        $client = $this->pantherClient();
        $driver = $client->getWebDriver();

        $this->loginInstructor($client, $secret);
        $this->saveProfileIfShown($client);

        $this->grantCreateCourses($client, $adminPw);

        $driver->get($this->uri('logout'));
        $this->loginInstructor($client, $secret);

        $title = 'Panther Demo ' . date('YmdHis');
        $driver->get($this->uri('courses/create'));
        $this->waitForPageText($client, 'Add course');
        $driver->findElement(WebDriverBy::id('course_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] button[type="submit"]'))->click();

        $this->waitForPageText($client, 'Course created.');
        $this->waitForPageText($client, $title);

        $courseHome = $this->courseHomeFromUrl($driver->getCurrentURL());
        $this->saveCourseNavigation($client, $courseHome);
        // Quiz1 create, lesson link, and take are paused while that tool is in flux.
        // Turn these back on with the newer quiz: createSampleQuiz, authorLessonQuizLink, takeQuizFromLessons.
        $this->createAndPublishPage($client, $courseHome);
        $this->placePageInLesson($client, $courseHome);
        $this->launchPageFromLessons($client, $courseHome);
        $this->captureScreenshot($client, 'demo-page-from-lessons');
    }

    private function loginInstructor(\Symfony\Component\Panther\Client $client, string $secret): void
    {
        $driver = $client->getWebDriver();
        $simulate = $this->uri('login/simulate');
        $driver->get($simulate);
        $this->waitForPageText($client, 'Demo login');

        $driver->executeScript(
            'document.querySelector("form[method=\'post\']").setAttribute("action", arguments[0]);',
            [$simulate]
        );
        $persona = new WebDriverSelect($driver->findElement(WebDriverBy::id('persona')));
        $persona->selectByValue('instructor-01');
        $driver->findElement(WebDriverBy::id('secret'))->sendKeys($secret);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] button[type="submit"]'))->click();
        $deadline = microtime(true) + 15;
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (!str_contains($page, 'name="persona"') && str_contains($page, 'Instructor 01')) {
                return;
            }
            usleep(200000);
        }
        $this->fail('Demo login did not leave the persona form.');
    }

    private function saveProfileIfShown(\Symfony\Component\Panther\Client $client): void
    {
        $driver = $client->getWebDriver();
        $page = $client->getPageSource();
        if (!str_contains($page, 'How much mail would you like us to send?')) {
            $href = $driver->executeScript(
                'var a = document.querySelector("a[href*=\'/profile\']"); return a ? a.href : "";'
            );
            if ($href === '') {
                return;
            }
            $driver->get($href);
            $this->waitForPageText($client, 'How much mail would you like us to send?');
        }

        $driver->executeScript(
            'document.querySelectorAll("button").forEach(function (b) { if ((b.innerText || "").trim() === "OK") { b.click(); } });'
        );
        $driver->findElement(WebDriverBy::cssSelector('button.btn-primary'))->click();
        $this->waitForPageText($client, 'Profile updated.');
    }

    private function grantCreateCourses(\Symfony\Component\Panther\Client $client, string $adminPw): void
    {
        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/'));
        $this->waitForPageText($client, 'Admin Unlock');
        $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($adminPw);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
        $this->waitForPageText($client, 'Administration Console');

        $driver->get($this->uri('admin/users/?search_text=' . rawurlencode('instructor01@notgoogle.com')));
        $this->waitForPageText($client, 'instructor01@notgoogle.com');
        $driver->findElement(WebDriverBy::cssSelector('tbody a'))->click();
        $this->waitForPageText($client, 'Create courses (0 or 1)');

        $driver->findElement(WebDriverBy::linkText('Edit'))->click();
        $this->waitForPageText($client, 'name="create_courses"');
        $field = $driver->findElement(WebDriverBy::id('create_courses'));
        $field->clear();
        $field->sendKeys('1');
        $driver->findElement(WebDriverBy::name('doUpdate'))->click();
        $this->waitForPageText($client, 'instructor01@notgoogle.com');
    }

    private function courseHomeFromUrl(string $url): string
    {
        if (!preg_match('#^(https?://.+?/courses/\d+)#', $url, $match)) {
            $this->fail('Course home URL was not found after create. URL: '.$url);
        }

        return $match[1];
    }

    private function saveCourseNavigation(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/navigation');
        $this->waitForPageText($client, 'Save navigation');

        foreach (array('assignments', 'discussions') as $tool) {
            $box = $driver->findElement(WebDriverBy::cssSelector('input[name="nav['.$tool.'][left]"]'));
            if (!$box->isSelected()) {
                $box->click();
            }
        }
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Save navigation')]"))->click();
        $this->waitForPageText($client, 'Navigation saved.');
        $this->assertTrue(
            $driver->findElement(WebDriverBy::cssSelector('input[name="nav[assignments][left]"]'))->isSelected()
        );
        $this->assertTrue(
            $driver->findElement(WebDriverBy::cssSelector('input[name="nav[discussions][left]"]'))->isSelected()
        );
    }

    private function createSampleQuiz(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/quiz1');
        $this->waitForPageText($client, 'Create sample quiz (all question types)');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create sample quiz')]"))->click();
        $this->waitForPageText($client, 'QTI Export Test');
        $this->waitForPageText($client, 'Sample quiz created.');
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
        $title->sendKeys('Panther Module');
        $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-module');
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
        $this->waitForPageText($client, 'Panther Module');
        $driver->findElement(WebDriverBy::partialLinkText('Panther Module'))->click();
        $this->waitForPageText($client, 'QTI Export Test');
        $driver->findElement(WebDriverBy::linkText('QTI Export Test'))->click();
        $this->waitForPageText($client, 'Submit quiz');
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Submit quiz')]"))->click();
        $this->waitForPageText($client, 'Auto-scored:');
    }

    private function createAndPublishPage(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/pages/add');
        $this->waitForPageText($client, 'Add New Page');
        $driver->findElement(WebDriverBy::id('title'))->sendKeys('Panther Page');
        $deadline = microtime(true) + 15;
        $editorReady = false;
        while (microtime(true) < $deadline) {
            $editorReady = (bool) $driver->executeScript('return !!(window.editor && window.editor.setData);');
            if ($editorReady) {
                break;
            }
            usleep(200000);
        }
        $this->assertTrue($editorReady, 'Page editor did not load.');
        $driver->executeScript(
            "editor.setData('<p>Hello from the Panther page.</p>');"
        );
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Create Page')]"))->click();
        $this->waitForPageText($client, 'Page created successfully');
        $this->waitForPageText($client, 'Draft');

        $driver->executeScript(
            "window.confirm = function () { return true; };
             document.querySelector('button[aria-label=\"Publish Panther Page\"]').click();"
        );
        $this->waitForPageText($client, 'Published');
    }

    private function placePageInLesson(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons/_author');
        $this->waitForPageText($client, 'Add module');
        if (!str_contains($client->getPageSource(), 'Panther Module')) {
            $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add module"]'))->click();
            $this->waitForPageText($client, 'Edit Module');
            $title = $driver->findElement(WebDriverBy::id('edit-module-title'));
            $title->clear();
            $title->sendKeys('Panther Module');
            $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-module');
            $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();
            $this->waitForPageText($client, 'Panther Module');
        }
        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add item"]'))->click();
        $this->waitForPageText($client, 'Add Item');
        $driver->executeScript(
            "document.getElementById('edit-item-type').value = 'html_page'; updateItemForm();"
        );
        $this->waitForPageText($client, 'Panther Page');
        $driver->executeScript(
            "var sel = document.getElementById('edit-page-id');
             var opt = Array.from(sel.options).find(function (o) { return o.text.indexOf('Panther Page') !== -1; });
             if (!opt) { throw new Error('page option missing'); }
             sel.value = opt.value;
             if (typeof onPagePicked === 'function') { onPagePicked(); }"
        );
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();
        $driver->executeScript('saveChanges()');
        $this->acceptAlertContaining($driver, 'saved');
    }

    private function launchPageFromLessons(\Symfony\Component\Panther\Client $client, string $courseHome): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons');
        $this->waitForPageText($client, 'Panther Module');
        $driver->findElement(WebDriverBy::partialLinkText('Panther Module'))->click();
        $this->waitForPageText($client, 'Panther Page');
        $driver->findElement(WebDriverBy::linkText('Panther Page'))->click();
        $this->waitForPageText($client, 'Hello from the Panther page.');
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

    private function waitForPageText(\Symfony\Component\Panther\Client $client, string $expected, int $timeoutSeconds = 15): void
    {
        $deadline = microtime(true) + $timeoutSeconds;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (str_contains($page, $expected)) {
                $this->pauseAfterPageChange($client);
                return;
            }
            usleep(200000);
        }

        $this->captureScreenshot($client, 'debug-demo-course');
        $excerpt = substr(preg_replace('/\s+/u', ' ', $page) ?? $page, 0, 400);
        $this->fail("Timed out waiting for '{$expected}'. Page excerpt: {$excerpt}");
    }

    private function pauseAfterPageChange(\Symfony\Component\Panther\Client $client): void
    {
        $url = $client->getCurrentURL();
        if ($url === $this->lastWatchedUrl) {
            return;
        }
        $this->lastWatchedUrl = $url;
        sleep(1);
    }
}
