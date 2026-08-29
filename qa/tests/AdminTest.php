<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

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
}
