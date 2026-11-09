<?php

use Tsugi\Services\Settings\DynamicRegistration;

class DynamicRegistrationTest extends \PHPUnit\Framework\TestCase
{
    public function testSakaiKeepsExtensionPlacements(): void
    {
        $platform = $this->platform('sakai', array(
            $this->messageType('LtiDeepLinkingRequest'),
            $this->messageType('LtiDataPrivacyLaunchRequest'),
        ));

        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), $platform);

        $this->assertSame(
            array('ContentArea', 'lessons'),
            $messages[0]['placements']
        );
        $this->assertSame('LtiDataPrivacyLaunchRequest', $messages[1]['type']);
        $this->assertSame(
            array('RichTextEditor', 'editor_button', 'content_editor'),
            $this->messagePlacements($messages, 'Editor')
        );
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
        $this->assertSame(array('ContentArea'), $messages[0]['placements']);
        $this->assertSame(array('migration_selection'), $this->messagePlacements($messages, 'Migration'));
        $this->assertSame(array('link_selection'), $this->messagePlacements($messages, 'Link'));
        $this->assertSame(
            array('RichTextEditor', 'editor_button'),
            $this->messagePlacements($messages, 'Editor')
        );
        $this->assertSame(array('assignment_selection'), $this->messagePlacements($messages, 'Assignment'));
        $this->assertSame('LtiResourceLinkRequest', $this->messageByLabel($messages, 'Plus')['type']);
        $this->assertArrayNotHasKey('placements', $this->messageByLabel($messages, 'Plus'));
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

    public function testCanvasWithoutAdvertisedPlacementsStillDropsExtensions(): void
    {
        $platform = $this->platform('canvas', null);
        $platform->issuer = 'https://school.example.edu';

        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), $platform);

        $this->assertSame(array('ContentArea'), $messages[0]['placements']);
        $this->assertNotContains(
            'LtiDataPrivacyLaunchRequest',
            array_column($messages, 'type')
        );
        $this->assertSame(
            array('RichTextEditor', 'editor_button'),
            $this->messagePlacements($messages, 'Editor')
        );
    }

    public function testMissingPlatformKeepsTheFullRegistration(): void
    {
        $messages = DynamicRegistration::messagesForPlatform($this->registrationMessages(), null);
        $this->assertCount(7, $messages);
        $this->assertSame('LtiDataPrivacyLaunchRequest', $messages[1]['type']);
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
                'placements' => array('ContentArea', 'lessons'),
            ),
            array(
                'type' => 'LtiDataPrivacyLaunchRequest',
                'label' => 'Privacy',
            ),
            array(
                'type' => 'LtiResourceLinkRequest',
                'label' => 'Plus',
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Migration',
                'placements' => array('migration_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Link',
                'placements' => array('link_selection'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Editor',
                'placements' => array('RichTextEditor', 'editor_button', 'content_editor'),
            ),
            array(
                'type' => 'LtiDeepLinkingRequest',
                'label' => 'Assignment',
                'placements' => array('assignment_selection'),
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
