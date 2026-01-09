<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

final class StoreTest extends TsugiPantherTestCase
{
    public function testStoreLoads(): void
    {
        $client = $this->pantherClient();
        $client->request('GET', $this->uri('store/'));

        $page = $client->getPageSource();
        $this->assertStringNotContainsString('No tools found.', $page);
        $this->assertStringContainsString('Grade Unit Test Tool', $page);
        $this->assertStringContainsString('Blob Upload/Download Test Tool', $page);

        $this->captureScreenshot($client, 'store-home');
    }
}
