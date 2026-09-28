<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;

final class DemoCourseTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testInstructorDemoLoginCreatesCourse(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $courseHome = $course['home'];
        $this->saveCourseNavigation($client, $courseHome);
        $this->createAndPublishPage($client, $courseHome);
        $this->placePageInLesson($client, $courseHome);
        $this->launchPageFromLessons($client, $courseHome);
        $this->captureScreenshot($client, 'demo-page-from-lessons');
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
}
