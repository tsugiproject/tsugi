<?php

require_once __DIR__ . '/Support/TsugiPantherTestCase.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\Exception\StaleElementReferenceException;

final class ToolLaunchTest extends TsugiPantherTestCase
{
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
        ?string $rolePattern = null,
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

                if (!str_contains($source, $expectedText)) {
                    usleep(200000);
                    continue;
                }
                if ($rolePattern !== null && !preg_match($rolePattern, $source)) {
                    usleep(200000);
                    continue;
                }
                $missingSnippet = false;
                foreach ($expectedSnippets as $snippet) {
                    if (!str_contains($source, $snippet)) {
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

        if ($rolePattern !== null) {
            $this->fail("Expected tool content to match role pattern {$rolePattern}.");
        }
        if ($expectedSnippets !== []) {
            $this->fail('Expected tool content to include all required snippets.');
        }
        $this->fail("Expected tool content to contain '{$expectedText}'.");
    }

    public function testGradeToolLaunches(): void
    {
        $this->assertToolLaunch(
            'grade',
            'Grade Test Harness',
            ['identity' => 'instructor'],
            '/\\[\"instructor\"\\]=&gt;\\s*bool\\(true\\)/',
            [],
            'tool-grade-instructor'
        );
    }

    public function testBlobToolLaunches(): void
    {
        $this->assertToolLaunch(
            'blob',
            'Upload File',
            ['identity' => 'instructor'],
            null,
            [],
            'tool-blob-instructor'
        );
    }

    public function testGradeToolLaunchesAsLearner(): void
    {
        $this->assertToolLaunch(
            'grade',
            'Grade Test Harness',
            ['identity' => 'learner1'],
            '/\\[\"instructor\"\\]=&gt;\\s*bool\\(false\\)/',
            [],
            'tool-grade-learner'
        );
    }

    public function testGradeToolHonorsRoleOverride(): void
    {
        $this->assertToolLaunch(
            'grade',
            'Grade Test Harness',
            [
                'identity' => 'learner1',
                'roles' => 'Instructor,Administrator',
            ],
            '/\\[\"instructor\"\\]=&gt;\\s*bool\\(true\\)/',
            [],
            'tool-grade-role-override'
        );
    }

    public function testBlobToolRejectsLearner(): void
    {
        $this->assertToolLaunch(
            'blob',
            'Must be instructor',
            ['identity' => 'learner1'],
            null,
            [],
            'tool-blob-learner-reject'
        );
    }

    public function testGradeToolHonorsOverrides(): void
    {
        $this->assertToolLaunch(
            'grade',
            'Grade Test Harness',
            [
                'identity' => 'instructor',
                'context_title' => 'QA Course',
                'context_label' => 'QA101',
                'context_type' => 'CourseSection',
                'resource_link_title' => 'QA Link',
                'resource_link_description' => 'QA Link Description',
                'custom_qa_param' => 'qa-value',
                'lis_outcome_service_url' => 'https://example.com/outcomes',
                'lis_result_sourcedid' => 'qa-sourcedid',
                'ext_memberships_url' => 'https://example.com/memberships',
                'ext_memberships_id' => 'qa-memberships',
            ],
            '/\\[\"instructor\"\\]=&gt;\\s*bool\\(true\\)/',
            [
                'QA Course',
                'QA101',
                'CourseSection',
                'QA Link',
                'QA Link Description',
                'custom_qa_param',
                'qa-value',
                'https://example.com/outcomes',
                'qa-sourcedid',
                'https://example.com/memberships',
                'qa-memberships',
            ],
            'tool-grade-overrides'
        );
    }
}
