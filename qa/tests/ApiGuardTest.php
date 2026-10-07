<?php

require_once __DIR__ . '/Support/ApiTestCase.php';
require_once __DIR__ . '/Support/ApiFixtures.php';

/**
 * Each /api/ script with no site cookie and no LTI session.
 */
final class ApiGuardTest extends ApiTestCase
{
    public function testNotificationsRequireLogin(): void
    {
        $response = $this->http()->request('GET', 'api/notifications.php');
        $this->assertHttpStatus($response, 403, 'notifications');
        $this->assertStringContainsString('application/json', $response->header('content-type'), $response->excerpt());
        $json = $this->assertJsonObject($response, 'notifications');
        $this->assertSame('error', $json['status'] ?? null, $response->excerpt());
        $this->assertSame('Not logged in', $json['detail'] ?? null, $response->excerpt());
        $this->assertPageHasNoPhpError($response->body, 'notifications');
    }

    public function testNotificationsPreflight(): void
    {
        $response = $this->http()->request('OPTIONS', 'api/notifications.php');
        $this->assertHttpStatus($response, 200, 'notifications OPTIONS');
        $this->assertSame('*', $response->header('access-control-allow-origin'));
        $this->assertSame('', trim($response->body));
    }

    public function testAnalyticsCookieRequiresLinkBeforeLogin(): void
    {
        $missing = $this->http()->request('GET', 'api/analytics_cookie.php');
        $this->assertHttpStatus($missing, 403, 'analytics_cookie');
        $json = $this->assertJsonObject($missing, 'analytics_cookie');
        $this->assertSame('No link_id', $json['error'] ?? null, $missing->excerpt());
        $this->assertPageHasNoPhpError($missing->body, 'analytics_cookie');

        foreach (['abc', '1.5'] as $bad) {
            $malformed = $this->http()->request('GET', 'api/analytics_cookie.php', [
                'query' => ['link_id' => $bad],
            ]);
            $this->assertHttpStatus($malformed, 403, 'analytics_cookie '.$bad);
            $json = $this->assertJsonObject($malformed, 'analytics_cookie '.$bad);
            $this->assertSame('No link_id', $json['error'] ?? null, $malformed->excerpt());
            $this->assertPageHasNoPhpError($malformed->body, 'analytics_cookie '.$bad);
        }

        $array = $this->http()->request('GET', 'api/analytics_cookie.php', [
            'query' => ['link_id' => ['1']],
        ]);
        $this->assertHttpStatus($array, 403, 'analytics_cookie array');
        $json = $this->assertJsonObject($array, 'analytics_cookie array');
        $this->assertSame('No link_id', $json['error'] ?? null, $array->excerpt());
        $this->assertPageHasNoPhpError($array->body, 'analytics_cookie array');

        $unknown = $this->http()->request('GET', 'api/analytics_cookie.php', [
            'query' => ['link_id' => 1],
        ]);
        $this->assertHttpStatus($unknown, 403, 'analytics_cookie link');
        $json = $this->assertJsonObject($unknown, 'analytics_cookie link');
        $this->assertSame('Not logged in', $json['error'] ?? null, $unknown->excerpt());
    }

    public function testLtiEndpointsRejectAMissingSession(): void
    {
        $paths = [
            'api/analytics.php',
            'api/settings.php',
            'api/socket.php',
            'api/grade-submit.php',
            'api/record-attempt.php',
        ];
        foreach ($paths as $path) {
            $method = $path === 'api/analytics.php' || $path === 'api/socket.php' ? 'GET' : 'POST';
            $response = $this->http()->request($method, $path);
            $this->assertHttpStatus($response, 403, $path);
            $this->assertStringContainsString('application/json', $response->header('content-type'), $path.' '.$response->excerpt());
            $json = $this->assertJsonObject($response, $path);
            $this->assertSame('error', $json['status'] ?? null, $path.' '.$response->excerpt());
            $this->assertPageHasNoPhpError($response->body, $path);
        }
    }

    public function testAnalyticsPreflightFromLocalhost(): void
    {
        $origin = preg_replace('#/tsugi/?$#', '', self::siteBase());
        $response = $this->http()->request('OPTIONS', 'api/analytics.php', [
            'headers' => ['Origin' => $origin],
        ]);
        $this->assertHttpStatus($response, 200, 'analytics OPTIONS');
        $this->assertSame($origin, $response->header('access-control-allow-origin'));
        $this->assertSame('', trim($response->body));
    }

