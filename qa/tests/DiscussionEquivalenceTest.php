<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;

/**
 * A lesson discussion opened through the LTI launch (tool/tdiscus) and the
 * discussions controller share one link. A thread created on either side
 * shows up on the other.
 *
 * Docker config sets discussion_lti_launch so the lessons click is the tool.
 */
final class DiscussionEquivalenceTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testThreadsMatchBetweenLessonsLaunchAndController(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];

        $stamp = date('YmdHis');
        $discussionTitle = 'Panther Shared Discussion '.$stamp;
        $toolThread = 'Thread from tool '.$stamp;
        $controllerThread = 'Thread from controller '.$stamp;

        $this->authorLessonDiscussion($client, $home, $discussionTitle);

        $this->openDiscussionFromLessons($client, $home, $discussionTitle);
        $this->postThread($client, $toolThread, 'Created from the lessons LTI launch.');

        $driver = $client->getWebDriver();
        $driver->get($home.'/discussions');
        $this->waitForPageText($client, $discussionTitle);
        // The catalog link includes a subscribe bell after the first thread, so the
        // visible text is the title plus that mark.
        $driver->findElement(WebDriverBy::partialLinkText($discussionTitle))->click();
        $this->waitForPageText($client, $toolThread);
        $this->assertStringContainsString('/discussions/', $driver->getCurrentURL());
        $this->assertStringNotContainsString('/tool/tdiscus', $driver->getCurrentURL());

        $this->postThread($client, $controllerThread, 'Created from the discussions controller.');
        $this->waitForPageText($client, $toolThread);

        $this->openDiscussionFromLessons($client, $home, $discussionTitle);
        $this->waitForPageText($client, $toolThread);
        $this->waitForPageText($client, $controllerThread);
        $this->captureScreenshot($client, 'discussion-equivalence');
    }

    private function authorLessonDiscussion(\Symfony\Component\Panther\Client $client, string $courseHome, string $discussionTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons/_author');
        $this->waitForPageText($client, 'Add module');

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add module"]'))->click();
        $this->waitForPageText($client, 'Edit Module');
        $title = $driver->findElement(WebDriverBy::id('edit-module-title'));
        $title->clear();
        $title->sendKeys('Panther Discussion Module');
        $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-discussion-module');
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add item"]'))->click();
        $this->waitForPageText($client, 'Add Item');
        $driver->executeScript(
            "document.getElementById('edit-item-type').value = 'discussion'; updateItemForm();"
        );
        $this->waitForPageText($client, 'Resource Link ID');
        $driver->executeScript(
            'document.getElementById("edit-title").value = arguments[0];',
            [$discussionTitle]
        );
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();

        $driver->executeScript('saveChanges()');
        $this->acceptAlertContaining($driver, 'saved');
    }

    private function openDiscussionFromLessons(\Symfony\Component\Panther\Client $client, string $courseHome, string $discussionTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons');
        $this->waitForPageText($client, 'Panther Discussion Module');
        $driver->findElement(WebDriverBy::partialLinkText('Panther Discussion Module'))->click();
        $this->waitForPageText($client, $discussionTitle);
        $driver->findElement(WebDriverBy::linkText($discussionTitle))->click();

        $deadline = microtime(true) + 30;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            $url = $driver->getCurrentURL();
            if (str_contains($url, '/tool/tdiscus') && str_contains($page, 'Add Thread')) {
                $this->pauseAfterPageChange($client);
                return;
            }
            $finish = $driver->findElements(WebDriverBy::xpath("//input[@type='submit' and contains(@value, 'Finish Launch')]"));
            if (count($finish) > 0) {
                $finish[0]->click();
            }
            usleep(200000);
        }

        $this->captureScreenshot($client, 'debug-discussion-launch');
        $excerpt = substr(preg_replace('/\s+/u', ' ', $page) ?? $page, 0, 400);
        $this->fail('Lessons discussion did not open tool/tdiscus. Page excerpt: '.$excerpt);
    }

    private function postThread(\Symfony\Component\Panther\Client $client, string $title, string $body): void
    {
        $driver = $client->getWebDriver();
        $driver->findElement(WebDriverBy::cssSelector('a.tdiscus-add-thread-link'))->click();
        $this->waitForPageText($client, 'New Thread');
        $driver->executeScript(
            'var tokenEl = document.querySelector("input[name=\'_LTI_TSUGI\']");
             var token = tokenEl ? tokenEl.value : "";
             var csrfEl = document.querySelector("input[name=\'CSRF_TOKEN\']");
             var csrf = csrfEl ? csrfEl.value : (window.CSRF_TOKEN || "");
             var form = document.createElement("form");
             form.method = "post";
             form.action = window.location.pathname + window.location.search;
             function add(name, value) {
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = name;
                input.value = value;
                form.appendChild(input);
             }
             add("_LTI_TSUGI", token);
             add("CSRF_TOKEN", csrf);
             add("title", arguments[0]);
             add("body", arguments[1]);
             document.body.appendChild(form);
             form.submit();',
            [$title, $body]
        );
        $this->waitForPageText($client, $title, 20);
        $html = html_entity_decode($client->getPageSource(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringNotContainsString('Title and body are required', $html);
        $this->assertStringNotContainsString('Missing or invalid CSRF token', $html);
    }

    private function acceptAlertContaining(\Facebook\WebDriver\WebDriver $driver, string $needle): void
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
}
