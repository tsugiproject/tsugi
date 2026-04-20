<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\Exception\StaleElementReferenceException;

final class ToolLaunchTest extends TsugiPantherTestCase
{
    /**
     * Normalize iframe HTML for assertions. WebDriver page source often HTML-entity-encodes
     * text (e.g. " → &quot;, > → &gt;), which breaks regexes written for raw var_dump output.
     */
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

    private function assertToolLaunch(
        string $toolKey,
        string $expectedText,
        array $query = [],
        array $expectedSnippets = [],
        ?string $screenshotName = null
    ): void
    {
        $client = $this->pantherClient();
        $path = 'store/test/' . $toolKey;
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }
        $client->request('GET', $this->uri($path));

        $page = $client->getPageSource();
        $this->assertStringNotContainsString('No tools found.', $page);
        $this->assertStringNotContainsString('Tool registration', $page);

        $driver = $client->getWebDriver();
        $deadline = microtime(true) + 15;
        $lastSource = '';
        $lastNormalized = '';
        do {
            $frame = $this->findLaunchFrame($client);
            if ($frame === null) {
                usleep(200000);
                continue;
            }
            try {
                $driver->switchTo()->frame($frame);
                $source = $driver->getPageSource();
                $driver->switchTo()->defaultContent();

                $lastSource = $source;
                $lastNormalized = $this->normalizedToolHtml($source);

                if (!str_contains($lastNormalized, $expectedText)) {
                    usleep(200000);
                    continue;
                }
                $missingSnippet = false;
                foreach ($expectedSnippets as $snippet) {
                    if (!str_contains($lastNormalized, $snippet)) {
                        $missingSnippet = true;
                        break;
                    }
                }
                if ($missingSnippet) {
                    usleep(200000);
                    continue;
                }
                if ($screenshotName !== null) {
                    $this->captureScreenshot($client, $screenshotName);
                }
                return;
            } catch (StaleElementReferenceException $exception) {
                $driver->switchTo()->defaultContent();
            }
        } while (microtime(true) < $deadline);

        if ($expectedSnippets !== []) {
            $this->fail('Expected tool content to include all required snippets.');
        }
        $this->fail("Expected tool content to contain '{$expectedText}'.");
    }

    public function testGiftToolLaunches(): void
    {
        $this->assertToolLaunch(
            'gift',
            'This quiz has not yet been configured',
            ['identity' => 'instructor'],
            [],
            'tool-gift-instructor'
        );
    }

    public function testPeerGradeToolLaunches(): void
    {
        $this->assertToolLaunch(
            'peer-grade',
            'This assignment is not yet configured',
            ['identity' => 'instructor'],
            [],
            'tool-peer-grade-instructor'
        );
    }

    public function testTdiscusToolLaunches(): void
    {
        $this->assertToolLaunch(
            'tdiscus',
            'Add Thread',
            ['identity' => 'instructor'],
            [],
            'tool-tdiscus-instructor'
        );
    }
}
