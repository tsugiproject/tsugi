<?php

require_once __DIR__ . '/TsugiPantherTestCase.php';

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

abstract class ToolLaunchHarness extends TsugiPantherTestCase
{
    private const TOOL_ERROR_SNIPPETS = [
        'Unhandled POST request',
        'Failure connecting to the database, see error log',
    ];

    protected function captureStep(\Symfony\Component\Panther\Client $client, string $flow, string $step): void
    {
        $this->captureScreenshot($client, $flow . '-' . $step);
    }

    protected function launchTool(\Symfony\Component\Panther\Client $client, string $toolKey, array $query = []): void
    {
        $path = 'store/test/' . $toolKey;
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }
        $client->request('GET', $this->uri($path));

        $page = $client->getPageSource();
        $this->assertStringNotContainsString('No tools found.', $page);
        $this->assertStringNotContainsString('Tool registration', $page);
    }

    protected function assertToolLaunch(
        string $toolKey,
        string $expectedText,
        array $query = [],
        array $expectedSnippets = [],
        ?string $screenshotName = null
    ): void {
        $client = $this->pantherClient();
        $this->launchTool($client, $toolKey, $query);
        $this->waitForFrameText($client, $expectedText);
        foreach ($expectedSnippets as $snippet) {
            $this->waitForFrameText($client, $snippet);
        }
        if ($screenshotName !== null) {
            $this->captureScreenshot($client, $screenshotName);
        }
    }

    protected function waitForFrameText(\Symfony\Component\Panther\Client $client, string $expectedText, int $timeoutSeconds = 15): void
    {
        $texts = [$expectedText];
        $this->waitForAnyFrameText($client, $texts, $timeoutSeconds);
    }

    protected function waitForAnyFrameText(\Symfony\Component\Panther\Client $client, array $expectedTexts, int $timeoutSeconds = 15): void
    {
        $driver = $client->getWebDriver();
        $deadline = microtime(true) + $timeoutSeconds;
        $lastNormalized = '';
        $lastFrameText = '';
        $lastUrl = '';
        $lastTopUrl = '';
        $lastTopTitle = '';
        $lastTopNormalized = '';
        $lastFrameCount = 0;
        $lastFrameSources = [];
        do {
            $driver->switchTo()->defaultContent();
            $lastTopUrl = (string) $driver->getCurrentURL();
            $lastTopTitle = (string) $driver->getTitle();
            $lastTopNormalized = $this->normalizedToolHtml($driver->getPageSource());
            $frames = $driver->findElements(WebDriverBy::tagName('iframe'));
            $lastFrameCount = count($frames);
            $lastFrameSources = [];
            $maxSources = min(3, $lastFrameCount);
            for ($i = 0; $i < $maxSources; $i++) {
                $src = (string) $frames[$i]->getAttribute('src');
                if ($src === '') {
                    $src = '[no src attribute]';
                }
                $lastFrameSources[] = $src;
            }

            $frame = $this->findLaunchFrame($client);
            if ($frame === null) {
                usleep(200000);
                continue;
            }
            try {
                $driver->switchTo()->frame($frame);
                $normalized = $this->normalizedToolHtml($driver->getPageSource());
                $lastNormalized = $normalized;
                $lastFrameText = trim((string) $driver->executeScript('return document.body ? (document.body.innerText || "") : "";'));
                $lastUrl = (string) $driver->getCurrentURL();
                $this->assertNoKnownToolErrors($normalized, $lastUrl);
                $driver->switchTo()->defaultContent();
                foreach ($expectedTexts as $expectedText) {
                    if (str_contains($normalized, $expectedText)) {
                        return;
                    }
                }
            } catch (StaleElementReferenceException $exception) {
                $driver->switchTo()->defaultContent();
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        $expectedLabel = implode("' OR '", $expectedTexts);
        $excerpt = $lastNormalized === '' ? '[no iframe HTML captured]' : substr(preg_replace('/\s+/u', ' ', $lastNormalized) ?? $lastNormalized, 0, 400);
        $frameTextExcerpt = $lastFrameText === '' ? '[no iframe text captured]' : substr(preg_replace('/\s+/u', ' ', $lastFrameText) ?? $lastFrameText, 0, 400);
        $urlHint = $lastUrl === '' ? '[unknown iframe URL]' : $lastUrl;
        $topUrlHint = $lastTopUrl === '' ? '[unknown top URL]' : $lastTopUrl;
        $topTitleHint = $lastTopTitle === '' ? '[unknown top title]' : $lastTopTitle;
        $topExcerpt = $lastTopNormalized === '' ? '[no top-page HTML captured]' : substr(preg_replace('/\s+/u', ' ', $lastTopNormalized) ?? $lastTopNormalized, 0, 300);
        $frameSourceHint = $lastFrameSources === [] ? '[no iframe src values]' : implode(', ', $lastFrameSources);
        $debugShot = 'debug-timeout-' . date('Ymd-His');
        $this->captureScreenshot($client, $debugShot);
        $this->fail(
            "Timed out waiting for tool iframe text: '{$expectedLabel}'. " .
            "Last iframe URL: {$urlHint}. Iframe HTML excerpt: {$excerpt}. Iframe text excerpt: {$frameTextExcerpt}. " .
            "Top URL: {$topUrlHint}. Top title: {$topTitleHint}. Top HTML excerpt: {$topExcerpt}. " .
            "Visible iframe count: {$lastFrameCount}. First iframe src values: {$frameSourceHint}. " .
            "Debug screenshot: qa/screenshots/{$debugShot}.png"
        );
    }

    protected function inLaunchFrame(
        \Symfony\Component\Panther\Client $client,
        callable $callback,
        int $timeoutSeconds = 15
    ): void {
        $driver = $client->getWebDriver();
        $deadline = microtime(true) + $timeoutSeconds;
        do {
            $frame = $this->findLaunchFrame($client);
            if ($frame === null) {
                usleep(200000);
                continue;
            }
            try {
                $driver->switchTo()->frame($frame);
                $callback($driver);
                $driver->switchTo()->defaultContent();
                return;
            } catch (StaleElementReferenceException $exception) {
                $driver->switchTo()->defaultContent();
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        $this->fail('Timed out while trying to interact with tool iframe.');
    }

    protected function clickFirst(RemoteWebDriver $driver, array $locators): void
    {
        foreach ($locators as $locator) {
            try {
                $driver->findElement($locator)->click();
                return;
            } catch (NoSuchElementException $exception) {
                continue;
            }
        }
        $this->fail('Could not find clickable target for provided locators.');
    }

    protected function findFirst(RemoteWebDriver $driver, array $locators): WebDriverElement
    {
        foreach ($locators as $locator) {
            try {
                return $driver->findElement($locator);
            } catch (NoSuchElementException $exception) {
                continue;
            }
        }
        $this->fail('Could not find element for provided locators.');
    }

    protected function waitForFrameElement(
        \Symfony\Component\Panther\Client $client,
        array $locators,
        int $timeoutSeconds = 15
    ): void {
        $driver = $client->getWebDriver();
        $deadline = microtime(true) + $timeoutSeconds;
        do {
            $frame = $this->findLaunchFrame($client);
            if ($frame === null) {
                usleep(200000);
                continue;
            }
            try {
                $driver->switchTo()->frame($frame);
                foreach ($locators as $locator) {
                    $els = $driver->findElements($locator);
                    if (count($els) > 0) {
                        $driver->switchTo()->defaultContent();
                        return;
                    }
                }
                $driver->switchTo()->defaultContent();
            } catch (StaleElementReferenceException $exception) {
                $driver->switchTo()->defaultContent();
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        $this->fail('Timed out waiting for expected element in tool iframe.');
    }

    private function normalizedToolHtml(string $source): string
    {
        return html_entity_decode($source, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function assertNoKnownToolErrors(string $normalizedHtml, string $currentUrl): void
    {
        foreach (self::TOOL_ERROR_SNIPPETS as $snippet) {
            if (!str_contains($normalizedHtml, $snippet)) {
                continue;
            }

            $excerpt = substr(preg_replace('/\s+/u', ' ', $normalizedHtml) ?? $normalizedHtml, 0, 400);
            $urlHint = $currentUrl === '' ? '[unknown iframe URL]' : $currentUrl;
            $this->fail("Tool iframe shows known error '{$snippet}' at {$urlHint}. Iframe excerpt: {$excerpt}");
        }
    }

    private function findLaunchFrame(\Symfony\Component\Panther\Client $client): ?WebDriverElement
    {
        $driver = $client->getWebDriver();
        $frames = $driver->findElements(WebDriverBy::cssSelector('iframe.lti_frameResize'));
        if (count($frames) > 0) {
            return $frames[0];
        }
        $frames = $driver->findElements(WebDriverBy::tagName('iframe'));
        if (count($frames) > 0) {
            return $frames[0];
        }
        return null;
    }
}
