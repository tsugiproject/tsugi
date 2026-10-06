<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

final class KeysetTest extends TsugiPantherTestCase
{
    public function testLtiKeysetReturnsJwks(): void
    {
        $url = $this->uri('lti/keyset.php');
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($url, false, $context);
        $this->assertIsString($body, $url);

        $headers = $http_response_header ?? [];
        $status = $headers[0] ?? '';
        $this->assertStringContainsString('200', $status, $url.' '.$body);
        $contentType = '';
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $contentType = $header;
            }
        }
        $this->assertStringContainsString('application/json', $contentType, $url);

        $json = json_decode($body);
        $this->assertIsObject($json, $body);
        $this->assertTrue(isset($json->keys) && is_array($json->keys), $body);
        $this->assertNotEmpty($json->keys, $body);

        foreach ($json->keys as $key) {
            $this->assertIsObject($key);
            $this->assertSame('RSA', $key->kty ?? null);
            $this->assertSame('RS256', $key->alg ?? null);
            $this->assertSame('sig', $key->use ?? null);
            $this->assertIsString($key->e ?? null);
            $this->assertNotSame('', $key->e);
            $this->assertIsString($key->n ?? null);
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key->n);
            $this->assertIsString($key->kid ?? null);
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key->kid);
        }
    }
}
