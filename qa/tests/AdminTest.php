<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;

final class AdminTest extends TsugiPantherTestCase
{
    public function testAdminConsoleAccessibleWithPassphrase(): void
    {
        $client = $this->unlockAdmin();
        $page = $client->getPageSource();
        $this->assertStringNotContainsString('Missing or invalid CSRF token', $page);
        $this->assertStringContainsString('Administration Console', $page);
        $this->captureScreenshot($client, 'admin-console');
    }

    public function testAdminScreensHaveNoTracebacks(): void
    {
        $client = $this->unlockAdmin();
        $driver = $client->getWebDriver();

        foreach ($this->adminSmokePages() as $path => $marker) {
            $driver->get($this->uri($path));
            $page = $this->waitForAdminText($client, $marker, $path);
            $this->assertPageHasNoPhpError($page, $path);
        }

        $this->captureScreenshot($client, 'admin-smoke');
    }

    /**
     * Folder screens link to children with relative hrefs (key-detail, key-add).
     * /admin/key has no trailing slash, so the browser would request /admin/key-detail.
     * The folder URL must end in / so those links stay under /admin/key/.
     */
    public function testAdminKeyDetailUrlOpensTheKey(): void
    {
        $client = $this->unlockAdmin();
        $driver = $client->getWebDriver();

        $driver->get($this->uri('admin/key'));
        $page = $this->waitForAdminText($client, 'Keys / Deployments', 'admin/key');
        $listUrl = $driver->getCurrentURL();
        $this->assertMatchesRegularExpression('#/admin/key/($|\?)#', $listUrl);

        $add = $driver->findElement(WebDriverBy::linkText('Insert Key'));
        $this->assertStringContainsString('/admin/key/key-add', $this->resolvedHref($driver, $add));

        $detailLinks = $driver->findElements(WebDriverBy::cssSelector('a[href*="key-detail"]'));
        if ($detailLinks === []) {
            return;
        }
        $this->assertStringContainsString('/admin/key/key-detail', $this->resolvedHref($driver, $detailLinks[0]));
        $detailLinks[0]->click();
        $page = $this->waitForAdminText($client, 'Key status:', 'admin/key/key-detail');
        $this->assertPageHasNoPhpError($page, $driver->getCurrentURL());
        $this->captureScreenshot($client, 'admin-key-detail');
    }

