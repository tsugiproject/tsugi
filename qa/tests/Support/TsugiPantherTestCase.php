<?php

use Symfony\Component\Panther\PantherTestCase;

abstract class TsugiPantherTestCase extends PantherTestCase
{
    protected function captureScreenshot(\Symfony\Component\Panther\Client $client, string $name): void
    {
        $screenshotDir = dirname(__DIR__, 2) . '/screenshots';
        if (!is_dir($screenshotDir)) {
            mkdir($screenshotDir, 0775, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $name);
        if ($safeName === null || $safeName === '') {
            $safeName = 'screenshot';
        }

        $client->takeScreenshot($screenshotDir . '/' . $safeName . '.png');
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
        self::preferProjectChromeDriver();
        $base = self::baseUri();

        return self::createPantherClient([
            'base_uri' => $base,
            'external_base_uri' => $base,
            'browser' => self::CHROME,
        ]);
    }

    /**
     * Panther searches PATH before ./drivers. A stale system chromedriver
     * (Homebrew) would otherwise win over the copy bdi installed for this Chrome.
     */
    private static function preferProjectChromeDriver(): void
    {
        $drivers = dirname(__DIR__, 3) . '/drivers';
        $binary = $drivers . '/chromedriver';
        if (!is_executable($binary)) {
            return;
        }

        $path = getenv('PATH');
        if ($path === false) {
            $path = '';
        }
        $first = explode(PATH_SEPARATOR, $path, 2)[0];
        if ($first === $drivers) {
            return;
        }

        putenv('PATH=' . $drivers . PATH_SEPARATOR . $path);
    }
}
