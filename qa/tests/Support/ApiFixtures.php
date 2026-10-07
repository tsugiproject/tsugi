<?php

require_once __DIR__ . '/ApiHttp.php';
require_once __DIR__ . '/ApiTestCase.php';

use Tsugi\Crypt\AesCtr;

/**
 * Site login (cookie) and one store Try It launch (cookieless LTI session).
 */
final class ApiFixtures
{
    private static ?ApiLaunchSession $launch = null;

    /** @var array<string,ApiHttp> */
    private static array $logins = [];

    public static function login(ApiTestCase $test, string $personaId, string $displayName): ApiHttp
    {
        if (isset(self::$logins[$personaId])) {
            return self::$logins[$personaId];
        }

        $secret = getenv('TSUGI_DEMO_SECRET');
        $test->assertNotFalse($secret, 'TSUGI_DEMO_SECRET must be set.');
        $test->assertNotSame('', $secret, 'TSUGI_DEMO_SECRET must not be empty.');

        $http = new ApiHttp(ApiTestCase::siteBase());
        $formPage = $http->request('GET', 'login/simulate');
        $test->assertHttpStatus($formPage, 200, 'GET login/simulate');
        $test->assertStringContainsString('Demo login', $formPage->body, $formPage->excerpt());
        $token = $test->csrfToken($formPage->body);
        $test->assertNotSame('', $token, 'Demo login form has no CSRF token. '.$formPage->excerpt());

        $loggedIn = $http->request('POST', 'login/simulate', [
            'form' => [
                'persona' => $personaId,
                'secret' => $secret,
                'CSRF_TOKEN' => $token,
            ],
        ]);
        $test->assertHttpStatus($loggedIn, 200, 'POST login/simulate '.$personaId);
        $test->assertStringNotContainsString('Could not log you in.', $loggedIn->body, $loggedIn->excerpt());
        $test->assertStringContainsString($displayName, $loggedIn->body, $loggedIn->excerpt());

        self::$logins[$personaId] = $http;
        return $http;
    }

    public static function instructor(ApiTestCase $test): ApiHttp
    {
        return self::login($test, 'instructor-01', 'Instructor 01');
    }

    public static function student(ApiTestCase $test): ApiHttp
    {
        return self::login($test, 'student-01', 'Student 01');
    }

    public static function unlockAdmin(ApiTestCase $test, ApiHttp $http): void
    {
        $password = getenv('TSUGI_ADMIN_PW');
        $test->assertNotFalse($password, 'TSUGI_ADMIN_PW must be set.');
        $test->assertNotSame('', $password, 'TSUGI_ADMIN_PW must not be empty.');

        $page = $http->request('GET', 'admin/');
        $test->assertHttpStatus($page, 200, 'GET admin/');
        if (str_contains($page->body, 'Administration Console')) {
            return;
        }
        $token = $test->csrfToken($page->body);
        $test->assertNotSame('', $token, 'Admin unlock form has no CSRF token. '.$page->excerpt());

        $unlocked = $http->request('POST', 'admin/', [
            'form' => [
                'passphrase' => $password,
                'CSRF_TOKEN' => $token,
            ],
        ]);
        $test->assertHttpStatus($unlocked, 200, 'POST admin/ unlock');
        $test->assertStringContainsString('Administration Console', $unlocked->body, $unlocked->excerpt());
    }

    public static function giftLaunch(ApiTestCase $test, bool $fresh = false): ApiLaunchSession
    {
        if (!$fresh && self::$launch !== null) {
            return self::$launch;
        }

        $http = new ApiHttp(ApiTestCase::siteBase());
        $page = $http->request('GET', 'store/test/gift?identity=instructor');
        $test->assertHttpStatus($page, 200, 'GET store/test/gift');
        $test->assertStringNotContainsString('Developer mode not properly configured', $page->body, $page->excerpt());
        $test->assertStringNotContainsString('No tools found.', $page->body, $page->excerpt());

        $form = self::oauthForm($page->body);
        $test->assertNotNull($form, 'Store test page has no signed launch form. '.$page->excerpt());
        $test->assertArrayHasKey('resource_link_id', $form['fields'], $page->excerpt());

        $launched = $http->request('POST', $form['action'], [
            'form' => $form['fields'],
        ]);
        $test->assertPageHasNoPhpError($launched->body, 'gift launch '.$launched->effectiveUrl);
        $sessionId = self::ltiSessionId($launched->effectiveUrl."\n".$launched->body);
        $test->assertNotSame('', $sessionId, 'Launch did not include an LTI session id. '.$launched->effectiveUrl.' '.$launched->excerpt());

        $linkId = self::linkIdForResource((string) $form['fields']['resource_link_id']);
        $test->assertGreaterThan(0, $linkId, 'Launch did not create an lti_link row.');

        $session = new ApiLaunchSession(
            $sessionId,
            $test->csrfToken($launched->body),
            $linkId
        );
        if (!$fresh) {
            self::$launch = $session;
        }
        return $session;
    }

