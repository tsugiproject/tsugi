<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;

/**
 * External Links: an unprovisioned lesson launch can be provisioned there,
 * made gradable, and then launched without a sourcedid after grades are turned off.
 * The grade column stays, so turning grades back on uses the same resource link.
 */
final class CourseExternalLinksTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testProvisionGradeAndOmitSourcedidWhenGradesAreOff(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];
        $toolTitle = 'Panther Links Tool';
        $itemTitle = 'Panther Proto Link';

        $this->addGradableCourseTool($client, $home, $toolTitle);
        $this->assertOneTabBar($client, 'External Tools');
        $this->saveUnprovisionedLessonLink($client, $home, $itemTitle);
        $this->filterAndClearTheLinkList($client, $home, $itemTitle);

        $this->provisionFromExternalLinks($client, $itemTitle, $toolTitle);
        $this->assertSame('Gradable', $this->linkStatus($client, $itemTitle));

        $resourceLinkId = $this->setGradable($client, $home, false);
        $withoutGrade = $this->launchParameters($client, $home, $itemTitle);
        $this->assertSame($resourceLinkId, $withoutGrade['resource_link_id']);
        $this->assertSame('', $withoutGrade['sourcedid'], 'A launch that is not gradable should omit lis_result_sourcedid.');
        $this->assertSame('', $withoutGrade['outcome'], 'A launch that is not gradable should omit lis_outcome_service_url.');

        $again = $this->setGradable($client, $home, true);
        $this->assertSame($resourceLinkId, $again);
        $withGrade = $this->launchParameters($client, $home, $itemTitle);
        $this->assertSame($resourceLinkId, $withGrade['resource_link_id']);
        $this->assertNotSame('', $withGrade['sourcedid']);
        $this->assertStringContainsString('poxresult.php', $withGrade['outcome']);
        $this->captureScreenshot($client, 'external-links-grade');
    }

    private function addGradableCourseTool(\Symfony\Component\Panther\Client $client, string $courseHome, string $title): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/tools/add');
        $this->waitForPageText($client, 'Add an LTI 1.1 tool to this course.');
        $this->assertOneTabBar($client, 'External Tools');

        $driver->findElement(WebDriverBy::id('lti11_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('lti11_lti11_url'))->sendKeys('https://tool.example/panther-links');
        $driver->findElement(WebDriverBy::id('lti11_lti11_key'))->sendKeys('panther-links-key');
        $driver->findElement(WebDriverBy::id('lti11_lti11_secret'))->sendKeys('panther-links-secret');
        $driver->findElement(WebDriverBy::id('messages_LtiResourceLinkRequest'))->click();
        $driver->findElement(WebDriverBy::id('services_score'))->click();
        $driver->findElement(WebDriverBy::cssSelector('#lti11-course-tool button[type="submit"]'))->click();
        $this->waitForPageText($client, 'The tool was added to this course.');
        $this->waitForPageText($client, $title);
    }

    private function saveUnprovisionedLessonLink(\Symfony\Component\Panther\Client $client, string $courseHome, string $itemTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons/_author');
        $this->waitForPageText($client, 'Add module');

        $driver->findElement(WebDriverBy::cssSelector('button[aria-label="Add module"]'))->click();
        $this->waitForPageText($client, 'Edit Module');
        $module = $driver->findElement(WebDriverBy::id('edit-module-title'));
        $module->clear();
        $module->sendKeys('Panther Links Module');
        $driver->findElement(WebDriverBy::id('edit-module-anchor'))->sendKeys('panther-links-module');
        $driver->findElement(WebDriverBy::xpath("//div[@id='item-modal']//button[contains(., 'Save')]"))->click();
        $this->waitForPageText($client, 'Panther Links Module');

        $saved = $driver->executeScript(
            'if (!window.lessonsData || !lessonsData.modules) { return "no module"; }
             var mod = lessonsData.modules.find(function (m) { return m && m.title === "Panther Links Module"; });
             if (!mod) { return "no module"; }
             mod.items = mod.items || [];
             mod.items.push({
                 type: "lti",
                 title: arguments[0],
                 launch: "https://imported.example/panther-proto",
                 resource_link_id: "panther_proto_" + Date.now(),
                 target: "_blank"
             });
             saveChanges();
             return "ok";',
            [$itemTitle]
        );
        $this->assertSame('ok', $saved, 'Could not add an unprovisioned lesson link.');
        $this->acceptAlertContaining($driver, 'saved');
    }

    private function filterAndClearTheLinkList(\Symfony\Component\Panther\Client $client, string $courseHome, string $itemTitle): void
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/tools/links');
        $this->waitForPageText($client, $itemTitle);
        $this->assertOneTabBar($client, 'External Links');
        $this->assertSame('Not provisioned', $this->linkStatus($client, $itemTitle));
        $this->waitForPageText($client, 'Lessons — Panther Links Module');

        $driver->findElement(WebDriverBy::id('link-search'))->sendKeys('zzzz-no-such-link');
        $this->waitUntil(
            function () use ($client, $itemTitle): bool {
                return $this->linkRowDisplay($client, $itemTitle) === 'none';
            },
            'The text filter did not hide the lesson link.'
        );
        $driver->findElement(WebDriverBy::id('link-clear'))->click();
        $this->waitUntil(
            function () use ($client, $itemTitle): bool {
                $search = $client->getWebDriver()->executeScript(
                    'var el = document.getElementById("link-search"); return el ? el.value : "missing";'
                );
                return $this->linkRowDisplay($client, $itemTitle) === '' && $search === '';
            },
            'Clear did not restore the lesson link.'
        );
    }

    private function provisionFromExternalLinks(
        \Symfony\Component\Panther\Client $client,
        string $itemTitle,
        string $toolTitle
    ): void {
        $driver = $client->getWebDriver();
        $picked = $driver->executeScript(
            'var title = arguments[0];
             var tool = arguments[1];
             var row = Array.from(document.querySelectorAll(".link-row")).find(function (el) {
                 return (el.innerText || "").indexOf(title) !== -1;
             });
             if (!row) { return "no row"; }
             var detail = row.nextElementSibling;
             var sel = detail && detail.querySelector(".js-provision");
             if (!sel) { return "no select"; }
             var opt = Array.from(sel.options).find(function (o) { return (o.textContent || "").indexOf(tool) !== -1; });
             if (!opt) { return "no tool"; }
             sel.value = opt.value;
             sel.dispatchEvent(new Event("change"));
             return opt.value;',
            [$itemTitle, $toolTitle]
        );
        $this->assertNotSame('no row', $picked, 'External Links did not list the lesson link.');
        $this->assertNotSame('no select', $picked, 'The unprovisioned link had no deployment menu.');
        $this->assertNotSame('no tool', $picked, 'The course tool was not in the deployment menu.');
        $this->assertNotSame('', $picked);

        $this->waitForPageText($client, 'Resource link:', 30);
        $this->waitForPageText($client, $itemTitle);
    }

    /**
     * @return string Resource link id shown on the content row.
     */
    private function setGradable(\Symfony\Component\Panther\Client $client, string $courseHome, bool $on): string
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/tools/links');
        $this->waitForPageText($client, 'Resource link:');
        $this->wrapAjax($driver);
        $driver->executeScript('window.__lastAjaxAction = "";');
        $clicked = $driver->executeScript(
            'var box = document.querySelector(".link-detail-body .js-send-grade");
             if (!box) { return "missing"; }
             if (box.checked === arguments[0]) { return "already"; }
             box.click();
             return "clicked";',
            [$on]
        );
        $this->assertNotSame('missing', $clicked, 'The grade checkbox was not on the link detail.');
        if ($clicked === 'clicked') {
            $this->waitForAjaxAction($driver, 'patch-lti');
        }
        $state = $driver->executeScript(
            'var box = document.querySelector(".link-detail-body .js-send-grade");
             var text = "";
             var node = document.querySelector(".link-detail-body");
             if (node) {
                 var help = Array.from(node.querySelectorAll("p")).find(function (p) {
                     return (p.textContent || "").indexOf("Resource link:") === 0;
                 });
                 text = help ? help.textContent : "";
             }
             return { checked: !!(box && box.checked), resource: text };'
        );
        $this->assertIsArray($state);
        $this->assertSame($on, $state['checked']);
        $this->assertStringContainsString('Resource link:', (string) $state['resource']);
        $id = trim(substr((string) $state['resource'], strlen('Resource link:')));
        $this->assertNotSame('', $id);

        return $id;
    }

    /**
     * @return array{resource_link_id: string, sourcedid: string, outcome: string}
     */
    private function launchParameters(\Symfony\Component\Panther\Client $client, string $courseHome, string $itemTitle): array
    {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/lessons');
        $this->waitForPageText($client, 'Panther Links Module');
        $driver->findElement(WebDriverBy::partialLinkText('Panther Links Module'))->click();
        $this->waitForPageText($client, $itemTitle);
        $href = $driver->executeScript(
            'var title = arguments[0];
             var link = Array.from(document.querySelectorAll("a")).find(function (el) {
                 return (el.textContent || "").indexOf(title) !== -1;
             });
             return link ? link.href : "";',
            [$itemTitle]
        );
        $this->assertIsString($href);
        $this->assertStringContainsString('lessons_launch/content-', $href);

        $html = $driver->executeAsyncScript(
            'var done = arguments[arguments.length - 1];
             fetch(arguments[0], {credentials: "same-origin"})
                 .then(function (response) { return response.text(); })
                 .then(function (text) { done(text); })
                 .catch(function (error) { done("ERR " + error); });',
            [$href]
        );
        $this->assertIsString($html);
        $this->assertFalse(str_starts_with($html, 'ERR '), 'Could not read the lesson launch.');
        $this->assertStringContainsString('name="resource_link_id"', $html);

        return [
            'resource_link_id' => $this->hiddenValue($html, 'resource_link_id'),
            'sourcedid' => $this->hiddenValue($html, 'lis_result_sourcedid'),
            'outcome' => $this->hiddenValue($html, 'lis_outcome_service_url'),
        ];
    }

    private function hiddenValue(string $html, string $name): string
    {
        $pattern = '/name="'.preg_quote($name, '/').'" value="([^"]*)"/';
        if (!preg_match($pattern, $html, $match)) {
            return '';
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function assertOneTabBar(\Symfony\Component\Panther\Client $client, string $activeLabel): void
    {
        $driver = $client->getWebDriver();
        $tabs = $driver->executeScript(
            'return {
                count: document.querySelectorAll("ul.nav-tabs").length,
                active: (document.querySelector("ul.nav-tabs li.active a") || {}).textContent || ""
             };'
        );
        $this->assertIsArray($tabs);
        $this->assertSame(1, $tabs['count'], 'Settings should have one tab bar.');
        $this->assertSame($activeLabel, trim((string) $tabs['active']));
    }

    private function linkStatus(\Symfony\Component\Panther\Client $client, string $itemTitle): string
    {
        $status = $client->getWebDriver()->executeScript(
            'var title = arguments[0];
             var row = Array.from(document.querySelectorAll(".link-row")).find(function (el) {
                 return (el.innerText || "").indexOf(title) !== -1;
             });
             if (!row) { return ""; }
             var cell = row.querySelector(".js-status");
             return cell ? cell.textContent.trim() : "";',
            [$itemTitle]
        );
        $this->assertIsString($status);

        return $status;
    }

    private function linkRowDisplay(\Symfony\Component\Panther\Client $client, string $itemTitle): string
    {
        $display = $client->getWebDriver()->executeScript(
            'var title = arguments[0];
             var row = Array.from(document.querySelectorAll(".link-row")).find(function (el) {
                 return (el.innerText || "").indexOf(title) !== -1;
             });
             return row ? row.style.display : "missing";',
            [$itemTitle]
        );
        $this->assertIsString($display);

        return $display;
    }

    private function wrapAjax(\Facebook\WebDriver\WebDriver $driver): void
    {
        $driver->executeScript(
            'if (window.__ajaxWrapped || !window.jQuery) { return; }
             var orig = jQuery.ajax;
             jQuery.ajax = function (opts) {
                 opts = opts || {};
                 var userSuccess = opts.success;
                 opts.success = function () {
                     window.__lastAjaxAction = (opts.data && opts.data.action) || "";
                     if (userSuccess) { return userSuccess.apply(this, arguments); }
                 };
                 return orig.call(this, opts);
             };
             window.__ajaxWrapped = true;'
        );
    }

    private function waitForAjaxAction(\Facebook\WebDriver\WebDriver $driver, string $action): void
    {
        $this->waitUntil(
            function () use ($driver, $action): bool {
                return $driver->executeScript('return window.__lastAjaxAction || "";') === $action;
            },
            'Timed out waiting for '.$action.'.'
        );
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

    private function waitUntil(callable $ready, string $message, int $timeoutSeconds = 15): void
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
