<?php

use Tsugi\Services\Settings\DynamicRegistration;

class DynamicRegistrationTest extends \PHPUnit\Framework\TestCase
{
    public function testSakaiPlusIsOneResourceLink(): void
    {
        $platform = $this->platform('sakailms.org', array(
            $this->messageType('LtiResourceLinkRequest'),
            $this->messageType('LtiDeepLinkingRequest'),
        ));

        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), $platform);

        $this->assertCount(1, $messages);
        $this->assertSame('LtiResourceLinkRequest', $messages[0]['type']);
        $this->assertSame('https://tool.example/plus', $messages[0]['target_link_uri']);
        $this->assertArrayNotHasKey('placements', $messages[0]);
    }

    public function testSakaiWithoutPlusIsOneStoreDeepLink(): void
    {
        $platform = $this->platform('sakai', array(
            $this->messageType('LtiDeepLinkingRequest'),
            $this->messageType('LtiResourceLinkRequest'),
        ));
        $messages = array();
        foreach ( $this->registrationMessages() as $message ) {
            if ( $message['type'] === 'LtiResourceLinkRequest' ) {
                continue;
            }
            $messages[] = $message;
        }

        $out = DynamicRegistration::messagesForPlatform($messages, $platform);

        $this->assertCount(1, $out);
        $this->assertSame('LtiDeepLinkingRequest', $out[0]['type']);
        $this->assertSame('https://tool.example/store/', $out[0]['target_link_uri']);
        $this->assertArrayNotHasKey('placements', $out[0]);
    }

    public function testCanvasDropsNamesItsSchemaRejects(): void
    {
        $deep = array(
            'https://canvas.instructure.com/lti/assignment_selection',
            'https://canvas.instructure.com/lti/editor_button',
            'https://canvas.instructure.com/lti/link_selection',
            'https://canvas.instructure.com/lti/migration_selection',
            'ContentArea',
            'RichTextEditor',
        );
        $platform = $this->platform('canvas', array(
            $this->messageType('LtiResourceLinkRequest', array(
                'https://canvas.instructure.com/lti/course_navigation',
                'ContentArea',
            )),
            $this->messageType('LtiDeepLinkingRequest', $deep),
            $this->messageType('LtiEulaRequest'),
        ));
        $platform->issuer = 'https://canvas.instructure.com';
        $platform->registration_endpoint = 'https://sso.canvaslms.com/api/lti/registrations';

        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), $platform);
        $types = array_column($messages, 'type');

        $this->assertNotContains('LtiDataPrivacyLaunchRequest', $types);
        $this->assertSame(
            array('ContentArea', 'link_selection', 'assignment_selection'),
            $messages[0]['placements']
        );
        $this->assertSame('https://tool.example/store/', $messages[0]['target_link_uri']);
        $this->assertSame(array('migration_selection'), $this->messagePlacements($messages, 'Migration'));
        $this->assertSame(
            array('RichTextEditor', 'editor_button'),
            $this->messagePlacements($messages, 'Editor')
        );
        $plus = $this->messageByLabel($messages, 'Plus');
        $this->assertSame('LtiResourceLinkRequest', $plus['type']);
        $this->assertSame(array('course_navigation'), $plus['placements']);
        $this->assertFalse($plus['https://canvas.instructure.com/lti/course_navigation/default_enabled']);
    }

    public function testPlusWithoutCourseNavigationIsNotADeepLink(): void
    {
        $platform = $this->platform('sakai', array(
            $this->messageType('LtiResourceLinkRequest', array('ContentArea', 'lessons')),
            $this->messageType('LtiDeepLinkingRequest', array('ContentArea')),
        ));
        $messages = DynamicRegistration::messagesForPlatform(array(
            array(
                'type' => 'LtiResourceLinkRequest',
                'label' => 'Plus',
                'target_link_uri' => 'https://tool.example/plus',
                'placements' => array('course_navigation'),
            ),
        ), $platform);

        $this->assertCount(1, $messages);
        $this->assertSame('LtiResourceLinkRequest', $messages[0]['type']);
        $this->assertArrayNotHasKey('placements', $messages[0]);
    }

    public function testCanvasOmitsAMessageWhosePlacementsAreAllRejected(): void
    {
        $platform = $this->platform('canvas', array(
            $this->messageType('LtiDeepLinkingRequest', array(
                'https://canvas.instructure.com/lti/link_selection',
            )),
        ));
        $messages = DynamicRegistration::messagesForPlatform(array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Lessons only',
                'placements' => array('lessons', 'content_editor'),
            ),
        ), $platform);

        $this->assertSame(array(), $messages);
    }

    public function testPlatformWithoutMessagesSupportedKeepsEveryMessage(): void
    {
        $platform = $this->platform('canvas', null);
        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), $platform);

        $this->assertSame(
            array('ContentArea', 'link_selection', 'assignment_selection', 'lessons'),
            $messages[0]['placements']
        );
        $this->assertContains('LtiDataPrivacyLaunchRequest', array_column($messages, 'type'));
        $this->assertSame(
            array('RichTextEditor', 'editor_button', 'content_editor'),
            $this->messagePlacements($messages, 'Editor')
        );
        $this->assertSame('Deep Link Again', $this->messageByLabel($messages, 'Deep Link Again')['label']);
    }

    public function testCanvasDropsUnplacedDuplicateAndKeepsShortNames(): void
    {
        $platform = $this->platform('canvas', array(
            $this->messageType('LtiDeepLinkingRequest', array(
                'https://canvas.instructure.com/lti/link_selection',
                'ContentArea',
            )),
            $this->messageType('LtiResourceLinkRequest', array(
                'https://canvas.instructure.com/lti/course_navigation',
            )),
        ));
        $messages = DynamicRegistration::messagesForPlatform(array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Content',
                'target_link_uri' => 'https://tool.example/store/',
                'placements' => array('ContentArea', 'lessons'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Link',
                'target_link_uri' => 'https://tool.example/store/?type=link_selection',
                'placements' => array('link_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Link copy',
                'target_link_uri' => 'https://tool.example/store/?type=link_selection',
                'placements' => array('link_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Same target without placements',
                'target_link_uri' => 'https://tool.example/store/',
            ),
            array(
                'type' => 'LtiResourceLinkRequest',
                'label' => 'Plus',
                'target_link_uri' => 'https://tool.example/plus',
            ),
        ), $platform);

        $labels = array_column($messages, 'label');
        $this->assertSame(array('Content', 'Link', 'Plus'), $labels);
        $this->assertSame(array('ContentArea'), $messages[0]['placements']);
        $this->assertSame(array('link_selection'), $messages[1]['placements']);
    }

    public function testFailureReportShowsTheLmsBodyAndRedactsTokens(): void
    {
        $report = DynamicRegistration::registrationFailureReport(
            422,
            '{"errors":["lessons is not allowed"]}',
            "HTTP/1.1 422 Unprocessable Content\r\nAuthorization: Bearer secret-token\r\n"
        );

        $this->assertStringContainsString('Http response code: 422', $report);
        $this->assertStringContainsString('lessons is not allowed', $report);
        $this->assertStringContainsString('Response headers:', $report);
        $this->assertStringContainsString('Authorization: Bearer [redacted]', $report);
        $this->assertStringNotContainsString('secret-token', $report);
        $this->assertStringNotContainsString('initiate_login_uri', $report);
        $this->assertSame(
            'https://canvas.example/config?registration_token=[redacted]&x=1',
            DynamicRegistration::redactSecrets('https://canvas.example/config?registration_token=eyJsecret&x=1')
        );
    }

    public function testCanvasDeploymentIdIsOnTheResponse(): void
    {
        $response = new \stdClass();
        $response->{'https://purl.imsglobal.org/spec/lti-tool-configuration'} = new \stdClass();
        $response->deployment_id = '672:43090cc0c11063fdf5b270094da05ed6e23ea6ad';

        $this->assertSame(
            '672:43090cc0c11063fdf5b270094da05ed6e23ea6ad',
            DynamicRegistration::deploymentIdFromResponse($response)
        );
    }

    public function testToolConfigurationDeploymentIdWins(): void
    {
        $tool = new \stdClass();
        $tool->deployment_id = '1';
        $response = new \stdClass();
        $response->{'https://purl.imsglobal.org/spec/lti-tool-configuration'} = $tool;
        $response->deployment_id = '672:elsewhere';

        $this->assertSame('1', DynamicRegistration::deploymentIdFromResponse($response));
        $this->assertNull(DynamicRegistration::deploymentIdFromResponse(new \stdClass()));
    }

    public function testMissingPlatformKeepsTheFullRegistration(): void
    {
        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), null);
        $this->assertCount(6, $messages);
        $this->assertSame('LtiDataPrivacyLaunchRequest', $messages[1]['type']);
        $this->assertSame('Deep Link Again', $messages[5]['label']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function registrationMessages(): array
    {
        return array(
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Deep Link',
                'target_link_uri' => 'https://tool.example/store/',
                'placements' => array('ContentArea', 'link_selection', 'assignment_selection', 'lessons'),
            ),
            array(
                'type' => 'LtiDataPrivacyLaunchRequest',
                'label' => 'Privacy',
                'target_link_uri' => 'https://tool.example/privacy',
            ),
            array(
                'type' => 'LtiResourceLinkRequest',
                'label' => 'Plus',
                'target_link_uri' => 'https://tool.example/plus',
                'placements' => array('course_navigation'),
                'https://canvas.instructure.com/lti/course_navigation/default_enabled' => false,
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Migration',
                'placements' => array('migration_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Editor',
                'placements' => array('RichTextEditor', 'editor_button', 'content_editor'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Deep Link Again',
                'target_link_uri' => 'https://tool.example/store/',
            ),
        );
    }

    /**
     * @param array<int, string>|null $placements
     * @return object
     */
    private function messageType($type, $placements = null)
    {
        $row = new \stdClass();
        $row->type = $type;
        if ( is_array($placements) ) {
            $row->placements = $placements;
        }
        return $row;
    }

    /**
     * @param array<int, object>|null $messages
     * @return object
     */
    private function platform($family, $messages)
    {
        $block = new \stdClass();
        $block->product_family_code = $family;
        if ( is_array($messages) ) {
            $block->messages_supported = $messages;
        }
        $platform = new \stdClass();
        $platform->{'https://purl.imsglobal.org/spec/lti-platform-configuration'} = $block;
        return $platform;
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, string>
     */
    private function messagePlacements(array $messages, $label)
    {
        return $this->messageByLabel($messages, $label)['placements'];
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array<string, mixed>
     */
    private function messageByLabel(array $messages, $label)
    {
        foreach ( $messages as $message ) {
            if ( $message['label'] === $label ) {
                return $message;
            }
        }
        $this->fail('Missing message '.$label);
    }
}
