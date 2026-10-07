<?php

require_once __DIR__ . '/TsugiPantherTestCase.php';
require_once __DIR__ . '/ApiHttp.php';

abstract class ApiTestCase extends TsugiPantherTestCase
{
    public static function siteBase(): string
    {
        return self::baseUri();
    }

    protected function http(): ApiHttp
    {
        return new ApiHttp(self::siteBase());
    }

    public function assertHttpStatus(ApiResponse $response, int $status, string $where): void
    {
        $this->assertSame(
            $status,
            $response->status,
            $where.' '.$response->statusLine.' '.$response->excerpt()
        );
    }

    protected function assertStatusLineContains(ApiResponse $response, string $needle, string $where): void
    {
        $this->assertStringContainsString(
            $needle,
            $response->statusLine,
            $where.' '.$response->statusLine.' '.$response->excerpt()
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function assertJsonObject(ApiResponse $response, string $where): array
    {
        $decoded = json_decode($response->body, true);
        $this->assertIsArray($decoded, $where.' '.$response->excerpt());
        return $decoded;
    }

    public function csrfToken(string $html): string
    {
        if (preg_match('/name="CSRF_TOKEN" value="([^"]+)"/', $html, $match)) {
            return $match[1];
        }
        if (preg_match('/CSRF_TOKEN = "([a-f0-9]+)"/', $html, $match)) {
            return $match[1];
        }
        return '';
    }
}
