<?php

require_once __DIR__ . '/Support/ToolLaunchHarness.php';

use Facebook\WebDriver\WebDriverBy;

final class ToolLaunchTest extends ToolLaunchHarness
{

    public function testGiftToolLaunches(): void
    {
        $client = $this->pantherClient();
        $this->launchTool($client, 'gift', ['identity' => 'instructor']);
        $this->waitForAnyFrameText($client, [
            'This quiz has not yet been configured',
            'Submit quiz',
        ]);
        $this->captureScreenshot($client, 'tool-gift-instructor');
    }

    public function testPeerGradeToolLaunches(): void
    {
        $client = $this->pantherClient();

        // Start as learner; either fresh (unconfigured) or already configured is acceptable.
        $this->launchTool($client, 'peer-grade', ['identity' => 'learner1']);
        $this->waitForAnyFrameText($client, [
            'This assignment is not yet configured',
            'Please Upload Your Submission',
        ]);

        // Configure as instructor.
        $this->launchTool($client, 'peer-grade', ['identity' => 'instructor']);
        $this->waitForAnyFrameText($client, ['This assignment is not yet configured', 'Please Upload Your Submission']);
        $this->inLaunchFrame($client, function ($driver): void {
            $driver->executeScript('window.location.href = "configure" + window.location.search;');
        });
        $this->waitForFrameText($client, 'Assignment Title');
        $this->inLaunchFrame($client, function ($driver): void {
            $titleInput = $this->findFirst($driver, [
                WebDriverBy::name('title'),
                WebDriverBy::cssSelector('input[name="title"]'),
            ]);
            $titleInput->clear();
            $titleInput->sendKeys('Panther Launch Setup ' . date('YmdHis'));

            // configure.php handles save only when save_settings is posted.
            $driver->executeScript(
                'var form = document.querySelector("form[method=\'post\']");
                 var save = document.querySelector("input[name=\"save_settings\"]");
                 if (form && save) {
                    if (form.requestSubmit) {
                        form.requestSubmit(save);
                    } else {
                        var hidden = document.createElement("input");
                        hidden.type = "hidden";
                        hidden.name = "save_settings";
                        hidden.value = save.value || "Save";
                        form.appendChild(hidden);
                        form.submit();
                    }
                 }'
            );
        });

        // Re-launch as learner; assignment should now be configured.
        $this->launchTool($client, 'peer-grade', ['identity' => 'learner1']);
        $this->waitForFrameText($client, 'Please Upload Your Submission');
        $this->inLaunchFrame($client, function ($driver): void {
            $html = html_entity_decode($driver->getPageSource(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertStringNotContainsString('This assignment is not yet configured', $html);
        });
        $this->captureScreenshot($client, 'tool-peer-grade-learner');
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
