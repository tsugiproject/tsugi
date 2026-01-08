<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

final class SmokeTest extends TsugiPantherTestCase
{
    public function testHomePageLoads(): void
    {
        $client = $this->pantherClient();
        $client->request('GET', $this->uri('/'));

        $this->assertStringContainsString(
            'Hello and welcome',
            $client->getPageSource()
        );

        $screenshotDir = __DIR__ . '/../screenshots';
        if (!is_dir($screenshotDir)) {
            mkdir($screenshotDir, 0775, true);
        }

        $client->takeScreenshot($screenshotDir . '/homepage.png');
    }
}
