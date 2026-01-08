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
        $input->submit();

        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if (str_contains($client->getPageSource(), 'Administration Console')) {
                break;
            }
            usleep(200000);
        }

        $this->assertStringContainsString(
            'Administration Console',
            $client->getPageSource()
        );

        $this->captureScreenshot($client, 'admin-console');
    }
}
