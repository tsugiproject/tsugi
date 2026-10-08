<?php

use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class PlatformDynamicRegistrationTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        if ( $PDOX->metadata($this->p().'lti_tool_registration_token') === false ) {
            $this->markTestSkipped('Registration token table is missing. Run php admin/upgrade.php.');
        }
    }

    public function testOpenIdConfigurationHasNoToolId(): void
    {
        global $CFG;
        $root = rtrim((string) $CFG->wwwroot, '/');
        $config = PlatformDynamicRegistration::openIdConfiguration();

        $this->assertSame($root, $config['issuer']);
        $this->assertSame($root.'/lti/oidc_auth', $config['authorization_endpoint']);
        $this->assertSame($root.'/lti/token', $config['token_endpoint']);
        $this->assertSame($root.'/lti/keyset', $config['jwks_uri']);
        $this->assertSame($root.'/lti/register', $config['registration_endpoint']);
        $this->assertSame($root.'/lti/openid-configuration', PlatformDynamicRegistration::openIdConfigurationUrl());
        $forward = PlatformDynamicRegistration::forwardUrl('https://tool.example/register?x=1', '99:abc');
        $this->assertStringStartsWith('https://tool.example/register?x=1&openid_configuration=', $forward);
        $this->assertStringContainsString('registration_token=99%3Aabc', $forward);
        foreach ( array('authorization_endpoint', 'token_endpoint', 'jwks_uri', 'registration_endpoint') as $key ) {
            $this->assertStringNotContainsString('?', $config[$key]);
        }
        $this->assertSame('tsugi.org', $config[PlatformDynamicRegistration::PLATFORM_CONFIGURATION]['product_family_code']);
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest', 'LtiDataPrivacyLaunchRequest'),
            array_column($config[PlatformDynamicRegistration::PLATFORM_CONFIGURATION]['messages_supported'], 'type')
        );
    }

    public function testPostCreatesTheToolOnce(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register?from=lms'
        );
        $payload = $this->payload();
        $raw = json_encode($payload);
        $this->assertIsString($raw);

        $result = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $result['status']);
        $this->assertSame('Virtual Garden', $result['document']['client_name']);
        $this->assertNotSame('', $result['document']['client_id']);
        $config = $result['document'][ToolRegistrationDocument::TOOL_CONFIGURATION];
        $this->assertNotSame('', $config['deployment_id']);

        $again = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(403, $again['status']);

        $registrationId = $this->registrationId('Virtual Garden');
        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertIsArray($stored);
        $this->assertSame($result['document']['client_id'], $stored['client_id']);
        $this->assertSame('https://client.example.org/lti', $this->column($registrationId, 'lti13_oidc_login_url'));
        $this->assertSame('https://client.example.org/jwks.json', $this->column($registrationId, 'lti13_jwks_url'));
        $this->assertSame('https://client.example.org/callback', $this->column($registrationId, 'lti13_redirect_uri'));
        $this->assertSame('https://client.example.org/launch', $this->column($registrationId, 'lti13_launch_url'));
        $this->assertTrue($stored['vendor_extension']['keep']);

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array('LtiDeepLinkingRequest'), array_column($messages, 'message_type'));
        $this->assertSame(array('Add a virtual garden'), array_column($messages, 'label'));

        $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
        $this->assertContains('name', ToolDeploymentGrant::allowedClaims($deploymentId));
        $this->assertContains(ToolRegistrationDocument::SCOPE_SCORE, ToolDeploymentGrant::allowedScopes($deploymentId));
        $this->assertContains('Virtual Garden', $this->titles(ToolDeploymentService::getRegistrationsForContext($this->id['eecs280'])));

        $logs = ToolRegistrationDocument::logsForRegistration($registrationId);
        $this->assertNotSame(array(), $logs);
        $this->assertSame($raw, $logs[0]['payload_text']);
        $this->assertSame('inbound', $logs[0]['direction']);

        $info = PlatformDynamicRegistration::courseToken($token, $this->id['eecs280']);
        $this->assertIsArray($info);
        $this->assertTrue($info['used']);
        $this->assertSame($registrationId, $info['registration_id']);

        $view = ToolRegistrationService::visibleLti13($this->id['eecs280'], $registrationId);
        $this->assertSame('Virtual Garden', $view['title']);
        $this->assertSame($this->id['eecs280'], $view['owner_context_id']);
        $this->assertSame($result['document']['client_id'], $view['client_id']);
        $this->assertSame('https://client.example.org/launch', $view['launch_url']);
        $this->assertSame(array(
            'https://client.example.org/callback',
            'https://client.example.org/callback2',
        ), $view['redirect_uris']);
        $this->assertSame(array('link_selection'), $view['messages'][0]['placements']);
        $this->assertSame(array('link_selection'), $view['deployments'][0]['enabled_placements']);
        $this->assertSame(array('LtiDeepLinkingRequest'), array_column($view['messages'], 'message_type'));
        $this->assertSame($config['deployment_id'], $view['deployments'][0]['deployment_id']);
        $this->assertContains('name', $view['deployments'][0]['allowed_claims']);
        $this->assertContains(ToolRegistrationDocument::SCOPE_SCORE, $view['deployments'][0]['allowed_scopes']);
        $this->assertStringContainsString('Virtual Garden', $view['registration_json']);

        $hidden = false;
        try {
            ToolRegistrationService::visibleLti13($this->id['eecs281'], $registrationId);
        } catch ( \InvalidArgumentException $e ) {
            $hidden = true;
        }
        $this->assertTrue($hidden);
    }

    public function testOmittedToolConfigurationStillGetsADeploymentId(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode(array(
            'initiate_login_uri' => 'https://client.example.org/lti',
            'redirect_uris' => array('https://client.example.org/callback'),
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'client_name' => 'No Config Garden',
        ));
        $this->assertIsString($raw);
        $result = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $result['status']);
        $config = $result['document'][ToolRegistrationDocument::TOOL_CONFIGURATION];
        $this->assertNotSame('', $config['deployment_id']);
        $this->assertSame('No Config Garden', $this->column($this->registrationId('No Config Garden'), 'title'));
    }

    public function testMalformedJsonIsLoggedAndDoesNotCreateATool(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = '{not-json';
        $result = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(400, $result['status']);
        $this->assertNull($this->registrationIdOrNull('Virtual Garden'));

        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT registration_id, payload_text
             FROM {$this->p()}lti_tool_registration_log
             WHERE payload_text = :payload_text",
            array(':payload_text' => $raw)
        );
        $this->assertIsArray($row);
        $this->assertNull($row['registration_id']);
        $this->assertSame($raw, $row['payload_text']);

        $info = PlatformDynamicRegistration::courseToken($token, $this->id['eecs280']);
        $this->assertIsArray($info);
        $this->assertFalse($info['used']);
    }

    public function testLoginUrlWithMarkupIsRejected(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode(array(
            'initiate_login_uri' => 'https://client.example.org/lti"><script>',
            'redirect_uris' => array('https://client.example.org/callback?a=1&b=2'),
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'client_name' => 'Markup Garden',
        ));
        $this->assertIsString($raw);
        $result = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(400, $result['status']);
        $this->assertNull($this->registrationIdOrNull('Markup Garden'));
    }

    public function testLoginUrlQueryStringIsAccepted(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode(array(
            'initiate_login_uri' => 'https://client.example.org/lti?a=1&b=2',
            'redirect_uris' => array('https://client.example.org/callback'),
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'client_name' => 'Query Garden',
        ));
        $this->assertIsString($raw);
        $result = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $result['status']);
        $this->assertSame('https://client.example.org/lti?a=1&b=2', $this->column($this->registrationId('Query Garden'), 'lti13_oidc_login_url'));
    }

    public function testUnknownTokenIsRejectedWithoutALogRow(): void
    {
        $raw = '{"initiate_login_uri":"https://client.example.org/nope"}';
        $result = PlatformDynamicRegistration::accept('Bearer 1:not-a-real-token', $raw, 'application/json');
        $this->assertSame(403, $result['status']);

        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT log_id FROM {$this->p()}lti_tool_registration_log WHERE payload_text = :payload_text",
            array(':payload_text' => $raw)
        );
        $this->assertFalse(is_array($row));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return array(
            'application_type' => 'web',
            'initiate_login_uri' => 'https://client.example.org/lti',
            'redirect_uris' => array(
                'https://client.example.org/callback',
                'https://client.example.org/callback2',
            ),
            'client_name' => 'Virtual Garden',
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'scope' => ToolRegistrationDocument::SCOPE_SCORE.' '.ToolRegistrationDocument::SCOPE_ROSTER,
            'vendor_extension' => array('keep' => true),
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'domain' => 'client.example.org',
                'target_link_uri' => 'https://client.example.org/launch',
                'claims' => array('iss', 'sub', 'name', 'email'),
                'messages' => array(
                    array(
                        'type' => 'LtiDeepLinkingRequest',
                        'target_link_uri' => 'https://client.example.org/lti/dl',
                        'label' => 'Add a virtual garden',
                        'placements' => array('link_selection'),
                    ),
                ),
            ),
        );
    }

    private function registrationId(string $title): int
    {
        $id = $this->registrationIdOrNull($title);
        $this->assertNotNull($id);
        return (int) $id;
    }

    private function registrationIdOrNull(string $title): ?int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT registration_id FROM {$this->p()}lti_tool_registration
             WHERE title = :title AND key_id = :key_id",
            array(
                ':title' => $title,
                ':key_id' => $this->id['keyA'],
            )
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return (int) $row['registration_id'];
    }

    private function column(int $registrationId, string $column): string
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT {$column} AS value FROM {$this->p()}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        $this->assertIsArray($row);
        return (string) $row['value'];
    }
}
