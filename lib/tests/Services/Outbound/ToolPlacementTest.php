<?php

use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolMessagePlacement;
use Tsugi\Services\Outbound\ToolPlacementService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class ToolPlacementTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        $p = $this->p();
        if ( $PDOX->metadata($p.'lti_tool_message_placement') === false
            || $PDOX->metadata($p.'lti_tool_deployment_placement') === false
            || ! $PDOX->columnExists('key_id', $p.'lti_tool_message') ) {
            $this->markTestSkipped('Message placement tables are missing. Run php admin/upgrade.php.');
        }
    }

    public function testOneMessageCanHaveSeveralPlacements(): void
    {
        $messageId = $this->message($this->registrationOn($this->id['keyA'], 'Picker'), 'LtiDeepLinkingRequest', 'https://tool.example/select');
        $first = ToolPlacementService::addPlacementToMessage($messageId, 'assignment_selection');
        $second = ToolPlacementService::addPlacementToMessage($messageId, 'link_selection');
        $third = ToolPlacementService::addPlacementToMessage($messageId, 'content_editor');

        $rows = ToolPlacementService::getPlacementsForMessage($messageId);
        $this->assertSame(
            array('assignment_selection', 'content_editor', 'link_selection'),
            $this->placementNames($rows)
        );
        $this->assertSame(array($first, $third, $second), array_column($rows, 'message_placement_id'));
        $this->assertSame($this->id['keyA'], $rows[0]['key_id']);
    }

    public function testDuplicatePlacementOnTheSameMessageIsRejected(): void
    {
        $messageId = $this->message($this->registrationOn($this->id['keyA'], 'Picker'), 'LtiDeepLinkingRequest', 'https://tool.example/select');
        ToolPlacementService::addPlacementToMessage($messageId, 'assignment_selection');
        $this->expectRejection(function () use ($messageId) {
            ToolPlacementService::addPlacementToMessage($messageId, 'assignment_selection');
        });
        $p = $this->p();
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_message_placement
                (message_id, registration_id, key_id, placement, created_at)
             VALUES (:message_id, :registration_id, :key_id, 'assignment_selection', NOW())",
            array(
                ':message_id' => $messageId,
                ':registration_id' => $this->registrationIdForMessage($messageId),
                ':key_id' => $this->id['keyA'],
            ),
            'lti_tool_message_placement_const_1'
        );
    }

    public function testTwoMessagesOfTheSameTypeKeepTheirOwnPlacements(): void
    {
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/foo',
                    'placements' => array('assignment_selection'),
                ),
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/bar',
                    'placements' => array('content_editor'),
                ),
            ),
        ));
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertCount(2, $messages);
        $this->assertSame('LtiDeepLinkingRequest', $messages[0]['message_type']);
        $this->assertSame('LtiDeepLinkingRequest', $messages[1]['message_type']);
        $this->assertSame(
            array('assignment_selection'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messages[0]['message_id']))
        );
        $this->assertSame(
            array('content_editor'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messages[1]['message_id']))
        );
    }

    public function testDeploymentsEnableDifferentSubsets(): void
    {
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/select',
                    'placements' => array('assignment_selection', 'link_selection', 'content_editor'),
                ),
            ),
        ));
        $available = $this->placementNames(
            ToolPlacementService::getAvailablePlacementsForDeployment(
                $this->deployment($registrationId, 'one')
            )
        );
        $this->assertSame(array('assignment_selection', 'content_editor', 'link_selection'), $available);

        $byName = $this->byPlacement(ToolPlacementService::getPlacementsForMessage(
            ToolRegistrationDocument::messagesForRegistration($registrationId)[0]['message_id']
        ));
        $first = $this->deployment($registrationId, 'first');
        $second = $this->deployment($registrationId, 'second');
        ToolPlacementService::enablePlacementForDeployment($first, $byName['assignment_selection']);
        ToolPlacementService::enablePlacementForDeployment($first, $byName['content_editor']);
        ToolPlacementService::enablePlacementForDeployment($second, $byName['link_selection']);

        $this->assertSame(
            array('assignment_selection', 'content_editor'),
            $this->placementNames(ToolPlacementService::getEnabledPlacementsForDeployment($first))
        );
        $this->assertSame(
            array('link_selection'),
            $this->placementNames(ToolPlacementService::getEnabledPlacementsForDeployment($second))
        );
        $this->assertSame($available, $this->placementNames(
            ToolPlacementService::getAvailablePlacementsForDeployment($first)
        ));
    }

    public function testDuplicateEnablementIsRejected(): void
    {
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        $messageId = $this->message($registrationId, 'LtiDeepLinkingRequest', 'https://tool.example/select');
        $placementId = ToolPlacementService::addPlacementToMessage($messageId, 'assignment_selection');
        $deploymentId = $this->deployment($registrationId, 'once');
        ToolPlacementService::enablePlacementForDeployment($deploymentId, $placementId);
        $this->expectRejection(function () use ($deploymentId, $placementId) {
            ToolPlacementService::enablePlacementForDeployment($deploymentId, $placementId);
        });
    }

    public function testDeploymentCannotEnableAnotherRegistration(): void
    {
        $left = $this->registrationOn($this->id['keyA'], 'Left');
        $right = $this->registrationOn($this->id['keyA'], 'Right');
        $placementId = ToolPlacementService::addPlacementToMessage(
            $this->message($left, 'LtiDeepLinkingRequest', 'https://tool.example/select'),
            'assignment_selection'
        );
        $deploymentId = $this->deployment($right, 'other-reg');
        $this->expectRejection(function () use ($deploymentId, $placementId) {
            ToolPlacementService::enablePlacementForDeployment($deploymentId, $placementId);
        });
    }

    public function testDeploymentCannotEnableAcrossTenants(): void
    {
        $home = $this->registrationOn($this->id['keyA'], 'Home');
        $other = $this->registrationOn($this->id['keyB'], 'Other');
        $placementId = ToolPlacementService::addPlacementToMessage(
            $this->message($other, 'LtiDeepLinkingRequest', 'https://tool.example/select'),
            'ContentArea'
        );
        $deploymentId = $this->deployment($home, 'home-deploy');
        $this->expectRejection(function () use ($deploymentId, $placementId) {
            ToolPlacementService::enablePlacementForDeployment($deploymentId, $placementId);
        });

        $p = $this->p();
        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_deployment_placement
                (tool_deployment_id, message_placement_id, registration_id, key_id, created_at)
             VALUES (:tool_deployment_id, :message_placement_id, :registration_id, :key_id, NOW())",
            array(
                ':tool_deployment_id' => $deploymentId,
                ':message_placement_id' => $placementId,
                ':registration_id' => $home,
                ':key_id' => $this->id['keyA'],
            ),
            'lti_tool_deployment_placement_ibfk_2'
        );
    }

    public function testDeletingAMessageOrPlacementRemovesEnablement(): void
    {
        global $PDOX;
        $p = $this->p();
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        $messageId = $this->message($registrationId, 'LtiDeepLinkingRequest', 'https://tool.example/select');
        $kept = ToolPlacementService::addPlacementToMessage($messageId, 'assignment_selection');
        $gone = ToolPlacementService::addPlacementToMessage($messageId, 'link_selection');
        $deploymentId = $this->deployment($registrationId, 'live');
        ToolPlacementService::enablePlacementForDeployment($deploymentId, $kept);
        ToolPlacementService::enablePlacementForDeployment($deploymentId, $gone);

        ToolPlacementService::removePlacementFromMessage($messageId, 'link_selection');
        $this->assertSame(
            array('assignment_selection'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messageId))
        );
        $this->assertSame(
            array('assignment_selection'),
            $this->placementNames(ToolPlacementService::getEnabledPlacementsForDeployment($deploymentId))
        );

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_message WHERE message_id = :message_id",
            array(':message_id' => $messageId)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        $this->assertSame(array(), ToolPlacementService::getEnabledPlacementsForDeployment($deploymentId));
        $left = $PDOX->rowDie(
            "SELECT message_placement_id FROM {$p}lti_tool_message_placement
             WHERE message_placement_id = :message_placement_id",
            array(':message_placement_id' => $kept)
        );
        $this->assertFalse(is_array($left));
        $this->assertTrue($this->deploymentExists($deploymentId));
    }

    public function testImportCreatesOneMessageAndItsPlacementRows(): void
    {
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/select',
                    'placements' => array('assignment_selection', 'link_selection', 'content_editor'),
                ),
            ),
        ));
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertCount(1, $messages);
        $this->assertSame(
            array('assignment_selection', 'link_selection', 'content_editor'),
            $messages[0]['message_json']['placements']
        );
        $this->assertSame(
            array('assignment_selection', 'content_editor', 'link_selection'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messages[0]['message_id']))
        );
    }

    public function testPlacementRulesAcceptKnownPairsAndRejectKnownMismatches(): void
    {
        $registrationId = $this->registrationOn($this->id['keyA'], 'Picker');
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/select',
                ),
                array(
                    'type' => 'LtiResourceLinkRequest',
                    'target_link_uri' => 'https://tool.example/launch',
                ),
            ),
        ));
        $stored = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $deep = (int) $stored[0]['message_id'];
        $launch = (int) $stored[1]['message_id'];
        ToolPlacementService::addPlacementToMessage($deep, 'assignment_selection');
        ToolPlacementService::addPlacementToMessage($deep, 'ContentArea');
        ToolPlacementService::addPlacementToMessage($deep, 'lessons');
        ToolPlacementService::addPlacementToMessage($launch, 'course_navigation');
        $this->assertSame('course_navigation', ToolMessagePlacement::assertCompatible('LtiResourceLinkRequest', 'course_navigation'));

        $this->expectRejection(function () use ($deep) {
            ToolPlacementService::addPlacementToMessage($deep, 'course_navigation');
        });
        $this->expectRejection(function () use ($launch) {
            ToolPlacementService::addPlacementToMessage($launch, 'content_editor');
        });
        $this->expectRejection(function () use ($registrationId) {
            ToolRegistrationDocument::storeDocument($registrationId, array(
                'messages' => array(
                    array(
                        'type' => 'LtiDeepLinkingRequest',
                        'placements' => array('assignment_selection', 'assignment_selection'),
                    ),
                ),
            ));
        });
    }

    public function testLti11CheckboxesSynthesizeMessageRows(): void
    {
        $plain = ToolRegistrationService::createRegistration($this->id['keyA'], 'Plain launch', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'plain-key',
            'lti11_secret' => 'plain-secret',
            'lti11_url' => 'https://tool.example/launch',
        ));
        $plainMessages = ToolRegistrationDocument::messagesForRegistration($plain);
        $this->assertCount(1, $plainMessages);
        $this->assertSame('LtiResourceLinkRequest', $plainMessages[0]['message_type']);
        $this->assertSame('https://tool.example/launch', $plainMessages[0]['target_link_uri']);
        $this->assertSame(array(), ToolPlacementService::getPlacementsForMessage($plainMessages[0]['message_id']));
        $plainDocument = ToolRegistrationDocument::registrationDocument($plain);
        $this->assertSame(
            array('iss', 'sub'),
            $plainDocument[ToolRegistrationDocument::TOOL_CONFIGURATION]['claims']
        );
        $this->assertArrayNotHasKey('scope', $plainDocument);

        $registrationId = ToolRegistrationService::createRegistration($this->id['keyA'], 'Checked', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'checked-key',
            'lti11_secret' => 'checked-secret',
            'lti11_url' => 'https://tool.example/launch',
            'messages' => array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest', 'LtiDataPrivacyLaunchRequest'),
            'placements' => array('lessons', 'content_editor', 'assignment_selection', 'course_navigation', 'common_cartridge'),
            'claims' => array('email', 'name', 'given_name', 'family_name'),
            'scopes' => array(
                'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
                'https://purl.imsglobal.org/spec/lti-ags/scope/score',
                'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
            ),
        ));
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertCount(3, $messages);
        $this->assertSame('LtiResourceLinkRequest', $messages[0]['message_type']);
        $this->assertSame('https://tool.example/launch', $messages[0]['target_link_uri']);
        $this->assertSame(
            array('course_navigation'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messages[0]['message_id']))
        );
        $this->assertSame('LtiDeepLinkingRequest', $messages[1]['message_type']);
        $this->assertSame(
            array('assignment_selection', 'common_cartridge', 'content_editor', 'lessons'),
            $this->placementNames(ToolPlacementService::getPlacementsForMessage($messages[1]['message_id']))
        );
        $this->assertSame('LtiDataPrivacyLaunchRequest', $messages[2]['message_type']);
        $this->assertSame(array(), ToolPlacementService::getPlacementsForMessage($messages[2]['message_id']));
        $document = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertSame(
            array('iss', 'sub', 'name', 'given_name', 'family_name', 'email'),
            $document[ToolRegistrationDocument::TOOL_CONFIGURATION]['claims']
        );
        $this->assertSame(
            'https://purl.imsglobal.org/spec/lti-ags/scope/score'
            .' https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'
            .' https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly'
            .' https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
            $document['scope']
        );
        $deploymentId = $this->onlyDeployment($registrationId);
        $this->assertSame(
            array('course_navigation', 'assignment_selection', 'common_cartridge', 'content_editor', 'lessons'),
            $this->placementNames(ToolPlacementService::getAvailablePlacementsForDeployment($deploymentId))
        );
        $this->assertSame(array(), ToolPlacementService::getEnabledPlacementsForDeployment($deploymentId));

        $deepOnly = ToolRegistrationService::createRegistration($this->id['keyA'], 'Picker only', null, null, array(
            'lti_version' => '1.1',
            'lti11_key' => 'deep-key',
            'lti11_secret' => 'deep-secret',
            'lti11_url' => 'https://tool.example/pick',
            'messages' => array('LtiDeepLinkingRequest'),
            'placements' => array('lessons'),
        ));
        $deepMessages = ToolRegistrationDocument::messagesForRegistration($deepOnly);
        $this->assertSame(array('LtiDeepLinkingRequest'), array_column($deepMessages, 'message_type'));
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Nav without launch', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'nav-key',
                'lti11_secret' => 'nav-secret',
                'lti11_url' => 'https://tool.example/launch',
                'messages' => array('LtiDeepLinkingRequest'),
                'placements' => array('course_navigation'),
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Editor without deep link', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'editor-key',
                'lti11_secret' => 'editor-secret',
                'lti11_url' => 'https://tool.example/launch',
                'messages' => array('LtiResourceLinkRequest'),
                'placements' => array('content_editor'),
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'No launch', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'none-key',
                'lti11_secret' => 'none-secret',
                'lti11_url' => 'https://tool.example/launch',
                'messages' => array('LtiDataPrivacyLaunchRequest'),
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Bad service', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'bad-service-key',
                'lti11_secret' => 'bad-service-secret',
                'lti11_url' => 'https://tool.example/launch',
                'scopes' => array('pl_privacy'),
            ));
        });

        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Unknown box', null, null, array(
                'lti_version' => '1.1',
                'lti11_key' => 'unknown-key',
                'lti11_secret' => 'unknown-secret',
                'lti11_url' => 'https://tool.example/launch',
                'placements' => array('lessons'),
            ));
        });
        $this->expectRejection(function () {
            ToolRegistrationService::createRegistration($this->id['keyA'], 'Document tool', null, null, array(
                'lti_version' => '1.3',
                'placements' => array('course_navigation'),
            ));
        });
    }

    private function registrationOn(int $keyId, string $title): int
    {
        return ToolRegistrationService::createRegistration($keyId, $title);
    }

    private function message(int $registrationId, string $type, string $target): int
    {
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $document = array('messages' => array());
        foreach ( $messages as $message ) {
            $document['messages'][] = $message['message_json'];
        }
        $document['messages'][] = array(
            'type' => $type,
            'target_link_uri' => $target,
        );
        ToolRegistrationDocument::storeDocument($registrationId, $document);
        $stored = ToolRegistrationDocument::messagesForRegistration($registrationId);
        return (int) $stored[count($stored) - 1]['message_id'];
    }

    private function deployment(int $registrationId, string $deploymentId): int
    {
        return ToolDeploymentService::createDeployment($registrationId, $deploymentId);
    }

    private function onlyDeployment(int $registrationId): int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT tool_deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        $this->assertIsArray($row);
        return (int) $row['tool_deployment_id'];
    }

    private function registrationIdForMessage(int $messageId): int
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT registration_id FROM {$this->p()}lti_tool_message WHERE message_id = :message_id",
            array(':message_id' => $messageId)
        );
        $this->assertIsArray($row);
        return (int) $row['registration_id'];
    }

    private function deploymentExists(int $toolDeploymentId): bool
    {
        global $PDOX;
        $row = $PDOX->rowDie(
            "SELECT tool_deployment_id FROM {$this->p()}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        return is_array($row);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function placementNames(array $rows): array
    {
        $names = array();
        foreach ( $rows as $row ) {
            $names[] = (string) $row['placement'];
        }
        return $names;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function byPlacement(array $rows): array
    {
        $out = array();
        foreach ( $rows as $row ) {
            $out[(string) $row['placement']] = (int) $row['message_placement_id'];
        }
        return $out;
    }

    private function expectRejection(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected the change to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertNotSame('', $ex->getMessage());
        }
    }
}