    /**
     * Encrypt a token the way rpc.php decrypts one, using the Docker QA secrets.
     */
    public static function rpcToken(string $payload): string
    {
        $secret = self::dockerConfigString('cookiesecret');
        $token = AesCtr::encrypt($payload, $secret, 256);
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Could not encrypt an RPC token');
        }
        return $token;
    }

    public static function dockerCookiePad(): string
    {
        return self::dockerConfigString('cookiepad');
    }

    /**
     * @return array<string,mixed>
     */
    public static function linkSettings(int $linkId): array
    {
        $stmt = self::pdo()->prepare('SELECT settings FROM lti_link WHERE link_id = :id');
        $stmt->execute([':id' => $linkId]);
        $raw = $stmt->fetchColumn();
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{action: string, fields: array<string,string>}|null
     */
    private static function oauthForm(string $html): ?array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return null;
        }

        foreach ($dom->getElementsByTagName('form') as $form) {
            $fields = [];
            foreach ($form->getElementsByTagName('input') as $input) {
                $name = $input->getAttribute('name');
                if ($name === '') {
                    continue;
                }
                $fields[$name] = $input->getAttribute('value');
            }
            if (!isset($fields['oauth_signature'])) {
                continue;
            }
            $action = $form->getAttribute('action');
            if ($action === '') {
                return null;
            }
            return ['action' => $action, 'fields' => $fields];
        }
        return null;
    }

    private static function ltiSessionId(string $haystack): string
    {
        if (preg_match('/_LTI_TSUGI=([A-Za-z0-9,-]+)/', $haystack, $match)) {
            return $match[1];
        }
        if (preg_match('/name="_LTI_TSUGI" value="([^"]+)"/', $haystack, $match)) {
            return $match[1];
        }
        return '';
    }

    private static function pdo(): PDO
    {
        $port = getenv('TSUGI_DB_PORT');
        if ($port === false || $port === '') {
            $port = '33306';
        }
        $user = getenv('TSUGI_DB_USER');
        if ($user === false || $user === '') {
            $user = 'ltiuser';
        }
        $pass = getenv('TSUGI_DB_PASS');
        if ($pass === false || $pass === '') {
            $pass = 'ltipassword';
        }

        return new PDO(
            'mysql:host=127.0.0.1;port='.$port.';dbname=tsugi',
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    private static function linkIdForResource(string $resourceLinkId): int
    {
        $stmt = self::pdo()->prepare(
            'SELECT link_id FROM lti_link WHERE link_key = :k ORDER BY link_id DESC LIMIT 1'
        );
        $stmt->execute([':k' => $resourceLinkId]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return 0;
        }
        return (int) $id;
    }

    private static function dockerConfigString(string $property): string
    {
        $path = dirname(__DIR__, 3).'/docker/tsugi-docker-config.php';
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException('Could not read '.$path);
        }
        $pattern = '/\$CFG->'.preg_quote($property, '/')."\\s*=\\s*'([^']+)'/";
        if (!preg_match($pattern, $source, $match)) {
            throw new RuntimeException('docker config has no $CFG->'.$property);
        }
        return $match[1];
    }
}

final class ApiLaunchSession
{
    public function __construct(
        public string $sessionId,
        public string $csrf,
        public int $linkId
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function query(): array
    {
        return ['_LTI_TSUGI' => $this->sessionId];
    }
}