    /**
     * Create, edit, and delete a registration, and point a new key at it.
     * The empty-fields checkbox stays unchecked, so the key keeps its own platform fields.
     */
    public function testRegistrationCrudAndKeyLink(): void
    {
        $client = $this->unlockAdmin();
        $driver = $client->getWebDriver();
        $stamp = 'panther-reg-'.bin2hex(random_bytes(4));
        $title = 'Panther '.$stamp;
        $issuer = 'https://'.$stamp.'.example';
        $clientId = $stamp.'-client';
        $notes = 'notes '.$stamp;
        $edited = 'edited '.$stamp;
        $keyTitle = 'Panther key '.$stamp;

        $driver->get($this->uri('admin/key/registrations'));
        $this->waitForAdminText($client, 'LTI 1.3 Registrations', 'admin/key/registrations');
        $driver->findElement(WebDriverBy::linkText('Insert Registration'))->click();
        $this->waitForAdminText($client, 'Adding LTI 1.3 Registration', 'admin/key/registration-add');

        $driver->findElement(WebDriverBy::id('title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::id('issuer'))->sendKeys($issuer);
        $driver->findElement(WebDriverBy::id('client_id'))->sendKeys($clientId);
        $driver->findElement(WebDriverBy::id('notes'))->sendKeys($notes);
        $driver->findElement(WebDriverBy::name('doSave'))->click();

        $page = $this->waitForAdminText($client, 'Registration Details', 'admin/key/registration-detail');
        $this->assertStringContainsString($title, $page);
        $this->assertStringContainsString($issuer, $page);
        $this->assertStringContainsString($clientId, $page);
        $this->assertStringContainsString($notes, $page);
        $this->assertPageHasNoPhpError($page, 'registration-detail');

        $driver->findElement(WebDriverBy::linkText('Edit'))->click();
        $this->waitForAdminText($client, 'name="notes"', 'admin/key/registration-detail edit');
        $notesField = $driver->findElement(WebDriverBy::id('notes'));
        $notesField->clear();
        $notesField->sendKeys($edited);
        $driver->findElement(WebDriverBy::name('doSave'))->click();

        $page = $this->waitForAdminText($client, $edited, 'admin/key/registration-detail updated');
        $this->assertStringContainsString($title, $page);
        $this->assertStringNotContainsString($notes, $page);
        $this->assertPageHasNoPhpError($page, 'registration-detail updated');

        $driver->get($this->uri('admin/key/key-add'));
        $this->waitForAdminText($client, 'Adding Key', 'admin/key/key-add');
        $driver->findElement(WebDriverBy::id('key_title'))->sendKeys($keyTitle);
        $chosen = $driver->executeScript(
            'const select = document.getElementById("lti_registration_id");'
            .'if (!select) return false;'
            .'const option = Array.from(select.options).find((item) => item.textContent.includes(arguments[0]));'
            .'if (!option) return false;'
            .'select.value = option.value;'
            .'select.dispatchEvent(new Event("change", {bubbles: true}));'
            .'return select.value !== "";',
            [$title]
        );
        $this->assertTrue($chosen, 'The new registration was not in the key form.');
        $box = $driver->findElements(WebDriverBy::id('empty_key_lti13_fields'));
        $this->assertNotEmpty($box);
        $this->assertFalse($box[0]->isSelected());
        $driver->findElement(WebDriverBy::name('doSave'))->click();

        $this->waitForAdminText($client, 'Keys / Deployments', 'admin/key after insert');
        $driver->get($this->uri('admin/key/?search_text='.rawurlencode($keyTitle)));
        $page = $this->waitForAdminText($client, $keyTitle, 'admin/key search');
        $this->assertPageHasNoPhpError($page, 'admin/key search');
        $driver->findElement(WebDriverBy::linkText($keyTitle))->click();
        $page = $this->waitForAdminText($client, 'Key status:', 'admin/key/key-detail');
        $this->assertStringContainsString('registration-detail?lti_registration_id=', $page);
        $this->assertSame('', trim($driver->findElement(WebDriverBy::id('lms_issuer'))->getText()));
        $this->assertSame('', trim($driver->findElement(WebDriverBy::id('lms_client'))->getText()));
        $this->assertPageHasNoPhpError($page, 'key linked to registration');

        $driver->findElement(WebDriverBy::linkText('Registration'))->click();
        $page = $this->waitForAdminText($client, $keyTitle, 'registration lists its key');
        $this->assertStringContainsString('<h2>Keys</h2>', $page);

        $driver->findElement(WebDriverBy::linkText($keyTitle))->click();
        $this->waitForAdminText($client, 'Key status:', 'admin/key/key-detail before delete');
        $this->clickClearOfNavbar($driver, $driver->findElement(WebDriverBy::name('doDelete')));
        $this->acceptAdminAlert($driver, 'delete this record');
        $this->waitForAdminText($client, 'Keys / Deployments', 'admin/key after key delete');
        $driver->get($this->uri('admin/key/?search_text='.rawurlencode($keyTitle)));
        $page = $this->waitForAdminText($client, 'Keys / Deployments', 'admin/key search after delete');
        $this->assertStringNotContainsString($keyTitle, $page);

        $driver->get($this->uri('admin/key/registrations?search_text='.rawurlencode($title)));
        $this->waitForAdminText($client, $title, 'admin/key/registrations before delete');
        $driver->findElement(WebDriverBy::linkText($title))->click();
        $this->waitForAdminText($client, 'Registration Details', 'registration before delete');
        $this->clickClearOfNavbar($driver, $driver->findElement(WebDriverBy::name('doDelete')));
        $this->acceptAdminAlert($driver, 'delete this registration');
        $this->waitForAdminText($client, 'LTI 1.3 Registrations', 'admin/key/registrations after delete');
        $driver->get($this->uri('admin/key/registrations?search_text='.rawurlencode($title)));
        $page = $this->waitForAdminText($client, 'LTI 1.3 Registrations', 'admin/key/registrations search after delete');
        $this->assertStringNotContainsString($title, $page);
        $this->assertPageHasNoPhpError($page, 'registrations after delete');
    }

    /**
     * Open each console section from its menu link, then one safe child.
     * Skips upgrade, delete, expire, blob cleanup, and mail send.
     * Row links are followed only when the list has a row.
     */
    public function testAdminConsoleGoesOneClickDeeper(): void
    {
        $client = $this->unlockAdmin();
        $driver = $client->getWebDriver();

        foreach ($this->adminConsoleSteps() as $step) {
            $driver->get($this->uri('admin/'));
            $this->waitForAdminText($client, 'Administration Console', 'admin/');
            $this->clickAdminLink(
                $client,
                $driver,
                WebDriverBy::linkText($step['link']),
                $step['marker'],
                $step['href'],
                $step['link']
            );

            if (isset($step['next'])) {
                $next = $step['next'];
                $by = isset($next['css'])
                    ? WebDriverBy::cssSelector($next['css'])
                    : WebDriverBy::linkText($next['link']);
                $this->clickAdminLink(
                    $client,
                    $driver,
                    $by,
                    $next['marker'],
                    $next['href'],
                    $step['link'].' → '.($next['link'] ?? $next['css'])
                );
            }

            if (isset($step['row'])) {
                $row = $step['row'];
                $this->clickFirstAdminLink(
                    $client,
                    $driver,
                    WebDriverBy::cssSelector($row['css']),
                    $row['marker'],
                    $row['href'],
                    $step['link'].' row'
                );
            }
        }

        $this->captureScreenshot($client, 'admin-one-click-deeper');
    }

    /**
     * GET-only admin screens. Skips actions that delete, send mail, or migrate.
     *
     * List screens print a column name only after the first row exists.
     * A fresh database shows "Nothing to display." instead.
     *
     * @return array<string, string|list<string>>
     */
    private function adminSmokePages(): array
    {
        return [
            'admin/' => 'Administration Console',
            'admin/site' => 'Edit Site Config',
            'admin/catalog' => 'Course catalog',
            'admin/org/' => 'Pick a tenant',
            'admin/catalog/edit.php' => 'Add catalog listing',
            'admin/key/' => 'Keys / Deployments',
            'admin/key/registrations' => 'LTI 1.3 Registrations',
            'admin/expire/' => 'Manage Data Expiry',
            'admin/context/' => ['members', 'Nothing to display.'],
            'admin/activity/' => ['link_title', 'Nothing to display.'],
            'admin/badges/' => 'Badges Awarded',
            'admin/users/' => ['displayname', 'Nothing to display.'],
            'admin/profile/' => ['premium', 'Nothing to display.'],
            'admin/recent' => ['Ipaddr', 'Nothing to display.'],
            'admin/keyset' => 'Keyset Detail',
            'admin/cache' => 'Cache Detail',
            'admin/mcache' => 'Memcached',
            'admin/crypt' => 'Encrypt/Decrypt',
            'admin/nonce' => 'Nonce count',
            'admin/dbsize.php' => 'Database size',
            'admin/guid' => 'GUID status',
            'admin/mail/' => '<h1>Mail</h1>',
            'admin/events' => 'Launch Activity',
            'admin/blob_status' => 'Blob Status',
            'admin/blob_move' => 'Blob Migration',
            'admin/blob_clean' => 'Blob cleanup',
            'admin/external/' => 'Manage Remote Tsugi Tools',
            'admin/info.php' => 'PHP Version',
        ];
    }

    /**
     * Console link, then one child that does not change data.
     *
     * @return list<array{link: string, marker: string|list<string>, href: string, next?: array{link?: string, css?: string, marker: string|list<string>, href: string}, row?: array{css: string, marker: string|list<string>, href: string}}>
     */
    private function adminConsoleSteps(): array
    {
        return [
            [
                'link' => 'Edit Site Config',
                'marker' => 'Edit Site Config',
                'href' => '/admin/site/',
            ],
            [
                'link' => 'Course Catalog',
                'marker' => 'Course catalog',
                'href' => '/admin/catalog/',
                'next' => [
                    'link' => 'Add listing',
                    'marker' => 'Add catalog listing',
                    'href' => '/admin/catalog/edit',
                ],
            ],
            [
                'link' => 'Manage Organizations',
                'marker' => ['Pick a tenant', 'Organizations'],
                'href' => '/admin/org/',
                'row' => [
                    'css' => 'a[href*="key_id="]',
                    'marker' => 'Add organization',
                    'href' => '/admin/org/',
                ],
            ],
            [
                'link' => 'Manage Access Keys',
                'marker' => 'Keys / Deployments',
                'href' => '/admin/key/',
                'next' => [
                    'link' => 'Insert Key',
                    'marker' => 'Adding Key',
                    'href' => '/admin/key/key-add',
                ],
            ],
            [
                'link' => 'Manage Data Expiry',
                'marker' => 'Manage Data Expiry',
                'href' => '/admin/expire/',
                // View is omitted when nothing is old enough to expire.
                'row' => [
                    'css' => 'a[href*="pii-detail"]',
                    'marker' => ['Nothing to display.', 'Login At'],
                    'href' => '/admin/expire/pii-detail',
                ],
            ],
            [
                'link' => 'View Contexts',
                'marker' => ['members', 'Nothing to display.'],
                'href' => '/admin/context/',
                'row' => [
                    'css' => 'a[href*="membership"]',
                    'marker' => 'View/Edit Context Settings',
                    'href' => '/admin/context/membership',
                ],
            ],
            [
                'link' => 'View Activity',
                'marker' => ['link_title', 'Nothing to display.'],
                'href' => '/admin/activity/',
                'row' => [
                    'css' => 'a[href*="activity-detail"]',
                    'marker' => 'tsugi-analytics-chart',
                    'href' => '/admin/activity/activity-detail',
                ],
            ],
            [
                'link' => 'Badges Awarded',
                'marker' => 'Badges Awarded',
                'href' => '/admin/badges/',
            ],
            [
                'link' => 'View Users',
                'marker' => ['displayname', 'Nothing to display.'],
                'href' => '/admin/users/',
                'row' => [
                    'css' => 'a[href*="user-detail"]',
                    'marker' => 'User Detail',
                    'href' => '/admin/users/user-detail',
                ],
            ],
            [
                'link' => 'View Profiles',
                'marker' => ['premium', 'Nothing to display.'],
                'href' => '/admin/profile/',
                'row' => [
                    'css' => 'a[href*="profile-detail"]',
                    'marker' => 'Profile Detail',
                    'href' => '/admin/profile/profile-detail',
                ],
            ],
            [
                'link' => 'Manage Installed Modules',
                'marker' => ['Installed Modules', 'Install folder'],
                'href' => '/admin/modules/',
            ],
            [
                'link' => 'Mail',
                'marker' => '<h1>Mail</h1>',
                'href' => '/admin/mail/',
                'next' => [
                    'link' => 'Test E-Mail',
                    'marker' => 'Test Mail Sending',
                    'href' => '/admin/testmail',
                ],
            ],
            [
                'link' => 'Manage Remote Tsugi Tools (deprecated)',
                'marker' => 'Manage Remote Tsugi Tools',
                'href' => '/admin/external/',
                'next' => [
                    'link' => 'Add Tool',
                    'marker' => 'Adding Remote Tsugi Tool',
                    'href' => '/admin/external/ext-add',
                ],
            ],
        ];
    }

    private function acceptAdminAlert(\Facebook\WebDriver\WebDriver $driver, string $needle): void
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

    private function unlockAdmin(): \Symfony\Component\Panther\Client
    {
        $passphrase = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($passphrase, 'TSUGI_ADMIN_PW must be set for admin tests.');
        $this->assertNotSame('', $passphrase, 'TSUGI_ADMIN_PW must not be empty.');

        $client = $this->pantherClient();
        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/'));
        $deadline = microtime(true) + 15;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (str_contains($page, 'Administration Console') || str_contains($page, 'name="passphrase"')) {
                break;
            }
            usleep(200000);
        }
        if (!str_contains($page, 'Administration Console')) {
            $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($passphrase);
            $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
            $this->waitForAdminText($client, 'Administration Console');
        }

        return $client;
    }

    /**
     * @param string|list<string> $marker
     */
    private function clickAdminLink(
        \Symfony\Component\Panther\Client $client,
        \Facebook\WebDriver\WebDriver $driver,
        WebDriverBy $by,
        string|array $marker,
        string $hrefContains,
        string $label
    ): void {
        $link = $driver->findElement($by);
        $href = $this->resolvedHref($driver, $link);
        $this->assertStringContainsString($hrefContains, $href, $label.' href '.$href);
        $link->click();
        $page = $this->waitForAdminText($client, $marker, $label);
        $this->assertPageHasNoPhpError($page, $label);
    }

    /**
     * @param string|list<string> $marker
     */
    private function clickFirstAdminLink(
        \Symfony\Component\Panther\Client $client,
        \Facebook\WebDriver\WebDriver $driver,
        WebDriverBy $by,
        string|array $marker,
        string $hrefContains,
        string $label
    ): void {
        $links = $driver->findElements($by);
        if ($links === []) {
            return;
        }
        $href = $this->resolvedHref($driver, $links[0]);
        $this->assertStringContainsString($hrefContains, $href, $label.' href '.$href);
        $links[0]->click();
        $page = $this->waitForAdminText($client, $marker, $label);
        $this->assertPageHasNoPhpError($page, $label);
    }

    /**
     * Chrome's getAttribute('href') is the raw markup (site/, key-add).
     * The DOM href is the URL the browser will actually request.
     */
    private function resolvedHref(\Facebook\WebDriver\WebDriver $driver, \Facebook\WebDriver\WebDriverElement $link): string
    {
        $href = $driver->executeScript('return arguments[0].href;', [$link]);
        $this->assertIsString($href);

        return $href;
    }

    /**
     * @param string|list<string> $marker
     */
    private function waitForAdminText(\Symfony\Component\Panther\Client $client, string|array $marker, string $path = 'admin/'): string
    {
        $markers = is_array($marker) ? $marker : [$marker];
        $deadline = microtime(true) + 20;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            foreach ($markers as $needle) {
                if (str_contains($page, $needle)) {
                    return $page;
                }
            }
            usleep(200000);
        }

        $this->captureScreenshot($client, 'debug-admin-smoke');
        $excerpt = substr(preg_replace('/\s+/u', ' ', $page) ?? $page, 0, 300);
        $shown = implode('" or "', $markers);
        $this->fail($path.' did not show "'.$shown.'". '.$excerpt);
    }
}
