<?php

use Tsugi\Services\Outbound\Lti11TestLaunch;
use Tsugi\Services\Outbound\Lti13TestLaunch;
use Tsugi\Services\Outbound\LtiContentService;
use Tsugi\Services\Outbound\LtiLessonPlacement;
use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;
use Tsugi\Util\LTI13;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class LtiLessonPlacementTest extends PlatformSchemaCase
{
    public function testLessonChoicesKeepLessonAndBlankMessages(): void
    {
        $deploymentId = $this->py4e();
        $choices = LtiLessonPlacement::choices($this->id['eecs280'], $deploymentId);
        $this->assertSame(
            array('PY4E select item', 'PY4E (privacy)', 'Install PY4E - Trophy (resource link)'),
            array_column($choices, 'label')
        );
        $this->assertSame(array('select', 'install', 'install'), array_column($choices, 'action'));
        $this->assertSame('', $choices[0]['detail']);
        $this->assertSame('', $choices[1]['detail']);
        $this->assertSame('', $choices[2]['detail']);
        $this->assertSame('PY4E', $choices[1]['name']);
        $this->assertSame('PY4E - Trophy', $choices[2]['name']);
        $this->assertSame('PY4E - Trophy', LtiLessonPlacement::installName($this->id['eecs280'], $deploymentId, $choices[2]['message_id']));
        $this->assertSame('https://tool.example/select', $choices[0]['launch']);
        $this->assertSame('https://tool.example/privacy', $choices[1]['launch']);
        $this->assertSame('https://tool.example/trophy', $choices[2]['launch']);
        $this->assertNotContains('https://tool.example/later', array_column($choices, 'launch'));
        $this->assertNotContains('https://tool.example/assign', array_column($choices, 'launch'));
        $this->assertNotContains('https://tool.example/editor', array_column($choices, 'launch'));
        $this->assertNotContains('https://tool.example/nav', array_column($choices, 'launch'));

        $this->assertSame(
            'https://tool.example/trophy',
            LtiLessonPlacement::installTarget($this->id['eecs280'], $deploymentId, $choices[2]['message_id'])
        );
        $this->assertSame(
            'https://tool.example/privacy',
            LtiLessonPlacement::installTarget($this->id['eecs280'], $deploymentId, $choices[1]['message_id'])
        );
        try {
            LtiLessonPlacement::installTarget($this->id['eecs280'], $deploymentId, $choices[0]['message_id']);
            $this->fail('A deep link is not installed from its own target.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('lesson placement', $ex->getMessage());
        }

        $listed = array();
        foreach ( Lti11TestLaunch::lessonChoices($this->id['eecs280']) as $choice ) {
            $listed[$choice['id']] = $choice;
        }
        $this->assertArrayHasKey($deploymentId, $listed);
        $this->assertSame('1.3', $listed[$deploymentId]['lti_version']);
        $this->assertSame('', $listed[$deploymentId]['launch']);
        $this->assertCount(3, $listed[$deploymentId]['placements']);

        $onlyEditor = $this->deploymentWith(array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/editor-only',
                'label' => 'Editor',
                'placements' => array('editor_button'),
            ),
        ), 'Editor only');
        $editorIds = array();
        foreach ( Lti11TestLaunch::lessonChoices($this->id['eecs280']) as $choice ) {
            $editorIds[] = $choice['id'];
        }
        $this->assertNotContains($onlyEditor, $editorIds);

        $classicId = ToolRegistrationService::createLti11ForCourse($this->id['eecs280'], 'Classic quiz', null, array(
            'lti11_key' => 'classic-'.bin2hex(random_bytes(4)),
            'lti11_secret' => 'classic-secret',
            'lti11_url' => 'https://tool.example/classic',
        ));
        $classicDeployment = ToolDeploymentService::onlyDeploymentId($classicId);
        $again = array();
        foreach ( Lti11TestLaunch::lessonChoices($this->id['eecs280']) as $choice ) {
            $again[$choice['id']] = $choice;
        }
        $this->assertSame('1.1', $again[$classicDeployment]['lti_version']);
        $this->assertSame('https://tool.example/classic', $again[$classicDeployment]['launch']);
        $this->assertSame(array(), $again[$classicDeployment]['placements']);

        $elsewhere = array();
        foreach ( Lti11TestLaunch::lessonChoices($this->id['eecs281']) as $choice ) {
            $elsewhere[] = $choice['id'];
        }
        $this->assertNotContains($deploymentId, $elsewhere);
    }

    public function testInstallStoresTheMessageTarget(): void
    {
        $deploymentId = $this->py4e();
        $choices = LtiLessonPlacement::choices($this->id['eecs280'], $deploymentId);
        $url = LtiLessonPlacement::installTarget($this->id['eecs280'], $deploymentId, $choices[2]['message_id']);
        $content = LtiContentService::placeAt($this->id['eecs280'], $deploymentId, 'Week trophy', $url);
        $this->assertSame('https://tool.example/trophy', $content['launch_url']);
        $this->assertSame($deploymentId, $content['tool_deployment_id']);
        $this->assertSame('Week trophy', $content['title']);
    }

    public function testDeepLinkReturnBecomesTheLaunchUrl(): void
    {
        $deploymentId = $this->py4e();
        $choices = LtiLessonPlacement::choices($this->id['eecs280'], $deploymentId);
        $started = LtiLessonPlacement::selectLaunch(
            $this->id['eecs280'],
            $deploymentId,
            $choices[0]['message_id'],
            424242,
            'https://local.dj4e.com/tsugi/lessons'
        );
        $this->assertSame('https://tool.example/select', $started['parameters']['target_link_uri']);
        $this->assertSame('https://tool.example/login', $started['form_endpoint']);

        $done = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $started['parameters']['client_id'],
            'redirect_uri' => 'https://tool.example/callback',
            'login_hint' => $started['parameters']['login_hint'],
            'lti_message_hint' => $started['parameters']['lti_message_hint'],
            'nonce' => 'nonce-lesson',
            'state' => 'lesson-state',
        ));
        $jwt = LTI13::parse_jwt($done['id_token'], false);
        $this->assertIsObject($jwt);
        $this->assertSame('https://tool.example/select', $jwt->body->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'});

        $key = openssl_pkey_new(array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        $this->assertNotFalse($key);
        $private = '';
        $this->assertTrue(openssl_pkey_export($key, $private));
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);
        $settings = $jwt->body->{LTI13::DEEPLINK_CLAIM};
        $one = $this->returnJwt($private, $started['parameters']['client_id'], $started['parameters']['lti_deployment_id'], $settings->data, array(
            array(
                'type' => 'ltiResourceLink',
                'title' => 'Chosen page',
                'url' => 'https://tool.example/chosen',
            ),
        ));
        $picked = LtiLessonPlacement::returnedTarget(
            $this->id['eecs280'],
            $deploymentId,
            $one,
            null,
            $details['key']
        );
        $this->assertNull($picked['items']);
        $this->assertSame('https://tool.example/chosen', $picked['url']);
        $this->assertSame('Chosen page', $picked['title']);
        $placed = LtiContentService::placeAt($this->id['eecs280'], $deploymentId, '', $picked['url']);
        $this->assertSame('https://tool.example/chosen', $placed['launch_url']);
        $this->assertSame('PY4E', $placed['title']);

        $many = $this->returnJwt($private, $started['parameters']['client_id'], $started['parameters']['lti_deployment_id'], $settings->data, array(
            array('type' => 'ltiResourceLink', 'title' => 'First', 'url' => 'https://tool.example/first'),
            array('type' => 'ltiResourceLink', 'title' => 'Second', 'url' => 'https://tool.example/second'),
        ));
        $choose = LtiLessonPlacement::returnedTarget($this->id['eecs280'], $deploymentId, $many, null, $details['key']);
        $this->assertSame('https://tool.example/second', $choose['items'][1]['url']);
        $second = LtiLessonPlacement::returnedTarget($this->id['eecs280'], $deploymentId, $many, 1, $details['key']);
        $this->assertSame('https://tool.example/second', $second['url']);

        try {
            LtiLessonPlacement::returnedTarget($this->id['eecs281'], $deploymentId, $one, null, $details['key']);
            $this->fail('A return for another course is rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('not for this course', $ex->getMessage());
        }
    }

    public function testPlacedLessonLaunchesAsResourceLink(): void
    {
        $deploymentId = $this->py4e();
        $this->assertFalse(Lti11TestLaunch::hasResourceLink($this->id['eecs280'], $deploymentId));
        $this->assertTrue(LtiContentService::inCourse($this->id['eecs280'], $deploymentId));
        $this->assertSame('1.3', LtiContentService::version($this->id['eecs280'], $deploymentId));

        try {
            LtiContentService::refuseDroppedPost('https://tool.example/mod/trophy', function () {
                return array('code' => 301, 'location' => 'https://tool.example/mod/trophy/');
            });
            $this->fail('A redirect that drops the POST is refused while authoring.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('https://tool.example/mod/trophy/', $ex->getMessage());
        }
        try {
            LtiContentService::refuseDroppedPost('https://tool.example/broken', function () {
                return array('code' => 404, 'location' => '');
            });
            $this->fail('A missing address is refused while authoring.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('not found', $ex->getMessage());
        }
        LtiContentService::refuseDroppedPost('https://tool.example/launch', function () {
            return array('code' => 200, 'location' => '');
        });
        LtiContentService::refuseDroppedPost('https://tool.example/down', function () {
            return null;
        });

        $placed = LtiContentService::placeAt(
            $this->id['eecs280'],
            $deploymentId,
            'PY4E Privacy',
            'https://tool.example/privacy.php'
        );
        $started = Lti13TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            424242,
            $placed['resource_link_id'],
            $placed['title'],
            'https://local.dj4e.com/tsugi/lessons',
            'Learner',
            '',
            false,
            false,
            $placed['launch_url'],
            'iframe',
            '',
            false,
            true
        );
        $this->assertSame('1.3', $started['protocol']);
        $this->assertSame('https://tool.example/login', $started['endpoint']);
        $this->assertSame('https://tool.example/privacy.php', $started['parameters']['target_link_uri']);

        $done = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'client_id' => $started['parameters']['client_id'],
            'redirect_uri' => 'https://tool.example/callback',
            'login_hint' => $started['parameters']['login_hint'],
            'lti_message_hint' => $started['parameters']['lti_message_hint'],
            'lti_deployment_id' => $started['parameters']['lti_deployment_id'],
            'nonce' => 'nonce-lesson',
            'state' => 'lesson-state',
        ));
        $jwt = LTI13::parse_jwt($done['id_token'], false);
        $this->assertIsObject($jwt);
        $body = $jwt->body;
        $this->assertSame('LtiResourceLinkRequest', $body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertSame('https://tool.example/privacy.php', $body->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'});
        $this->assertSame($placed['resource_link_id'], $body->{LTI13::RESOURCE_LINK_CLAIM}->id);
        $this->assertSame('PY4E Privacy', $body->{LTI13::RESOURCE_LINK_CLAIM}->title);
        $this->assertFalse(isset($body->{LTI13::RESOURCE_LINK_CLAIM}->description));
        $this->assertSame('iframe', $body->{LTI13::PRESENTATION_CLAIM}->document_target);
        $this->assertFalse(isset($body->name));
        $this->assertFalse(isset($body->email));
        $this->assertFalse(isset($body->{LTI13::ENDPOINT_CLAIM}));

        $trophy = LtiContentService::placeAt(
            $this->id['eecs280'],
            $deploymentId,
            'PY4E - Trophy',
            'https://tool.example/mod/trophy'
        );
        $trophyLaunch = Lti13TestLaunch::courseResourceLink(
            $this->id['eecs280'],
            $deploymentId,
            424242,
            $trophy['resource_link_id'],
            $trophy['title'],
            'https://local.dj4e.com/tsugi/lessons',
            'Learner',
            '',
            null,
            null,
            $trophy['launch_url']
        );
        $this->assertSame('https://tool.example/mod/trophy', $trophyLaunch['parameters']['target_link_uri']);
        $trophyDone = Lti13TestLaunch::complete(array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'client_id' => $trophyLaunch['parameters']['client_id'],
            'redirect_uri' => 'https://tool.example/callback',
            'login_hint' => $trophyLaunch['parameters']['login_hint'],
            'lti_message_hint' => $trophyLaunch['parameters']['lti_message_hint'],
            'lti_deployment_id' => $trophyLaunch['parameters']['lti_deployment_id'],
            'nonce' => 'nonce-trophy',
            'state' => 'trophy-state',
        ));
        $trophyJwt = LTI13::parse_jwt($trophyDone['id_token'], false);
        $this->assertIsObject($trophyJwt);
        $this->assertSame('LtiResourceLinkRequest', $trophyJwt->body->{LTI13::MESSAGE_TYPE_CLAIM});
        $this->assertSame('https://tool.example/mod/trophy', $trophyJwt->body->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'});
        $this->assertSame($trophy['resource_link_id'], $trophyJwt->body->{LTI13::RESOURCE_LINK_CLAIM}->id);
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     */
    private function deploymentWith(array $messages, string $title): int
    {
        $registrationId = ToolRegistrationService::createRegistration($this->id['keyA'], $title, null, null, array(
            'lti_version' => '1.3',
            'lti13_client_id' => $title.'-'.bin2hex(random_bytes(4)),
            'lti13_oidc_login_url' => 'https://tool.example/login',
            'lti13_jwks_url' => 'https://tool.example/jwks',
            'lti13_launch_url' => 'https://tool.example/launch',
            'lti13_redirect_uri' => 'https://tool.example/callback',
        ));
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => $messages,
        ));
        $deploymentId = ToolDeploymentService::createDeployment($registrationId, 'deploy-'.bin2hex(random_bytes(4)));
        ToolDeploymentService::assignContext($deploymentId, $this->id['eecs280']);
        return $deploymentId;
    }

    private function py4e(): int
    {
        return $this->deploymentWith(array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/assign',
                'label' => 'Assignments',
                'placements' => array('assignment_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/select',
                'label' => 'PY4E',
            ),
            array(
                'type' => 'LtiDataPrivacyLaunchRequest',
                'target_link_uri' => 'https://tool.example/privacy',
                'label' => 'PY4E',
            ),
            array(
                'type' => 'LtiResourceLinkRequest',
                'target_link_uri' => 'https://tool.example/trophy',
                'label' => 'PY4E - Trophy',
                'placements' => array('lessons', 'assignment_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/links',
                'label' => 'Link selection',
                'placements' => array('link_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/editor',
                'label' => 'Editor',
                'placements' => array('editor_button'),
            ),
            array(
                'type' => 'LtiResourceLinkRequest',
                'target_link_uri' => 'https://tool.example/nav',
                'label' => 'Navigation',
                'placements' => array('course_navigation'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'target_link_uri' => 'https://tool.example/later',
                'label' => 'Later',
            ),
        ), 'PY4E');
    }

    /**
     * @param string $private
     * @param string $clientId
     * @param string $deploymentId
     * @param string $data
     * @param array<int, array<string, mixed>> $items
     */
    private function returnJwt(string $private, string $clientId, string $deploymentId, string $data, array $items): string
    {
        return LTI13::encode_jwt(array(
            'iss' => $clientId,
            'aud' => PlatformDynamicRegistration::openIdConfiguration()['issuer'],
            'iat' => time(),
            'exp' => time() + 600,
            'nonce' => 'return-nonce',
            LTI13::MESSAGE_TYPE_CLAIM => 'LtiDeepLinkingResponse',
            LTI13::VERSION_CLAIM => '1.3.0',
            LTI13::DEPLOYMENT_ID_CLAIM => $deploymentId,
            'https://purl.imsglobal.org/spec/lti-dl/claim/data' => $data,
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => $items,
        ), $private, 'tool-key');
    }
}