    public function testPoxRejectsNonXml(): void
    {
        $plain = $this->http()->request('POST', 'api/poxresult.php', [
            'headers' => ['Content-Type' => 'text/plain'],
            'body' => 'hello',
        ]);
        $this->assertHttpStatus($plain, 400, 'pox text');
        $this->assertStatusLineContains($plain, 'Must be content type xml', 'pox text');
        $this->assertStringContainsString('Data dump:', $plain->body, $plain->excerpt());
        $this->assertPageHasNoPhpError($plain->body, 'pox text');

        $notXml = $this->http()->request('POST', 'api/poxresult.php', [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => 'not-xml',
        ]);
        $this->assertHttpStatus($notXml, 400, 'pox body');
        $this->assertStatusLineContains($notXml, 'Expecting XML', 'pox body');
    }

    public function testRosterRejectsABadMembershipId(): void
    {
        $missing = $this->http()->request('POST', 'api/ltiextroster.php', [
            'form' => [],
        ]);
        $this->assertHttpStatus($missing, 400, 'roster missing');
        $this->assertStatusLineContains($missing, 'Invalid sourcedid format', 'roster missing');
        $this->assertPageHasNoPhpError($missing->body, 'roster missing');

        $short = $this->http()->request('POST', 'api/ltiextroster.php', [
            'form' => ['id' => '1::2::3'],
        ]);
        $this->assertHttpStatus($short, 400, 'roster short');
        $this->assertStatusLineContains($short, 'Invalid sourcedid format', 'roster short');

        $textSig = $this->http()->request('POST', 'api/ltiextroster.php', [
            'form' => ['id' => '1::2::nope::sig'],
        ]);
        $this->assertHttpStatus($textSig, 400, 'roster numeric');
        $this->assertStatusLineContains($textSig, 'sourcedid requires 4 numeric parameters', 'roster numeric');

        $unknown = $this->http()->request('POST', 'api/ltiextroster.php', [
            'form' => ['id' => '1::2::3::not-a-real-signature'],
        ]);
        $this->assertHttpStatus($unknown, 403, 'roster unknown');
        $this->assertStringContainsString('Could not locate sourcedid row', $unknown->header('x-error-message'), $unknown->excerpt());
    }

    public function testUnknownApiPathIsNotFound(): void
    {
        $response = $this->http()->request('GET', 'api/no-such-endpoint');
        $this->assertHttpStatus($response, 404, 'route');
        $this->assertStringContainsString('Page not found.', $response->body, $response->excerpt());
        $this->assertPageHasNoPhpError($response->body, 'route');
    }

    public function testAnnotateAndStickyGraderRequireASessionPath(): void
    {
        foreach (['api/annotate/', 'api/stickygrader/'] as $path) {
            $missing = $this->http()->request('GET', $path);
            $this->assertHttpStatus($missing, 500, $path);
            $this->assertStringContainsString('Missing Session', $missing->body, $missing->excerpt());
            $this->assertPageHasNoPhpError($missing->body, $path);
        }

        $shape = $this->http()->request('GET', 'api/annotate/not-a-session');
        $this->assertHttpStatus($shape, 500, 'annotate shape');
        $this->assertStringContainsString('Missing user_id', $shape->body, $shape->excerpt());
    }

    public function testRpcRejectsMissingAndBadTokens(): void
    {
        $missing = $this->http()->request('POST', 'api/rpc.php', [
            'form' => [],
        ]);
        $this->assertHttpStatus($missing, 400, 'rpc missing');
        $this->assertStringContainsString('{"detail":"No token"}', $missing->body, $missing->excerpt());

        $prefix = $this->http()->request('POST', 'api/rpc.php', [
            'form' => [
                'token' => ApiFixtures::rpcToken('nope::session'),
            ],
        ]);
        $this->assertHttpStatus($prefix, 400, 'rpc prefix');
        $this->assertStringContainsString('{"detail":"Bad token prefix"}', $prefix->body, $prefix->excerpt());
        $this->assertPageHasNoPhpError($prefix->body, 'rpc prefix');

        $session = $this->http()->request('POST', 'api/rpc.php', [
            'form' => [
                'token' => ApiFixtures::rpcToken(ApiFixtures::dockerCookiePad().'::not-a-real-session'),
            ],
        ]);
        $this->assertHttpStatus($session, 400, 'rpc session');
        $this->assertStringContainsString('{"detail":"Invalid session"}', $session->body, $session->excerpt());
    }
}
