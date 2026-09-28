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
        $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($passphrase);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
        $this->waitForAdminText($client, 'Administration Console');

        foreach ($this->adminSmokePages() as $path => $marker) {
            $driver->get($this->uri($path));
            $page = $this->waitForAdminText($client, $marker, $path);
            $this->assertNoAdminTraceback($page, $path);
        }

        $this->captureScreenshot($client, 'admin-smoke');
    }

    /**
     * GET-only admin screens. Skips actions that delete, send mail, or migrate.
     *
     * @return array<string, string>
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
            'admin/context/' => 'members',
            'admin/activity/' => 'link_title',
            'admin/badges/' => 'Badges Awarded',
            'admin/users/' => 'displayname',
            'admin/profile/' => 'premium',
            'admin/recent' => 'Ipaddr',
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

    private function waitForAdminText(\Symfony\Component\Panther\Client $client, string $marker, string $path = 'admin/'): string
    {
        $deadline = microtime(true) + 20;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $this->pageSource($client, $path);
            if (str_contains($page, $marker)) {
                return $page;
            }
            usleep(200000);
        }

        $this->captureScreenshot($client, 'debug-admin-smoke');
        $excerpt = substr(preg_replace('/\s+/u', ' ', $page) ?? $page, 0, 300);
        $this->fail($path.' did not show "'.$marker.'". '.$excerpt);
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

    private function assertNoAdminTraceback(string $page, string $path): void
    {
        $needles = [
            'Fatal error',
            'Uncaught ',
            'Stack trace:',
            'SQLSTATE[',
            'Parse error',
            'Warning:',
            'Notice:',
            'Deprecated:',
            'Failure connecting to the database',
        ];
        foreach ($needles as $needle) {
            $this->assertStringNotContainsString($needle, $page, $path.' contained '.$needle);
        }
    }
}
