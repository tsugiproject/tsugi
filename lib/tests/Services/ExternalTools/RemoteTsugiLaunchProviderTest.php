<?php

namespace Tsugi\Tests\Services\ExternalTools;

use PHPUnit\Framework\TestCase;
use Tsugi\Services\ExternalTools\RemoteTsugiLaunchProvider;

class RemoteTsugiLaunchProviderTest extends TestCase
{
    public function testBuildRemovesEventNonceAndAddsCallback(): void
    {
        $provider = new RemoteTsugiLaunchProvider();
        $launch = $provider->build(
            array(
                'user_id' => 42,
                'context_id' => 7,
                'event_nonce' => 'nonce-to-drop',
            ),
            'https://remote.example/launch',
            'https://tsugi.example/api/rpc.php?PHPSESSID=abc123',
            'encrypted-token'
        );

        $this->assertSame('https://remote.example/launch', $launch->launch_url);
        $this->assertSame(42, $launch->jwt_claim['lti']['user_id']);
        $this->assertSame(7, $launch->jwt_claim['lti']['context_id']);
        $this->assertArrayNotHasKey('event_nonce', $launch->jwt_claim['lti']);
        $this->assertSame(
            'https://tsugi.example/api/rpc.php?PHPSESSID=abc123',
            $launch->jwt_claim['callback']['endpoint']
        );
        $this->assertSame('encrypted-token', $launch->jwt_claim['callback']['token']);
        $this->assertSame(array('button' => 'Go'), $launch->extra);
    }

    public function testBuildEnablesDebugModeWhenRequested(): void
    {
        $provider = new RemoteTsugiLaunchProvider();
        $launch = $provider->build(
            array('user_id' => 42),
            'https://remote.example/launch?debug=true',
            'https://tsugi.example/api/rpc.php',
            'encrypted-token'
        );

        $this->assertTrue($launch->debug);
    }
}
