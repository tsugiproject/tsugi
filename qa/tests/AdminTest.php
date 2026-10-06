<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

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
        $page = $this->waitForAdminText($client, 'LTI Tenants', 'admin/key');
        $listUrl = $driver->getCurrentURL();
        $this->assertMatchesRegularExpression('#/admin/key/($|\?)#', $listUrl);

        $add = $driver->findElement(WebDriverBy::linkText('Insert Tenant'));
        $this->assertStringContainsString('/admin/key/key-add', $this->resolvedHref($driver, $add));

        $detailLinks = $driver->findElements(WebDriverBy::cssSelector('a[href*="key-detail"]'));
        if ($detailLinks === []) {
            return;
        }
        $this->assertStringContainsString('/admin/key/key-detail', $this->resolvedHref($driver, $detailLinks[0]));
        $detailLinks[0]->click();
        $page = $this->waitForAdminText($client, 'Tenant Details', 'admin/key/key-detail');
        $this->assertPageHasNoPhpError($page, $driver->getCurrentURL());
        $this->captureScreenshot($client, 'admin-key-detail');
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
            'admin/key/' => 'LTI Tenants',
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
            'admin/mail/' => '<h1>Mail</h1>',
            'admin/events' => 'Event Detail',
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
                'marker' => 'LTI Tenants',
                'href' => '/admin/key/',
                'next' => [
                    'link' => 'Insert Tenant',
                    'marker' => 'Adding Tsugi Tenant/Key',
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
                'href' => '/admin/install/',
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
