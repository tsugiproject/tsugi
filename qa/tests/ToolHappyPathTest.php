<?php

require_once __DIR__ . '/Support/ToolLaunchHarness.php';

use Facebook\WebDriver\WebDriverBy;

final class ToolHappyPathTest extends ToolLaunchHarness
{
    public function testGiftHappyPathConfigureAndRenderQuiz(): void
    {
        $client = $this->pantherClient();
        $this->launchTool($client, 'gift', ['identity' => 'instructor']);
        $this->captureStep($client, 'gift-happy', '00-launched');
        $this->waitForAnyFrameText($client, [
            'This quiz has not yet been configured',
            'Submit quiz',
        ]);

        $giftText = "::Q1::2 + 2 = {=4 ~3}\n";

        $this->inLaunchFrame($client, function ($driver): void {
            // Keep launch/session query parameters when moving to sibling routes.
            $driver->executeScript('window.location.href = "old_configure.php" + window.location.search;');
        });
        $this->waitForFrameText($client, 'The assignment is configured by carefully editing the gift below');
        $this->captureStep($client, 'gift-happy', '02-configure-open');

        $this->inLaunchFrame($client, function ($driver) use ($giftText): void {
            $textarea = $this->findFirst($driver, [
                WebDriverBy::name('gift'),
                WebDriverBy::cssSelector('textarea[name="gift"]'),
                WebDriverBy::tagName('textarea'),
            ]);
            $textarea->clear();
            $textarea->sendKeys($giftText);
            // Submit via JS to avoid clickability differences across themes/layouts.
            $driver->executeScript('document.querySelector("form[method=\'post\']").submit();');
        });

        $this->waitForFrameText($client, 'Quiz updated');
        $this->waitForFrameText($client, 'Submit quiz');
        $this->captureStep($client, 'gift-happy', '03-configured');
        $this->captureScreenshot($client, 'tool-gift-happy-path');
    }

    public function testPeerGradeHappyPathConfigureAssignment(): void
    {
        $client = $this->pantherClient();
        $this->launchTool($client, 'peer-grade', ['identity' => 'instructor']);
        $this->captureStep($client, 'peer-grade-happy', '00-launched');
        $this->waitForAnyFrameText($client, [
            'This assignment is not yet configured',
            'Please Upload Your Submission',
        ]);

        $title = 'Panther Peer Grade ' . date('YmdHis');

        $this->inLaunchFrame($client, function ($driver): void {
            // Keep launch/session query parameters when moving to sibling routes.
            $driver->executeScript('window.location.href = "configure" + window.location.search;');
        });
        $this->waitForFrameText($client, 'Assignment Title');
        $this->captureStep($client, 'peer-grade-happy', '02-configure-open');

        $this->inLaunchFrame($client, function ($driver) use ($title): void {
            $titleInput = $this->findFirst($driver, [
                WebDriverBy::name('title'),
                WebDriverBy::cssSelector('input[name="title"]'),
            ]);
            $titleInput->clear();
            $titleInput->sendKeys($title);
            // peer-grade/configure.php handles save only when save_settings is posted.
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

        $this->waitForAnyFrameText($client, [
            'Please Upload Your Submission',
            $title,
        ], 20);
        $this->captureStep($client, 'peer-grade-happy', '03-configured');
        $this->captureScreenshot($client, 'tool-peer-grade-happy-path');
    }

    public function testTdiscusHappyPathCreateThread(): void
    {
        $client = $this->pantherClient();
        $this->launchTool($client, 'tdiscus', ['identity' => 'instructor']);
        $this->captureStep($client, 'tdiscus-happy', '00-launched');
        $this->waitForFrameText($client, 'Add Thread');

        $threadTitle = 'Panther thread ' . date('YmdHis');

        $this->inLaunchFrame($client, function ($driver): void {
            // Direct route avoids occasional nav click timing/layout issues.
            $driver->executeScript('window.location.href = "threadform" + window.location.search;');
        });
        $this->waitForFrameText($client, 'New Thread');
        $this->captureStep($client, 'tdiscus-happy', '02-threadform-open');

        $this->inLaunchFrame($client, function ($driver) use ($threadTitle): void {
            // Build and submit a synthetic POST form so title/body are always present,
            // independent of CKEditor initialization timing.
            $driver->executeScript(
                'var tokenEl = document.querySelector("input[name=\"_LTI_TSUGI\"]");
                 var token = tokenEl ? tokenEl.value : "";
                 var form = document.createElement("form");
                 form.method = "post";
                 form.action = window.location.pathname + window.location.search;
                 function add(name, value) {
                    var input = document.createElement("input");
                    input.type = "hidden";
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                 }
                 add("_LTI_TSUGI", token);
                 add("title", arguments[0]);
                 add("body", arguments[1]);
                 document.body.appendChild(form);
                 form.submit();',
                [$threadTitle, 'Thread created by Panther happy path test.']
            );
        });

        $this->waitForAnyFrameText($client, [
            $threadTitle,
            'Title and body are required',
        ], 20);
        $this->inLaunchFrame($client, function ($driver) use ($threadTitle): void {
            $html = html_entity_decode($driver->getPageSource(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertStringNotContainsString('Title and body are required', $html);
            $this->assertStringContainsString($threadTitle, $html);
        });
        $this->captureStep($client, 'tdiscus-happy', '03-thread-created');
        $this->captureScreenshot($client, 'tool-tdiscus-happy-path');
    }
}
