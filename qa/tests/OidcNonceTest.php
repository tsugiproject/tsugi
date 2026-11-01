<?php

require_once __DIR__ . '/Support/ApiHttp.php';

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Exercise the real login, callback and tool endpoints with a synthetic platform. */
final class OidcNonceTest extends TestCase
{
    private static PDO $pdo;
    private static int $keyId;
    private static string $privateKey = '';
    private static string $issuer;
    private static string $base;
    private ApiHttp $http;

    public static function setUpBeforeClass(): void
    {
        self::$base = rtrim(getenv('TSUGI_BASE_URL') ?: 'http://localhost:8888/tsugi', '/');
        self::$pdo = new PDO(
            'mysql:host='.(getenv('TSUGI_QA_DB_HOST') ?: '127.0.0.1').
            ';port='.(getenv('TSUGI_DB_PORT') ?: '33306').';dbname=tsugi',
            getenv('TSUGI_DB_USER') ?: 'ltiuser',
            getenv('TSUGI_DB_PASS') ?: 'ltipassword',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        openssl_pkey_export($key, self::$privateKey);
        self::$issuer = 'https://nonce-test.invalid/'.bin2hex(random_bytes(16));
        $stmt = self::$pdo->prepare('INSERT INTO lti_key
            (key_title, lms_issuer, lms_issuer_sha256, lms_client, deploy_key, deploy_sha256,
             lms_oidc_auth, lms_cache_pubkey, lms_cache_kid)
            VALUES (:title, :issuer, :hash, :client, :deployment, :deployment_hash, :auth, :pubkey, :kid)');
        $stmt->execute([
            ':title' => 'QA OIDC nonce', ':issuer' => self::$issuer,
            ':hash' => hash('sha256', self::$issuer), ':client' => 'nonce-client',
            ':deployment' => 'nonce-deployment', ':deployment_hash' => hash('sha256', 'nonce-deployment'),
            ':auth' => self::$issuer.'/authorize', ':pubkey' => openssl_pkey_get_details($key)['key'],
            ':kid' => 'nonce-test-key',
        ]);
        self::$keyId = (int) self::$pdo->lastInsertId();
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$keyId)) {
            // The fixture owns this tenant and its launch data (foreign keys cascade).
            self::$pdo->prepare('DELETE FROM lti_key WHERE key_id = ?')->execute([self::$keyId]);
        }
    }

    protected function setUp(): void
    {
        $this->http = new ApiHttp(self::$base);
    }

    private function login(): array
    {
        $page = $this->http->request('GET', 'lti/oidc_login/'.self::$keyId, ['query' => [
            'iss' => self::$issuer, 'login_hint' => 'learner',
            'target_link_uri' => self::$base.'/tool/gift/index.php',
        ]]);
        $this->assertSame(200, $page->status, $page->excerpt());
        $this->assertSame(1, preg_match('/(?:let|const) TSUGI_REDIRECT = ("[^"\n]+");/', $page->body, $match), $page->excerpt());
        $redirect = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        parse_str(parse_url($redirect, PHP_URL_QUERY), $params);
        $this->assertNotEmpty($params['nonce']);
        $this->assertNotEmpty($params['state']);
        return $params;
    }

    private function token(array $login, array $overrides = [], bool $omitNonce = false): string
    {
        $claim = 'https://purl.imsglobal.org/spec/lti/claim/';
        $body = array_replace([
            'iss' => self::$issuer, 'aud' => 'nonce-client', 'sub' => 'nonce-learner',
            'iat' => time(), 'exp' => time() + 120, 'nonce' => $login['nonce'],
            $claim.'deployment_id' => 'nonce-deployment',
            $claim.'version' => '1.3.0', $claim.'message_type' => 'LtiResourceLinkRequest',
            $claim.'target_link_uri' => self::$base.'/tool/gift/index.php',
            $claim.'resource_link' => ['id' => 'nonce-link'],
            $claim.'context' => ['id' => 'nonce-course', 'title' => 'Nonce QA'],
            $claim.'roles' => ['http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'],
        ], $overrides);
        if ($omitNonce) unset($body['nonce']);
        return JWT::encode($body, self::$privateKey, 'RS256', 'nonce-test-key');
    }

    private function launchResponse(array $login, string $token): ApiResponse
    {
        $page = $this->http->request('POST', 'lti/oidc_launch', ['form' => [
            'state' => $login['state'], 'id_token' => $token,
        ]]);
        // Follow the top-level postMessage fallback, which reuses the saved ID token.
        if (str_contains($page->body, 'id="oidc_postmessage"')) {
            $page = $this->http->request('POST', 'lti/oidc_launch', ['form' => [
                'state' => $login['state'], 'postmessage' => 'success',
            ]]);
        }
        return $page;
    }

