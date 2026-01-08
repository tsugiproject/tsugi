<?php

use Symfony\Component\Panther\PantherTestCase;

abstract class TsugiPantherTestCase extends PantherTestCase
{
    private static function ensureWebDriverLoaded(): void
    {
        if (class_exists('Facebook\\WebDriver\\Local\\LocalWebDriver')) {
            return;
        }

        $base = dirname(__DIR__, 3) . '/vendor/php-webdriver/webdriver/lib';
        $remote = $base . '/Remote/RemoteWebDriver.php';
        $local = $base . '/Local/LocalWebDriver.php';
        if (is_file($remote)) {
            require_once $remote;
        }
        if (is_file($local)) {
            require_once $local;
        }
    }

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
        self::ensureWebDriverLoaded();
        $base = self::baseUri();

        return self::createPantherClient([
            'base_uri' => $base,
            'external_base_uri' => $base,
            'browser' => self::CHROME,
        ]);
    }
}
