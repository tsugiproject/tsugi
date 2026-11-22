<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\WebDriverBy;

/**
 * Register this same Tsugi as an LTI 1.3 tool on a Panther course.
 * The test stops on the launch page. Send would replace the course session.
 */
final class CourseToolLoopbackTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testSameServerDynamicRegistrationShowsPrivacyAndDeepLink(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $home = $course['home'];
        $title = 'Panther Loopback '.date('YmdHis');
        $unlock = 'panther-unlock';

        $this->unlockAdmin($client);
        $registrationUrl = $this->draftKeyRegistrationUrl($client, $title, $unlock);

        $driver = $client->getWebDriver();
        $driver->get($home.'/settings/tools');
        $this->waitForPageText($client, 'LTI Dynamic Registration URL');
        $driver->findElement(WebDriverBy::id('tool_registration_url'))->sendKeys($registrationUrl);
        $driver->findElement(WebDriverBy::cssSelector('form[action*="/tools/dynamic"] button[type="submit"]'))->click();
        $this->waitForPageText($client, 'When the tool finishes, this page returns to the course tools.');

        $driver->switchTo()->frame($driver->findElement(WebDriverBy::id('tsugi-dynamic-registration')));
        $this->waitForPageText($client, 'Continue Registration in the LMS', 45);
        $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Continue Registration in the LMS')]"))->click();
        $driver->switchTo()->defaultContent();

        $this->waitForPageText($client, 'The tool was registered.', 20);
        $this->waitForPageText($client, 'LtiDeepLinkingRequest');
        $this->waitForPageText($client, 'LtiDataPrivacyLaunchRequest');
        $this->assertStringContainsString('(LTI 1.3)', $client->getPageSource());

        $driver->findElement(WebDriverBy::linkText('Test'))->click();
        $this->waitForPageText($client, 'Deep link');
        $this->waitForPageText($client, 'Privacy launch');
        $page = $client->getPageSource();
        $this->assertStringContainsString('Send', $page);
        $this->assertStringContainsString('/settings/tools/test', $driver->getCurrentURL());
        $this->captureScreenshot($client, 'course-tool-loopback');
    }

    private function unlockAdmin(\Symfony\Component\Panther\Client $client): void
    {
        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/'));
        $page = $client->getPageSource();
        if (!str_contains($page, 'Admin Unlock')) {
            $this->waitForPageText($client, 'Administration Console');
            return;
        }
        $adminPw = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($adminPw);
        $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($adminPw);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
        $this->waitForPageText($client, 'Administration Console');
    }

    private function draftKeyRegistrationUrl(\Symfony\Component\Panther\Client $client, string $title, string $unlock): string
    {
        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/key/key-add'));
        $this->waitForPageText($client, 'Adding Tsugi Tenant/Key');
        $driver->findElement(WebDriverBy::id('key_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('lms_issuer'))->sendKeys($this->baseUri());
        $driver->findElement(WebDriverBy::id('lms_client'))->sendKeys('panther-client-'.date('YmdHis'));
        $driver->findElement(WebDriverBy::name('doSave'))->click();
        $this->waitForPageText($client, 'LTI Tenants');
        $this->openKey($client, $title);

        $driver->findElement(WebDriverBy::linkText('Edit'))->click();
        $this->waitForPageText($client, 'name="unlock_code"');
        $driver->findElement(WebDriverBy::id('unlock_code'))->sendKeys($unlock);
        $driver->findElement(WebDriverBy::name('doUpdate'))->click();
        $this->waitForPageText($client, 'LTI Tenants');
        $this->openKey($client, $title);
        $this->waitForPageText($client, 'LTI Advantage Dynamic Registration URL');

        $page = $client->getPageSource();
        if (!preg_match('#https?://[^"\s<]+/admin/key/auto\.php\?tsugi_key=\d+&(?:amp;)?unlock_code='.preg_quote($unlock, '#').'#', $page, $match)) {
            $this->fail('Dynamic registration URL was not on the key detail page.');
        }

        return html_entity_decode($match[0], ENT_QUOTES, 'UTF-8');
    }

    private function openKey(\Symfony\Component\Panther\Client $client, string $title): void
    {
        $driver = $client->getWebDriver();
        $driver->findElement(WebDriverBy::xpath(
            "//tr[contains(., '".$title."')]//a[contains(@href, 'key-detail')]"
        ))->click();
        $this->waitForPageText($client, 'Tenant Details');
        $this->waitForPageText($client, $title);
    }
}