    private function forward(ApiResponse $page): ApiResponse
    {
        $this->assertStringContainsString('id="oidc_forward"', $page->body, $page->excerpt());
        $dom = new DOMDocument();
        @$dom->loadHTML($page->body);
        $form = $dom->getElementById('oidc_forward');
        $this->assertNotNull($form);
        $fields = [];
        foreach ($form->getElementsByTagName('input') as $input) {
            $fields[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        return $this->http->request('POST', $form->getAttribute('action'), ['form' => $fields]);
    }

    public function testMatchingNonceLaunchesAndReplayIsRejected(): void
    {
        $login = $this->login();
        $token = $this->token($login);
        $callback = $this->launchResponse($login, $token);
        $tool = $this->forward($callback);
        $this->assertSame(200, $tool->status, $tool->excerpt());
        $this->assertStringContainsString('This quiz has not yet been configured', $tool->body, $tool->excerpt());
        $this->assertStringNotContainsString('Launch Error', $tool->body);
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM lti_nonce WHERE key_id = ? AND nonce = ?');
        $stmt->execute([self::$keyId, $login['nonce']]);
        $this->assertSame(1, (int) $stmt->fetchColumn(), 'The tool must accept and record the launch nonce.');
        $replayed = $this->launchResponse($login, $token);
        $this->assertStringContainsString('Could not find state in session', $replayed->body);
        $toolReplay = $this->forward($callback);
        $this->assertStringContainsString('OAuth nonce error', $toolReplay->body, $toolReplay->excerpt());
    }

    public static function invalidNonces(): array
    {
        return [
            'mismatch' => [['nonce' => 'another-request'], false],
            'missing' => [[], true],
            'empty' => [['nonce' => ''], false],
            'null' => [['nonce' => null], false],
            'number' => [['nonce' => 123], false],
            'array' => [['nonce' => ['not-a-string']], false],
        ];
    }

    #[DataProvider('invalidNonces')]
    public function testInvalidNonceIsNotForwarded(array $overrides, bool $omitNonce): void
    {
        $login = $this->login();
        $page = $this->launchResponse($login, $this->token($login, $overrides, $omitNonce));
        $this->assertStringContainsString('OIDC nonce', $page->body, $page->excerpt());
        $this->assertStringNotContainsString('id="oidc_forward"', $page->body);
    }

    public function testConcurrentTransactionsAreDistinctAndCannotBeCrossed(): void
    {
        $first = $this->login();
        $second = $this->login();
        $this->assertNotSame($first['nonce'], $second['nonce']);
        $this->assertNotSame($first['state'], $second['state']);
        $crossed = $this->launchResponse($first, $this->token($second));
        $this->assertStringContainsString('OIDC nonce', $crossed->body, $crossed->excerpt());
        // Rejecting the crossed response must not overwrite either transaction's nonce.
        foreach ([$second, $first] as $login) {
            $page = $this->launchResponse($login, $this->token($login));
            $this->assertStringContainsString('id="oidc_forward"', $page->body, $page->excerpt());
        }
    }

    public function testDirectToolPostCannotBypassTheNonceCheck(): void
    {
        $login = $this->login();
        $page = $this->http->request('POST', 'tool/gift/index.php', ['form' => [
            'state' => $login['state'], 'id_token' => $this->token($login, ['nonce' => 'wrong-direct-nonce']),
        ]]);
        $this->assertStringContainsString('OIDC nonce', $page->body, $page->excerpt());
    }

    public function testDirectToolPostRequiresAuthenticState(): void
    {
        $login = $this->login();
        // Even a matching nonce is insufficient without a state signed by Tsugi.
        $parts = explode('.', $login['state']);
        $tampered = JWT::jsonDecode(JWT::urlsafeB64Decode($parts[1]));
        $tampered->nonce = 'tampered';
        $parts[1] = JWT::urlsafeB64Encode(json_encode($tampered));
        foreach (['', 'not-a-signed-state', implode('.', $parts)] as $state) {
            $page = $this->http->request('POST', 'tool/gift/index.php', ['form' => [
                'state' => $state, 'id_token' => $this->token($login, ['nonce' => 'tampered']),
            ]]);
            $this->assertStringContainsString('OIDC state', $page->body, $page->excerpt());
        }
    }
}
