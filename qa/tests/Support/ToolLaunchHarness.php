<?php

require_once __DIR__ . '/TsugiPantherTestCase.php';

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

abstract class ToolLaunchHarness extends TsugiPantherTestCase
{
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
        $lastUrl = '';
        do {
            $frame = $this->findLaunchFrame($client);
            if ($frame === null) {
                usleep(200000);
                continue;
            }
            try {
                $driver->switchTo()->frame($frame);
                $normalized = $this->normalizedToolHtml($driver->getPageSource());
                $lastNormalized = $normalized;
                $lastUrl = (string) $driver->getCurrentURL();
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
        $urlHint = $lastUrl === '' ? '[unknown iframe URL]' : $lastUrl;
        $this->fail("Timed out waiting for tool iframe text: '{$expectedLabel}'. Last iframe URL: {$urlHint}. Iframe excerpt: {$excerpt}");
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
