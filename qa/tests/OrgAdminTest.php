<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';
require_once __DIR__ . '/Support/DemoCourseSteps.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Admin organization builder: one tenant, a child org, and a course placed on it.
 */
final class OrgAdminTest extends TsugiPantherTestCase
{
    use DemoCourseSteps;

    public function testOrgAdminPlacesACourseAndBlocksDeleteUntilItIsMoved(): void
    {
        $client = $this->pantherClient();
        $course = $this->startInstructorCourse($client);
        $this->unlockAdmin($client);

        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/org/'));
        $this->waitForPageText($client, 'Pick a tenant');
        $driver->findElement(WebDriverBy::xpath("//tr[contains(., 'google.com')]//a[contains(., 'Open')]"))->click();
        $this->waitForPageText($client, 'Add organization');

        $this->createOrg($client, 'Panther College', 'Tenant root');
        $this->waitForPageText($client, 'Organization created.');
        $this->createOrg($client, 'Panther Department', 'Panther College');
        $this->waitForPageText($client, 'Organization created.');
        $this->waitForPageText(
            $client,
            'There is 1 sub-organization. You must move it before you can delete this organization.'
        );

        $driver->findElement(WebDriverBy::xpath(
            "//tr[contains(., '1 sub-organization')]//a[normalize-space()='Courses']"
        ))->click();
        $this->waitForPageText($client, 'Add a course');
        $courseSelect = new WebDriverSelect($driver->findElement(WebDriverBy::id('add_course')));
        $courseSelect->selectByVisibleText($course['title']);
        $driver->findElement(WebDriverBy::xpath("//form[.//input[@name='action' and @value='place']]//button[@type='submit']"))->click();
        $this->waitForPageText($client, 'Course added to this organization.');
        $this->waitForPageText($client, $course['title']);

        $driver->findElement(WebDriverBy::linkText('Back to organizations'))->click();
        $this->waitForPageText(
            $client,
            'There are 1 course in this organization and 1 sub-organization. You must move them before you can delete this organization.'
        );

        $this->moveOrg($client, 'Panther Department', 'Tenant root');
        $this->waitForPageText($client, 'Organization moved.');
        $this->waitForPageText(
            $client,
            'There is 1 course in this organization. You must move it before you can delete this organization.'
        );

        $driver->findElement(WebDriverBy::xpath(
            "//tr[contains(., '1 course in this organization')]//a[normalize-space()='Courses']"
        ))->click();
        $this->waitForPageText($client, $course['title']);
        $courseRow = $driver->findElement(WebDriverBy::xpath(
            "//tr[contains(., '".$course['title']."')]"
        ));
        $move = new WebDriverSelect($courseRow->findElement(WebDriverBy::name('parent_org_id')));
        $move->selectByVisibleText('Not in an organization');
        $courseRow->findElement(WebDriverBy::xpath(".//button[contains(., 'Move')]"))->click();
        $this->waitForPageText($client, 'Course moved.');

        $driver->findElement(WebDriverBy::linkText('Back to organizations'))->click();
        $this->waitForPageText($client, 'Panther College');
        $page = $client->getPageSource();
        $this->assertStringNotContainsString('before you can delete this organization', $page);
        $this->assertStringContainsString('>Delete<', $page);
        $this->captureScreenshot($client, 'org-admin');
    }

    private function unlockAdmin(\Symfony\Component\Panther\Client $client): void
    {
        $passphrase = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($passphrase, 'TSUGI_ADMIN_PW must be set.');
        $this->assertNotSame('', $passphrase, 'TSUGI_ADMIN_PW must not be empty.');

        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/'));
        $page = $client->getPageSource();
        if (str_contains($page, 'Administration Console')) {
            return;
        }
        $this->waitForPageText($client, 'name="passphrase"');
        $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($passphrase);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
        $this->waitForPageText($client, 'Administration Console');
    }

    private function createOrg(\Symfony\Component\Panther\Client $client, string $title, string $parentLabel): void
    {
        $driver = $client->getWebDriver();
        $titleField = $driver->findElement(WebDriverBy::id('new_title'));
        $titleField->clear();
        $titleField->sendKeys($title);
        $parent = new WebDriverSelect($driver->findElement(WebDriverBy::id('new_parent')));
        $parent->selectByVisibleText($parentLabel);
        $driver->findElement(WebDriverBy::xpath(
            "//form[.//input[@name='action' and @value='create']]//button[@type='submit']"
        ))->click();
    }

    private function moveOrg(\Symfony\Component\Panther\Client $client, string $title, string $parentLabel): void
    {
        $driver = $client->getWebDriver();
        $row = $driver->findElement(WebDriverBy::xpath("//input[@value='".$title."']/ancestor::tr"));
        $parent = new WebDriverSelect($row->findElement(WebDriverBy::name('parent_org_id')));
        $parent->selectByVisibleText($parentLabel);
        $row->findElement(WebDriverBy::xpath(".//form[.//input[@name='action' and @value='move']]//button[@type='submit']"))->click();
    }
}
