<?php

use Tsugi\Core\Keyset;
use Tsugi\Services\Ims\AssignmentsGrades;
use Tsugi\Services\Ims\NamesRoles;
use Tsugi\Services\Outbound\Lti13TestLaunch;
use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\LTI13;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class Lti13TestLaunchTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        if ( $PDOX->metadata($this->p().'lti_tool_registration_token') === false ) {
            $this->markTestSkipped('Registration token table is missing. Run php admin/upgrade.php.');
        }
    }

    public function testDeepLinkOnlyRegistrationDoesNotSendAResourceLink(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload());
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $registrationId = $this->registrationId('Virtual Garden');

        $choices = Lti13TestLaunch::choices($this->id['eecs280'], $registrationId);
        $this->assertSame('LtiResourceLinkRequest', $choices[0]['type']);
        $this->assertFalse($choices[0]['ready']);
        $deep = null;
        foreach ( $choices as $choice ) {
            if ( $choice['type'] === 'LtiDeepLinkingRequest' ) {
                $deep = $choice;
            }
        }
        $this->assertNotNull($deep);
        $this->assertTrue($deep['ready']);

        $started = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiResourceLinkRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Learner'
        );
        $this->assertFalse($started['ready']);
        $this->assertTrue($started['missing_resource_link']);
        $this->assertTrue($started['content_item_url']);
        $this->assertSame('https://client.example.org/launch', $started['endpoint']);
        $this->assertSame(array(), $started['parameters']);
        $this->assertSame('', $started['jwt_signed']);
    }

    public function testResourceLinkMessageIsSigned(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload(true));
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $registrationId = $this->registrationId('Resource Garden');
        $config = $stored['document'][ToolRegistrationDocument::TOOL_CONFIGURATION];

        $started = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiResourceLinkRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Learner'
        );
        $this->assertTrue($started['ready']);
        $this->assertFalse($started['missing_resource_link']);
        $this->assertSame('https://client.example.org/resource', $started['endpoint']);
        $this->assertSame('https://client.example.org/lti', $started['form_endpoint']);
        $this->assertSame(PlatformDynamicRegistration::openIdConfiguration()['issuer'], $started['parameters']['iss']);
        $this->assertSame($stored['document']['client_id'], $started['parameters']['client_id']);
        $this->assertSame($config['deployment_id'], $started['parameters']['lti_deployment_id']);
        $this->assertSame('https://client.example.org/resource', $started['parameters']['target_link_uri']);
        $this->assertStringContainsString('LtiResourceLinkRequest', $started['jwt_json']);
        $this->assertNotSame('', $started['jwt_signed']);

        $done = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'client_id' => $started['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $started['parameters']['login_hint'],
            'lti_message_hint' => $started['parameters']['lti_message_hint'],
            'lti_deployment_id' => $started['parameters']['lti_deployment_id'],
            'nonce' => 'nonce-4242',
            'state' => 'tool-state',
        ));
        $this->assertSame('https://client.example.org/callback', $done['redirect_uri']);
        $this->assertSame('tool-state', $done['state']);

        $jwt = LTI13::parse_jwt($done['id_token'], false);
        $this->assertIsObject($jwt);
        $body = $jwt->body;
        $this->assertSame('LtiResourceLinkRequest', $body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertSame('1.3.0', $body->{LTI13::VERSION_CLAIM});
        $this->assertSame($config['deployment_id'], $body->{LTI13::DEPLOYMENT_ID_CLAIM});
        $this->assertSame('https://client.example.org/resource', $body->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'});
        $this->assertSame('nonce-4242', $body->nonce);
        $this->assertSame('424242', $body->sub);
        $this->assertSame($stored['document']['client_id'], $body->aud);
        $this->assertContains(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
            $body->{LTI13::ROLES_CLAIM}
        );
        $this->assertSame('test-'.$registrationId, $body->{LTI13::RESOURCE_LINK_CLAIM}->id);
        $this->assertContains(ToolRegistrationDocument::SCOPE_SCORE, $body->{LTI13::ENDPOINT_CLAIM}->scope);
        $this->assertSame(
            AssignmentsGrades::lineItemsUrl($this->id['eecs280']),
            $body->{LTI13::ENDPOINT_CLAIM}->lineitems
        );
        $this->assertSame(
            NamesRoles::membershipUrl($this->id['eecs280']),
            $body->{LTI13::NAMESANDROLES_CLAIM}->context_memberships_url
        );
        $this->assertContains('2.0', $body->{LTI13::NAMESANDROLES_CLAIM}->service_versions);

        $verified = false;
        foreach ( Keyset::getCurrentKeys() as $row ) {
            if ( isset($row['pubkey']) && LTI13::verifyPublicKey($done['id_token'], $row['pubkey']) === true ) {
                $verified = true;
            }
        }
        $this->assertTrue($verified);

        $rejected = false;
        try {
            Lti13TestLaunch::complete(array(
                'scope' => 'openid',
                'response_type' => 'id_token',
                'client_id' => $started['parameters']['client_id'],
                'redirect_uri' => 'https://evil.example/callback',
                'login_hint' => $started['parameters']['login_hint'],
                'lti_message_hint' => $started['parameters']['lti_message_hint'],
                'nonce' => 'nonce-4242',
                'state' => 'tool-state',
            ));
        } catch ( \InvalidArgumentException $e ) {
            $rejected = true;
        }
        $this->assertTrue($rejected);

        $quiet = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiResourceLinkRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Learner',
            false
        );
        $this->assertStringNotContainsString(LTI13::ENDPOINT_CLAIM, $quiet['jwt_json']);
        $quietDone = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $quiet['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $quiet['parameters']['login_hint'],
            'lti_message_hint' => $quiet['parameters']['lti_message_hint'],
            'nonce' => 'nonce-quiet',
            'state' => 'quiet-state',
        ));
        $quietJwt = LTI13::parse_jwt($quietDone['id_token'], false);
        $this->assertIsObject($quietJwt);
        $this->assertObjectNotHasProperty(LTI13::ENDPOINT_CLAIM, $quietJwt->body);
        $this->assertSame(
            NamesRoles::membershipUrl($this->id['eecs280']),
            $quietJwt->body->{LTI13::NAMESANDROLES_CLAIM}->context_memberships_url
        );
    }

    public function testPrivacyLaunchOmitsTheResourceAndTheCourse(): void
    {
        global $PDOX;
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload(false, true));
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $registrationId = $this->registrationId('Privacy Garden');

        $privacy = null;
        $content = null;
        foreach ( Lti13TestLaunch::choices($this->id['eecs280'], $registrationId) as $choice ) {
            if ( $choice['type'] === 'LtiDataPrivacyLaunchRequest' ) {
                $privacy = $choice;
            }
            if ( $choice['type'] === 'LtiDeepLinkingRequest' ) {
                $content = $choice;
            }
            if ( $choice['type'] === 'LtiResourceLinkRequest' ) {
                $this->assertFalse($choice['ready']);
            }
        }
        $this->assertNotNull($privacy);
        $this->assertTrue($privacy['ready']);
        $this->assertNotNull($content);
        $this->assertTrue($content['ready']);

        $started = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiDataPrivacyLaunchRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Instructor'
        );
        $this->assertTrue($started['ready']);
        $this->assertSame('https://client.example.org/privacy', $started['endpoint']);
        $this->assertSame('https://client.example.org/privacy', $started['parameters']['target_link_uri']);
        $this->assertStringContainsString('LtiDataPrivacyLaunchRequest', $started['jwt_json']);
        $this->assertStringNotContainsString(LTI13::RESOURCE_LINK_CLAIM, $started['jwt_json']);
        $this->assertStringNotContainsString(LTI13::CONTEXT_ID_CLAIM, $started['jwt_json']);

        $done = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'client_id' => $started['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $started['parameters']['login_hint'],
            'lti_message_hint' => $started['parameters']['lti_message_hint'],
            'nonce' => 'nonce-privacy',
            'state' => 'privacy-state',
        ));
        $jwt = LTI13::parse_jwt($done['id_token'], false);
        $this->assertIsObject($jwt);
        $body = $jwt->body;
        $this->assertSame('LtiDataPrivacyLaunchRequest', $body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertSame('nonce-privacy', $body->nonce);
        $this->assertSame('424242', $body->sub);
        $this->assertSame('424242', $body->{LTI13::FOR_USER_CLAIM}->user_id);
        $this->assertContains(
            'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
            $body->{LTI13::ROLES_CLAIM}
        );
        $this->assertContains(
            'http://purl.imsglobal.org/vocab/lis/v2/system/person#User',
            $body->{LTI13::FOR_USER_CLAIM}->roles
        );
        $this->assertObjectNotHasProperty(LTI13::RESOURCE_LINK_CLAIM, $body);
        $this->assertObjectNotHasProperty(LTI13::CONTEXT_ID_CLAIM, $body);
        $this->assertObjectNotHasProperty(LTI13::ENDPOINT_CLAIM, $body);
        $this->assertObjectNotHasProperty(LTI13::NAMESANDROLES_CLAIM, $body);

        $PDOX->queryDie(
            "UPDATE {$this->p()}lti_tool_message
             SET target_link_uri = :url
             WHERE registration_id = :id AND message_type = :type",
            array(
                ':url' => 'https://client.example.org/launch',
                ':id' => $registrationId,
                ':type' => 'LtiDataPrivacyLaunchRequest',
            )
        );
        $rejected = false;
        try {
            Lti13TestLaunch::complete(array(
                'scope' => 'openid',
                'response_type' => 'id_token',
                'client_id' => $started['parameters']['client_id'],
                'redirect_uri' => 'https://client.example.org/callback',
                'login_hint' => $started['parameters']['login_hint'],
                'lti_message_hint' => $started['parameters']['lti_message_hint'],
                'nonce' => 'nonce-privacy',
                'state' => 'privacy-state',
            ));
        } catch ( \InvalidArgumentException $e ) {
            $rejected = str_contains($e->getMessage(), 'no privacy launch');
        }
        $this->assertTrue($rejected);
    }

    public function testDeepLinkReturnBecomesAResourceLink(): void
    {
        $token = PlatformDynamicRegistration::startForCourse(
            $this->id['eecs280'],
            null,
            'https://client.example.org/register'
        );
        $raw = json_encode($this->payload());
        $this->assertIsString($raw);
        $stored = PlatformDynamicRegistration::accept('Bearer '.$token, $raw, 'application/json');
        $this->assertSame(200, $stored['status']);
        $registrationId = $this->registrationId('Virtual Garden');
        $config = $stored['document'][ToolRegistrationDocument::TOOL_CONFIGURATION];

        $started = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiDeepLinkingRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Instructor'
        );
        $this->assertTrue($started['ready']);
        $this->assertTrue($started['modal']);
        $this->assertSame('https://client.example.org/launch', $started['endpoint']);

        $done = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $started['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $started['parameters']['login_hint'],
            'lti_message_hint' => $started['parameters']['lti_message_hint'],
            'nonce' => 'nonce-deep',
            'state' => 'deep-state',
        ));
        $jwt = LTI13::parse_jwt($done['id_token'], false);
        $this->assertIsObject($jwt);
        $body = $jwt->body;
        $this->assertSame('LtiDeepLinkingRequest', $body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertObjectNotHasProperty(LTI13::RESOURCE_LINK_CLAIM, $body);
        $settings = $body->{LTI13::DEEPLINK_CLAIM};
        $this->assertSame(Lti13TestLaunch::returnUrl(), $settings->deep_link_return_url);
        $this->assertContains('ltiResourceLink', $settings->accept_types);

        $key = openssl_pkey_new(array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        $this->assertNotFalse($key);
        $private = '';
        $this->assertTrue(openssl_pkey_export($key, $private));
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);
        $response = LTI13::encode_jwt(array(
            'iss' => $stored['document']['client_id'],
            'aud' => PlatformDynamicRegistration::openIdConfiguration()['issuer'],
            'iat' => time(),
            'exp' => time() + 600,
            'nonce' => 'return-nonce',
            LTI13::MESSAGE_TYPE_CLAIM => 'LtiDeepLinkingResponse',
            LTI13::VERSION_CLAIM => '1.3.0',
            LTI13::DEPLOYMENT_ID_CLAIM => $config['deployment_id'],
            'https://purl.imsglobal.org/spec/lti-dl/claim/data' => $settings->data,
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => array(
                array(
                    'type' => 'ltiResourceLink',
                    'title' => 'Chosen garden',
                    'url' => 'https://client.example.org/chosen',
                    'custom' => array('garden' => 'roses'),
                ),
            ),
        ), $private, 'tool-key');
        $accepted = Lti13TestLaunch::acceptReturn($response, $details['key']);
        $this->assertSame('https://client.example.org/chosen', $accepted['items'][0]['url']);
        $this->assertSame('Chosen garden', $accepted['items'][0]['title']);
        $this->assertStringContainsString('roses', $accepted['items'][0]['json']);

        $returned = Lti13TestLaunch::launchReturned(
            $this->id['eecs280'],
            $registrationId,
            424242,
            $accepted['items'][0]['url'],
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Instructor',
            $accepted['items'][0]['title']
        );
        $this->assertTrue($returned['ready']);
        $this->assertTrue($returned['new_window']);
        $sent = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $returned['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $returned['parameters']['login_hint'],
            'lti_message_hint' => $returned['parameters']['lti_message_hint'],
            'nonce' => 'nonce-returned',
            'state' => 'returned-state',
        ));
        $resource = LTI13::parse_jwt($sent['id_token'], false);
        $this->assertIsObject($resource);
        $this->assertSame('LtiResourceLinkRequest', $resource->body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertSame('https://client.example.org/chosen', $resource->body->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'});
        $this->assertSame('Chosen garden', $resource->body->{LTI13::RESOURCE_LINK_CLAIM}->title);
        $this->assertSame('deep-'.$registrationId, $resource->body->{LTI13::RESOURCE_LINK_CLAIM}->id);

        $quiet = Lti13TestLaunch::launch(
            $this->id['eecs280'],
            $registrationId,
            424242,
            'LtiDeepLinkingRequest',
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Instructor',
            false
        );
        $this->assertStringNotContainsString(LTI13::ENDPOINT_CLAIM, $quiet['jwt_json']);
        $quietDone = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $quiet['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $quiet['parameters']['login_hint'],
            'lti_message_hint' => $quiet['parameters']['lti_message_hint'],
            'nonce' => 'nonce-quiet-deep',
            'state' => 'quiet-deep',
        ));
        $quietJwt = LTI13::parse_jwt($quietDone['id_token'], false);
        $this->assertIsObject($quietJwt);
        $this->assertObjectNotHasProperty(LTI13::ENDPOINT_CLAIM, $quietJwt->body);

        $quietReturn = Lti13TestLaunch::launchReturned(
            $this->id['eecs280'],
            $registrationId,
            424242,
            $accepted['items'][0]['url'],
            'https://local.dj4e.com/tsugi/courses/1/settings/tools/test',
            'Instructor',
            $accepted['items'][0]['title'],
            false
        );
        $this->assertStringNotContainsString(LTI13::ENDPOINT_CLAIM, $quietReturn['jwt_json']);
        $quietSent = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $quietReturn['parameters']['client_id'],
            'redirect_uri' => 'https://client.example.org/callback',
            'login_hint' => $quietReturn['parameters']['login_hint'],
            'lti_message_hint' => $quietReturn['parameters']['lti_message_hint'],
            'nonce' => 'nonce-quiet-return',
            'state' => 'quiet-return',
        ));
        $quietResource = LTI13::parse_jwt($quietSent['id_token'], false);
        $this->assertIsObject($quietResource);
        $this->assertObjectNotHasProperty(LTI13::ENDPOINT_CLAIM, $quietResource->body);

        $rejected = false;
        try {
            Lti13TestLaunch::acceptReturn($response.'x', $details['key']);
        } catch ( \InvalidArgumentException $e ) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(bool $resourceLink = false, bool $privacy = false): array
    {
        $messages = array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://client.example.org/launch',
                'label' => 'Add a virtual garden',
                'placements' => array('link_selection'),
            ),
        );
        if ( $resourceLink ) {
            array_unshift($messages, array(
                'type' => 'LtiResourceLinkRequest',
                'target_link_uri' => 'https://client.example.org/resource',
                'label' => 'Garden',
            ));
        }
        if ( $privacy ) {
            $messages[] = array(
                'type' => 'LtiDataPrivacyLaunchRequest',
                'target_link_uri' => 'https://client.example.org/privacy',
                'label' => 'Privacy',
            );
        }
        $title = 'Virtual Garden';
        if ( $resourceLink ) {
            $title = 'Resource Garden';
        } else if ( $privacy ) {
            $title = 'Privacy Garden';
        }
        return array(
            'application_type' => 'web',
            'initiate_login_uri' => 'https://client.example.org/lti',
            'redirect_uris' => array(
                'https://client.example.org/callback',
                'https://client.example.org/callback2',
            ),
            'client_name' => $title,
            'jwks_uri' => 'https://client.example.org/jwks.json',
            'scope' => ToolRegistrationDocument::SCOPE_SCORE.' '.ToolRegistrationDocument::SCOPE_ROSTER,
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'domain' => 'client.example.org',
                'target_link_uri' => 'https://client.example.org/launch',
                'claims' => array('iss', 'sub', 'name', 'email'),
                'messages' => $messages,
            ),
        );
    }

    private function registrationId(string $title): int
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
        $this->assertIsArray($row);
        return (int) $row['registration_id'];
    }
}
