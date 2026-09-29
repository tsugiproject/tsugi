<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Panther\PantherTestCase;

require_once __DIR__ . '/ScanningWebDriver.php';

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

        $client = self::createPantherClient([
            'base_uri' => $base,
            'external_base_uri' => $base,
            'browser' => self::CHROME,
        ]);
        $client->start();
        $this->installPageScan($client);

        return $client;
    }

    /**
     * PHP display_errors output, not the word "Warning:" in course settings.
     */
    public function assertPageHasNoPhpError(string $html, string $where = ''): void
    {
        $patterns = [
            '/<b>(?:Warning|Notice|Deprecated|Fatal error|Parse error)<\/b>:/i',
            '/(?:Fatal error|Parse error|Warning|Notice|Deprecated):[^\n]{0,400}\bon line \d+/i',
            '/Uncaught (?:[\w\\\\]+)*(?:Exception|Error)\b/',
            '/Stack trace:/',
            '/SQLSTATE\[/',
            '/Failure connecting to the database/',
        ];
        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $hit = $match[0][0];
            $at = (int) $match[0][1];
            $excerpt = substr($html, max(0, $at - 60), 220);
            $excerpt = preg_replace('/\s+/u', ' ', $excerpt ?? '');
            $place = $where !== '' ? $where : 'the returned page';
            $this->fail('PHP error on '.$place.': '.$hit.' … '.$excerpt);
        }
    }

    private function installPageScan(\Symfony\Component\Panther\Client $client): void
    {
        $property = new \ReflectionProperty($client, 'webDriver');
        $inner = $property->getValue($client);
        if (!$inner instanceof RemoteWebDriver) {
            return;
        }
        $property->setValue($client, new ScanningWebDriver($inner, $this));
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
