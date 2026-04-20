<?php

require_once __DIR__ . '/Support/ToolLaunchHarness.php';

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
