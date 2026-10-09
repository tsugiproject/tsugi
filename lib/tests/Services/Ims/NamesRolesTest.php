<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Ims\AccessToken;
use Tsugi\Services\Ims\NamesRoles;
use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\LTI13;
use Firebase\JWT\JWT;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class NamesRolesTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        if ( $PDOX->metadata($this->p().'lti_tool_registration_token') === false ) {
            $this->markTestSkipped('Registration token table is missing. Run php admin/upgrade.php.');
        }
    }

    public function testRosterFollowsTheDeployment(): void
    {
        $registered = $this->register('Roster Garden');
        $instructor = $this->insertUser('Ada Instructor', 'ada@example.test', LTIX::ROLE_INSTRUCTOR);
        $learner = $this->insertUser('Grace Learner', 'grace@example.test', LTIX::ROLE_LEARNER);
        $admin = $this->insertUser('Alan Admin', 'alan@example.test', LTIX::ROLE_ADMINISTRATOR);
        $this->insertUser('Gone Student', 'gone@example.test', LTIX::ROLE_LEARNER, 1);

        $keys = $this->keyPair();
        $token = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_LINEITEM.' '.ToolRegistrationDocument::SCOPE_ROSTER);
        $this->assertSame(200, $token['status']);
        $this->assertSame(ToolRegistrationDocument::SCOPE_ROSTER, $token['body']['scope']);

        $jwt = LTI13::parse_jwt($token['body']['access_token'], false);
        $this->assertIsObject($jwt);
        $this->assertSame($registered['deployment_id'], $jwt->body->{LTI13::DEPLOYMENT_ID_CLAIM});

        $page = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array('limit' => '2'));
        $this->assertSame(200, $page['status']);
        $this->assertSame(NamesRoles::membershipUrl($this->id['eecs280']), $page['body']['id']);
        $this->assertSame((string) $this->id['eecs280'], $page['body']['context']['id']);
        $this->assertSame('EECS 280', $page['body']['context']['title']);
        $this->assertCount(2, $page['body']['members']);
        $this->assertSame((string) $instructor, $page['body']['members'][0]['user_id']);
        $this->assertSame(array(NamesRoles::ROLE_INSTRUCTOR), $page['body']['members'][0]['roles']);
        $this->assertSame('Ada Instructor', $page['body']['members'][0]['name']);
        $this->assertSame('ada@example.test', $page['body']['members'][0]['email']);
        $this->assertArrayNotHasKey('given_name', $page['body']['members'][0]);
        $this->assertIsString($page['next']);
        $this->assertStringContainsString('offset=2', $page['next']);

        $rest = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array(
            'limit' => '2',
            'offset' => '2',
        ));
        $this->assertSame(200, $rest['status']);
        $this->assertNull($rest['next']);
        $this->assertCount(1, $rest['body']['members']);
        $this->assertSame((string) $admin, $rest['body']['members'][0]['user_id']);
        $this->assertContains(NamesRoles::ROLE_ADMINISTRATOR, $rest['body']['members'][0]['roles']);
        $this->assertContains(NamesRoles::ROLE_INSTRUCTOR, $rest['body']['members'][0]['roles']);

        $learners = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array(
            'role' => NamesRoles::ROLE_LEARNER,
        ));
        $this->assertSame(200, $learners['status']);
        $this->assertCount(1, $learners['body']['members']);
        $this->assertSame((string) $learner, $learners['body']['members'][0]['user_id']);

        $elsewhere = NamesRoles::read($token['body']['access_token'], $this->id['eecs281'], array());
        $this->assertSame(403, $elsewhere['status']);
        $this->assertSame('not_allowed', $elsewhere['body']['error']);

        ToolDeploymentGrant::blockClaim($registered['tool_deployment_id'], 'email');
        $hidden = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array());
        $this->assertSame(200, $hidden['status']);
        $this->assertArrayNotHasKey('email', $hidden['body']['members'][0]);
        $this->assertSame('Ada Instructor', $hidden['body']['members'][0]['name']);

        ToolDeploymentGrant::blockScope($registered['tool_deployment_id'], ToolRegistrationDocument::SCOPE_ROSTER);
        $revoked = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array());
        $this->assertSame(403, $revoked['status']);
        $this->assertSame('not_allowed', $revoked['body']['error']);
    }

    public function testScoreTokenCannotReadTheRoster(): void
    {
        $registered = $this->register('Score Garden');
        $this->insertUser('Ada Instructor', 'ada-score@example.test', LTIX::ROLE_INSTRUCTOR);
        $keys = $this->keyPair();
        $token = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_SCORE);
        $this->assertSame(200, $token['status']);
        $this->assertSame(ToolRegistrationDocument::SCOPE_SCORE, $token['body']['scope']);

        $read = NamesRoles::read($token['body']['access_token'], $this->id['eecs280'], array());
        $this->assertSame(403, $read['status']);
        $this->assertSame('insufficient_scope', $read['body']['error']);
    }

    public function testUnknownScopeAndBadSignatureAreRejected(): void
    {
        $registered = $this->register('Reject Garden');
        $keys = $this->keyPair();
        $missing = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_LINEITEM);
        $this->assertSame(400, $missing['status']);
        $this->assertSame('invalid_scope', $missing['body']['error']);

        $other = $this->keyPair();
        $forged = $this->token($registered, $keys, ToolRegistrationDocument::SCOPE_ROSTER, $other['jwks']);
        $this->assertSame(401, $forged['status']);
        $this->assertSame('invalid_client', $forged['body']['error']);

        $wrongAudience = AccessToken::grant(array(
            'grant_type' => 'client_credentials',
            'client_assertion_type' => AccessToken::ASSERTION_TYPE,
            'client_assertion' => $this->assertion($registered['client_id'], $keys, 'https://evil.example/token'),
            'scope' => ToolRegistrationDocument::SCOPE_ROSTER,
        ), AccessToken::audiences(), $keys['jwks']);
        $this->assertSame(400, $wrongAudience['status']);
        $this->assertSame('invalid_grant', $wrongAudience['body']['error']);
    }

    public function testJwksFetchRefusesPrivateAddressesUnlessLocalTestingIsEnabled(): void
    {
        global $CFG, $PDOX;
        $previous = isset($CFG->qa_allow_local_jwks) ? $CFG->qa_allow_local_jwks : false;
        $CFG->qa_allow_local_jwks = false;
        try {
            $this->assertNull(AccessToken::jwksFetchTarget('http://127.0.0.1/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://127.0.0.1/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://10.1.2.3/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://192.168.1.9/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://[::1]/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://localhost/jwks.json'));
            $this->assertNull(AccessToken::jwksFetchTarget('https://user@8.8.8.8/jwks.json'));
            $this->assertSame('8.8.8.8:443:8.8.8.8', AccessToken::jwksFetchTarget('https://8.8.8.8/jwks.json'));
            $this->assertSame('8.8.8.8:8443:8.8.8.8', AccessToken::jwksFetchTarget('https://8.8.8.8:8443/jwks.json'));

            $CFG->qa_allow_local_jwks = true;
            $this->assertNull(AccessToken::jwksFetchTarget('http://127.0.0.1/jwks.json'));
            $this->assertSame('127.0.0.1:443:127.0.0.1', AccessToken::jwksFetchTarget('https://127.0.0.1/jwks.json'));
            $local = AccessToken::jwksFetchTarget('https://localhost/jwks.json');
            $this->assertIsString($local);
            $this->assertStringStartsWith('localhost:443:', $local);

            $CFG->qa_allow_local_jwks = false;
            $registered = $this->register('Private JWKS Garden');
            $PDOX->queryDie(
                "UPDATE {$this->p()}lti_tool_registration
                 SET lti13_jwks_url = :url
                 WHERE lti13_client_id = :client",
                array(
                    ':url' => 'https://127.0.0.1/jwks.json',
                    ':client' => $registered['client_id'],
                )
            );
            $keys = $this->keyPair();
            $audience = PlatformDynamicRegistration::openIdConfiguration()['token_endpoint'];
            $logFile = tempnam(sys_get_temp_dir(), 'jwks-reject');
            $this->assertIsString($logFile);
            $previousLog = ini_get('error_log');
            ini_set('error_log', $logFile);
            try {
                $denied = AccessToken::grant(array(
                    'grant_type' => 'client_credentials',
                    'client_assertion_type' => AccessToken::ASSERTION_TYPE,
                    'client_assertion' => $this->assertion($registered['client_id'], $keys, $audience),
                    'scope' => ToolRegistrationDocument::SCOPE_ROSTER,
                ), AccessToken::audiences(), null);
            } finally {
                ini_set('error_log', $previousLog === false ? '' : $previousLog);
            }
            $logged = (string) file_get_contents($logFile);
            unlink($logFile);
            $this->assertSame(401, $denied['status']);
            $this->assertSame('invalid_client', $denied['body']['error']);
            $this->assertStringContainsString('LTI token JWKS url rejected', $logged);
            $this->assertStringContainsString('url=https://127.0.0.1/jwks.json', $logged);
            $this->assertStringContainsString('qa_allow_local_jwks=false', $logged);
            $this->assertStringContainsString('127.0.0.1 is loopback, private, or reserved', $logged);
            $this->assertStringContainsString('$CFG->qa_allow_local_jwks = true', $logged);
            $this->assertStringContainsString('config.php', $logged);
        } finally {
            $CFG->qa_allow_local_jwks = $previous;
        }
    }

    public function testMembershipPath(): void
    {
        $this->assertSame(12, NamesRoles::contextIdFromPath('/tsugi/ims/nrps/context/12/memberships'));
        $this->assertSame(12, NamesRoles::contextIdFromPath('/ims/nrps/context/12/memberships/'));
        $this->assertNull(NamesRoles::contextIdFromPath('/tsugi/lti/nrps/context/12/memberships'));
        $this->assertNull(NamesRoles::contextIdFromPath('/ims/nrps/context/0/memberships'));
    }

    /**
     * @return array{client_id:string, deployment_id:string, tool_deployment_id:int}
     */
    private function register(string $title): array
    {
        global $PDOX;
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload($title));
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $row = $PDOX->rowDie(
            "SELECT d.tool_deployment_id, d.deployment_id
             FROM {$this->p()}lti_tool_registration r
             JOIN {$this->p()}lti_tool_deployment d ON d.registration_id = r.registration_id
             WHERE r.title = :title AND r.key_id = :key_id",
            array(
                ':title' => $title,
                ':key_id' => $this->id['keyA'],
            )
        );
        $this->assertIsArray($row);
        return array(
            'client_id' => (string) $stored['document']['client_id'],
            'deployment_id' => (string) $row['deployment_id'],
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
        );
    }

    /**
     * @param array{client_id:string, deployment_id:string, tool_deployment_id:int} $registered
     * @param array{private:string, jwks:array<string, mixed>} $keys
     * @param array<string, mixed>|null $jwks
     * @return array{status:int, body:array<string, mixed>}
     */
    private function token(array $registered, array $keys, string $scope, ?array $jwks = null): array
    {
        $audience = PlatformDynamicRegistration::openIdConfiguration()['token_endpoint'];
        return AccessToken::grant(array(
            'grant_type' => 'client_credentials',
            'client_assertion_type' => AccessToken::ASSERTION_TYPE,
            'client_assertion' => $this->assertion($registered['client_id'], $keys, $audience),
            'scope' => $scope,
        ), AccessToken::audiences(), $jwks ?? $keys['jwks']);
    }

    /**
     * @param array{private:string, jwks:array<string, mixed>} $keys
     */
    private function assertion(string $clientId, array $keys, string $audience): string
    {
        $now = time();
        return LTI13::encode_jwt(array(
            'iss' => $clientId,
            'sub' => $clientId,
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + 60,
            'jti' => bin2hex(random_bytes(8)),
        ), $keys['private'], 'test-key');
    }

    /**
     * @return array{private:string, jwks:array<string, mixed>}
     */
    private function keyPair(): array
    {
        $public = null;
        $private = null;
        $ok = LTI13::generatePKCS8Pair($public, $private);
        $this->assertTrue($ok === true);
        $this->assertIsString($public);
        $this->assertIsString($private);
        $opened = openssl_pkey_get_public($public);
        $this->assertNotFalse($opened);
        $details = openssl_pkey_get_details($opened);
        $this->assertIsArray($details);
        return array(
            'private' => $private,
            'jwks' => array(
                'keys' => array(
                    array(
                        'kty' => 'RSA',
                        'alg' => 'RS256',
                        'use' => 'sig',
                        'kid' => 'test-key',
                        'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                        'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
                    ),
                ),
            ),
        );
    }

    private function insertUser(string $name, string $email, int $role, int $deleted = 0): int
    {
        global $PDOX;
        $userKey = bin2hex(random_bytes(8));
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_user
                (user_key, user_sha256, displayname, email, key_id, created_at, updated_at)
             VALUES (:user_key, :sha, :name, :email, :key_id, NOW(), NOW())",
            array(
                ':user_key' => $userKey,
                ':sha' => hash('sha256', $userKey),
                ':name' => $name,
                ':email' => $email,
                ':key_id' => $this->id['keyA'],
            )
        );
        $userId = (int) $PDOX->lastInsertId();
        $PDOX->queryDie(
            "INSERT INTO {$this->p()}lti_membership
                (context_id, user_id, role, deleted, created_at, updated_at)
             VALUES (:context_id, :user_id, :role, :deleted, NOW(), NOW())",
            array(
                ':context_id' => $this->id['eecs280'],
                ':user_id' => $userId,
                ':role' => $role,
                ':deleted' => $deleted,
            )
        );
        return $userId;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $title): array
    {
        return array(
            'application_type' => 'web',
            'initiate_login_uri' => 'https://client.example.org/lti',
            'redirect_uris' => array('https://client.example.org/callback'),
            'client_name' => $title,
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'scope' => ToolRegistrationDocument::SCOPE_SCORE.' '.ToolRegistrationDocument::SCOPE_ROSTER,
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'domain' => 'client.example.org',
                'target_link_uri' => 'https://client.example.org/launch',
                'claims' => array('iss', 'sub', 'name', 'email'),
                'messages' => array(
                    array(
                        'type' => 'LtiResourceLinkRequest',
                        'target_link_uri' => 'https://client.example.org/resource',
                    ),
                ),
            ),
        );
    }
}
