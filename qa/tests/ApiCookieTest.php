<?php

require_once __DIR__ . '/Support/ApiTestCase.php';
require_once __DIR__ . '/Support/ApiFixtures.php';

/**
 * Cookie-session /api/ calls after /login/simulate.
 */
final class ApiCookieTest extends ApiTestCase
{
    public function testNotificationsForALoggedInInstructor(): void
    {
        $http = ApiFixtures::instructor($this);
        $response = $http->request('GET', 'api/notifications.php');
        $this->assertHttpStatus($response, 200, 'notifications');
        $this->assertStringContainsString('application/json', $response->header('content-type'), $response->excerpt());
        $json = $this->assertJsonObject($response, 'notifications');
        $this->assertSame('success', $json['status'] ?? null, $response->excerpt());
        foreach (['notifications', 'announcements'] as $list) {
            $this->assertIsArray($json[$list] ?? null, $list.' '.$response->excerpt());
        }
        foreach (['unread_notification_count', 'unread_announcement_count', 'total_unread'] as $count) {
            $this->assertIsInt($json[$count] ?? null, $count.' '.$response->excerpt());
        }
        $this->assertSame(
            $json['unread_notification_count'] + $json['unread_announcement_count'],
            $json['total_unread'],
            $response->excerpt()
        );
        $this->assertPageHasNoPhpError($response->body, 'notifications');
    }

    public function testAnalyticsCookieRejectsAMissingOrUnknownLink(): void
    {
        $http = ApiFixtures::instructor($this);

        $missing = $http->request('GET', 'api/analytics_cookie.php');
        $this->assertHttpStatus($missing, 403, 'analytics_cookie missing');
        $json = $this->assertJsonObject($missing, 'analytics_cookie missing');
        $this->assertSame('No link_id', $json['error'] ?? null, $missing->excerpt());

        $unknown = $http->request('GET', 'api/analytics_cookie.php', [
            'query' => ['link_id' => 999999999],
        ]);
        $this->assertHttpStatus($unknown, 403, 'analytics_cookie unknown');
        $json = $this->assertJsonObject($unknown, 'analytics_cookie unknown');
        $this->assertSame('Invalid link_id', $json['error'] ?? null, $unknown->excerpt());
        $this->assertPageHasNoPhpError($unknown->body, 'analytics_cookie unknown');
    }
}
