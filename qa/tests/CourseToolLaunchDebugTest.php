<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\WebDriverBy;

/**
 * Course LTI 1.1 tools: the test page shows launch parameters in the debug
 * toggle. These checks do not submit the launch.
 */
final class CourseToolLaunchDebugTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testPrivacyCheckboxesAppearInTheLaunchDebugToggle(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];

        $this->addCourseTool($client, $home, 'Names only', true, false);
        $this->assertLaunchDebug($client, $home, 'Names only', true, false);

        $this->addCourseTool($client, $home, 'Email only', false, true);
        $this->assertLaunchDebug($client, $home, 'Email only', false, true);

        $this->addCourseTool($client, $home, 'Names and email', true, true);
        $this->assertLaunchDebug($client, $home, 'Names and email', true, true);

        $this->addCourseTool($client, $home, 'No privacy', false, false);
        $this->assertLaunchDebug($client, $home, 'No privacy', false, false);

        $this->captureScreenshot($client, 'course-tool-launch-debug');
    }

    private function addCourseTool(
        \Symfony\Component\Panther\Client $client,
        string $courseHome,
        string $title,
        bool $names,
        bool $email
    ): void {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/tools/add');
        $this->waitForPageText($client, 'Add an LTI 1.1 tool to this course.');

        $suffix = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? 'tool');
        $driver->findElement(WebDriverBy::id('lti11_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('lti11_lti11_url'))->sendKeys('https://tool.example/'.$suffix);
        $driver->findElement(WebDriverBy::id('lti11_lti11_key'))->sendKeys('panther-key-'.$suffix);
        $driver->findElement(WebDriverBy::id('lti11_lti11_secret'))->sendKeys('panther-secret-'.$suffix);
        $driver->findElement(WebDriverBy::id('messages_LtiResourceLinkRequest'))->click();
        if ($names) {
            $driver->findElement(WebDriverBy::id('privacy_names'))->click();
        }
        if ($email) {
            $driver->findElement(WebDriverBy::id('privacy_email'))->click();
        }
        $driver->findElement(WebDriverBy::cssSelector('#lti11-course-tool button[type="submit"]'))->click();
        $this->waitForPageText($client, 'The tool was added to this course.');
        $this->waitForPageText($client, $title);
    }

    private function assertLaunchDebug(
        \Symfony\Component\Panther\Client $client,
        string $courseHome,
        string $title,
        bool $names,
        bool $email
    ): void {
        $driver = $client->getWebDriver();
        $driver->get($courseHome.'/settings/tools');
        $this->waitForPageText($client, $title);
        $driver->findElement(WebDriverBy::xpath(
            "//li[contains(., '".$title."')]//a[normalize-space()='Test']"
        ))->click();
        $this->waitForPageText($client, 'toggle_debug_data');
        $this->assertStringContainsString('/settings/tools/test', $driver->getCurrentURL());

        $opened = $driver->executeScript(
            'var link = document.querySelector("a.basicltiDebugToggle");'
            .' if (!link) { return ""; }'
            .' var href = link.getAttribute("href") || "";'
            .' if (href.indexOf("javascript:") === 0) { eval(href.substring(11)); }'
            .' else { link.click(); }'
            .' var box = document.querySelector("div[id^=\'basicltiDebug_\']");'
            .' if (!box) { return ""; }'
            .' return box.style.display + "\n" + (box.innerText || box.textContent || "");'
        );
        $this->assertIsString($opened);
        $this->assertStringStartsWith("block\n", $opened, 'Debug toggle did not open the parameter list.');
        $debug = substr($opened, strlen("block\n"));

        $this->assertStringContainsString('basic-lti-launch-request', $debug);
        $this->assertStringContainsString('https://tool.example/', $debug);
        $this->assertStringNotContainsString('panther-secret-', $debug);
        $this->assertStringContainsString('Send Resource link', $client->getPageSource());

        if ($names) {
            $this->assertStringContainsString('lis_person_name_full = Instructor 01', $debug);
            $this->assertStringContainsString('lis_person_name_given = Instructor', $debug);
            $this->assertStringContainsString('lis_person_name_family = 01', $debug);
        } else {
            $this->assertStringNotContainsString('lis_person_name_full', $debug);
            $this->assertStringNotContainsString('lis_person_name_given', $debug);
            $this->assertStringNotContainsString('lis_person_name_family', $debug);
        }
        if ($email) {
            $this->assertStringContainsString('lis_person_contact_email_primary = instructor01@notgoogle.com', $debug);
        } else {
            $this->assertStringNotContainsString('lis_person_contact_email_primary', $debug);
        }

        $this->assertStringContainsString('/settings/tools/test', $driver->getCurrentURL());
    }
}
