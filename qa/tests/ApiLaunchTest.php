<?php

require_once __DIR__ . '/Support/ApiTestCase.php';
require_once __DIR__ . '/Support/ApiFixtures.php';

/**
 * LTI session from one store Try It launch, reused across the launch-scoped APIs.
 */
final class ApiLaunchTest extends ApiTestCase
{
    public function testAnalyticsAndSocketUseTheLaunchSession(): void
    {
        $launch = ApiFixtures::giftLaunch($this);
        $http = $this->http();

        $analytics = $http->request('GET', 'api/analytics.php', [
            'query' => $launch->query(),
        ]);
        $this->assertHttpStatus($analytics, 200, 'analytics');
        $this->assertStringContainsString('application/json', $analytics->header('content-type'), $analytics->excerpt());
        $json = $this->assertJsonObject($analytics, 'analytics');
        foreach (['rows', 'n', 'width', 'timestart'] as $key) {
            $this->assertArrayHasKey($key, $json, $analytics->excerpt());
        }
        $this->assertIsArray($json['rows'], $analytics->excerpt());
        $this->assertPageHasNoPhpError($analytics->body, 'analytics');

        $socket = $http->request('GET', 'api/socket.php', [
            'query' => $launch->query(),
        ]);
        $this->assertHttpStatus($socket, 200, 'socket');
        $rows = json_decode($socket->body, true);
        $this->assertIsArray($rows, $socket->excerpt());
        $this->assertPageHasNoPhpError($socket->body, 'socket');
    }

    public function testSettingsRequireTheLaunchCsrfToken(): void
    {
        $launch = ApiFixtures::giftLaunch($this);
        $http = $this->http();

        $missing = $http->request('POST', 'api/settings.php', [
            'query' => $launch->query(),
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ]);
        $this->assertHttpStatus($missing, 403, 'settings missing csrf');
        $json = $this->assertJsonObject($missing, 'settings missing csrf');
        $this->assertSame('Missing or invalid CSRF token', $json['error'] ?? null, $missing->excerpt());

        $this->assertNotSame('', $launch->csrf, 'Gift launch page did not include CSRF_TOKEN.');
        $saved = $http->request('POST', 'api/settings.php', [
            'query' => $launch->query(),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-CSRF-TOKEN' => $launch->csrf,
            ],
            'body' => '{}',
        ]);
        $this->assertHttpStatus($saved, 200, 'settings');
        $this->assertSame('{}', trim($saved->body), $saved->excerpt());
        $this->assertPageHasNoPhpError($saved->body, 'settings');
    }

    public function testGradeSubmitAndRecordAttemptStopWithoutABudget(): void
    {
        $launch = ApiFixtures::giftLaunch($this);
        $http = $this->http();

        $grade = $http->request('POST', 'api/grade-submit.php', [
            'query' => $launch->query(),
            'form' => ['grade' => '1'],
        ]);
        $this->assertHttpStatus($grade, 200, 'grade-submit');
        $json = $this->assertJsonObject($grade, 'grade-submit');
        $this->assertSame('failure', $json['status'] ?? null, $grade->excerpt());
        $this->assertSame('Missing GSRF token', $json['detail'] ?? null, $grade->excerpt());
        $this->assertPageHasNoPhpError($grade->body, 'grade-submit');

        $attempt = $http->request('POST', 'api/record-attempt.php', [
            'query' => $launch->query(),
        ]);
        $this->assertHttpStatus($attempt, 200, 'record-attempt');
        $json = $this->assertJsonObject($attempt, 'record-attempt');
        $this->assertSame('failure', $json['status'] ?? null, $attempt->excerpt());
        $this->assertSame('Missing RECORD_ATTEMPT_GSRF token', $json['detail'] ?? null, $attempt->excerpt());
        $this->assertPageHasNoPhpError($attempt->body, 'record-attempt');
    }

    public function testAnnotateAndStickyGraderRejectAToolSession(): void
    {
        // The navigation check exempts a script directory of exactly "api".
        // These two live in subdirectories, so a tool/gift session is refused.
        // The refusal clears that session, so each call gets its own launch.
        $http = $this->http();
        foreach (['api/annotate/', 'api/stickygrader/'] as $prefix) {
            $launch = ApiFixtures::giftLaunch($this, true);
            $response = $http->request('GET', $prefix.rawurlencode($launch->sessionId).':1');
            $this->assertHttpStatus($response, 400, $prefix);
            $this->assertStringContainsString('Improper navigation detected', $response->body, $response->excerpt());
            $this->assertPageHasNoPhpError($response->body, $prefix);
        }
    }

    public function testAnalyticsCookieAllowsAdminAndRefusesTheStudent(): void
    {
        $launch = ApiFixtures::giftLaunch($this);

        $instructor = ApiFixtures::instructor($this);
        ApiFixtures::unlockAdmin($this, $instructor);
        $allowed = $instructor->request('GET', 'api/analytics_cookie.php', [
            'query' => ['link_id' => $launch->linkId],
        ]);
        $this->assertHttpStatus($allowed, 200, 'analytics_cookie admin');
        $json = $this->assertJsonObject($allowed, 'analytics_cookie admin');
        foreach (['rows', 'n', 'width', 'timestart'] as $key) {
            $this->assertArrayHasKey($key, $json, $allowed->excerpt());
        }
        $this->assertPageHasNoPhpError($allowed->body, 'analytics_cookie admin');

        $student = ApiFixtures::student($this);
        $refused = $student->request('GET', 'api/analytics_cookie.php', [
            'query' => ['link_id' => $launch->linkId],
        ]);
        $this->assertHttpStatus($refused, 403, 'analytics_cookie student');
        $json = $this->assertJsonObject($refused, 'analytics_cookie student');
        $this->assertSame('Not authorized', $json['error'] ?? null, $refused->excerpt());
        $this->assertPageHasNoPhpError($refused->body, 'analytics_cookie student');
    }
}
