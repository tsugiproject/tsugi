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

        $this->captureScreenshot($client, 'smoke-homepage');
    }
}
