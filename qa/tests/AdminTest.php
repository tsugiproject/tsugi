<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Facebook\WebDriver\WebDriverBy;

final class AdminTest extends TsugiPantherTestCase
{
    public function testAdminConsoleAccessibleWithPassphrase(): void
    {
        $passphrase = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($passphrase, 'TSUGI_ADMIN_PW must be set for admin tests.');
        $this->assertNotSame('', $passphrase, 'TSUGI_ADMIN_PW must not be empty.');

        $client = $this->pantherClient();
        $client->request('GET', $this->uri('admin/'));

        $driver = $client->getWebDriver();
        $input = $driver->findElement(WebDriverBy::name('passphrase'));
        $input->sendKeys($passphrase);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();

        $deadline = microtime(true) + 15;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (str_contains($page, 'Administration Console')) {
                break;
            }
            usleep(200000);
        }

        $this->assertStringNotContainsString('Missing or invalid CSRF token', $page);
        $this->assertStringContainsString(
            'Administration Console',
            $page
        );

        $this->captureScreenshot($client, 'admin-console');
    }

    public function testAdminScreensHaveNoTracebacks(): void
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

        foreach ($this->adminSmokePages() as $path => $marker) {
            $driver->get($this->uri($path));
            $page = $this->waitForAdminText($client, $marker, $path);
            $this->assertPageHasNoPhpError($page, $path);
        }

        $this->captureScreenshot($client, 'admin-smoke');
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
            'admin/catalog/edit.php' => 'Add catalog listing',
            'admin/key/' => 'LTI Tenants',
            'admin/expire/' => 'Manage Data Expiry',
            'admin/context/' => ['members', 'Nothing to display.'],
            'admin/activity/' => ['link_title', 'Nothing to display.'],
            'admin/badges/' => 'Badges Awarded',
            'admin/users/' => ['displayname', 'Nothing to display.'],
            'admin/profile/' => ['premium', 'Nothing to display.'],
            'admin/recent' => ['Ipaddr', 'Nothing to display.'],
            'admin/install/' => 'git',
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
     * @param string|list<string> $marker
     */
    private function waitForAdminText(\Symfony\Component\Panther\Client $client, string|array $marker, string $path = 'admin/'): string
    {
        $markers = is_array($marker) ? $marker : [$marker];
        $deadline = microtime(true) + 20;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $this->pageSource($client, $path);
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

    /**
     * The install screen alerts when git refuses the container checkout.
     * That is the host volume's ownership, not a PHP traceback.
     */
    private function pageSource(\Symfony\Component\Panther\Client $client, string $path): string
    {
        try {
            return $client->getPageSource();
        } catch (UnexpectedAlertOpenException $exception) {
            $text = $exception->getMessage();
            try {
                $alert = $client->getWebDriver()->switchTo()->alert();
                $fromAlert = $alert->getText();
                if ($fromAlert !== '') {
                    $text = $fromAlert;
                }
                $alert->accept();
            } catch (NoSuchAlertException $ignored) {
            }
            if (!str_contains($text, 'dubious ownership')) {
                $this->fail($path.' opened an alert: '.$text);
            }

            return $client->getPageSource();
        }
    }
}
