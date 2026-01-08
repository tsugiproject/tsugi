<?php

use Symfony\Component\Panther\PantherTestCase;

abstract class TsugiPantherTestCase extends PantherTestCase
{
    protected static function baseUri(): string
    {
        $base = getenv('TSUGI_BASE_URL');
        if ($base === false || $base === '') {
            $base = 'http://localhost:8888/tsugi';
        }

        return rtrim($base, '/');
    }

    protected function uri(string $path): string
    {
        $trimmed = ltrim($path, '/');
        if ($trimmed === '') {
            return self::baseUri() . '/';
        }

        return self::baseUri() . '/' . $trimmed;
    }

    protected function pantherClient(): \Symfony\Component\Panther\Client
    {
        $base = self::baseUri();

        return self::createPantherClient([
            'base_uri' => $base,
            'external_base_uri' => $base,
            'browser' => self::CHROME,
        ]);
    }
}
