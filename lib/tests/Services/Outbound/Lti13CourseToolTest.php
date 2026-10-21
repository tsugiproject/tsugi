<?php

use Tsugi\Services\Outbound\Lti13CourseTool;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolPlacementService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class Lti13CourseToolTest extends PlatformSchemaCase
{
    private const SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    private const LINEITEM_READONLY = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    private const ROSTER = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    public function testTurnOffKeepsTheScopeAndTheMessages(): void
    {
        $made = $this->tool();
        $before = ToolRegistrationDocument::messagesForRegistration($made['registration_id']);
        $placementId = $this->placementId($before[0]['message_id'], 'lessons');

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'scope_off',
            'scope' => self::SCORE,
            'tool_deployment_id' => $made['tool_deployment_id'],
        ));

        $document = ToolRegistrationDocument::registrationDocument($made['registration_id']);
        $this->assertContains(self::SCORE, ToolRegistrationDocument::requestedScopes($document));
        $this->assertNotContains(self::SCORE, ToolDeploymentGrant::allowedScopes($made['tool_deployment_id']));
        $this->assertContains(self::ROSTER, ToolDeploymentGrant::allowedScopes($made['tool_deployment_id']));
        $after = ToolRegistrationDocument::messagesForRegistration($made['registration_id']);
        $this->assertSame($before[0]['message_id'], $after[0]['message_id']);
        $this->assertSame('yes', $after[0]['message_json']['custom_parameters']['keep']);
        $enabled = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($made['tool_deployment_id']) as $row ) {
            $enabled[] = (int) $row['message_placement_id'];
        }
        $this->assertContains($placementId, $enabled);

        $state = Lti13CourseTool::editState($this->id['eecs280'], $made['registration_id']);
        $score = $this->rowNamed($state['scopes'], self::SCORE);
        $this->assertFalse($score['deployments'][0]['on']);

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'scope_on',
            'scope' => self::SCORE,
            'tool_deployment_id' => $made['tool_deployment_id'],
        ));
        $this->assertContains(self::SCORE, ToolDeploymentGrant::allowedScopes($made['tool_deployment_id']));

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'delete_scope',
            'scope' => self::SCORE,
        ));
        $document = ToolRegistrationDocument::registrationDocument($made['registration_id']);
        $this->assertNotContains(self::SCORE, ToolRegistrationDocument::requestedScopes($document));
        $this->assertNotContains(self::SCORE, ToolDeploymentGrant::allowedScopes($made['tool_deployment_id']));
        $this->assertSame($before[0]['message_id'], ToolRegistrationDocument::messagesForRegistration($made['registration_id'])[0]['message_id']);
    }

    public function testAddComesFromTheVocabularyAndStartsOn(): void
    {
        $made = $this->tool();
        $before = Lti13CourseTool::editState($this->id['eecs280'], $made['registration_id']);
        $this->assertContains(self::LINEITEM_READONLY, array_column($before['scope_available'], 'value'));

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'add_scope',
            'scope' => self::LINEITEM_READONLY,
        ));
        $document = ToolRegistrationDocument::registrationDocument($made['registration_id']);
        $this->assertSame(
            self::SCORE.' '.self::LINEITEM_READONLY.' '.self::ROSTER,
            $document['scope']
        );
        $this->assertContains(self::LINEITEM_READONLY, ToolDeploymentGrant::allowedScopes($made['tool_deployment_id']));

        $this->expectRejection(function () use ($made) {
            Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
                'edit_action' => 'add_scope',
                'scope' => 'https://example.com/not-a-scope',
            ));
        });
        $this->expectRejection(function () use ($made) {
            Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
                'edit_action' => 'delete_claim',
                'claim' => 'iss',
            ));
        });
        $this->expectRejection(function () use ($made) {
            Lti13CourseTool::apply($this->id['eecs281'], $made['registration_id'], array(
                'edit_action' => 'scope_off',
                'scope' => self::ROSTER,
                'tool_deployment_id' => $made['tool_deployment_id'],
            ));
        });
    }

    public function testPlacementsTurnOffWithoutLeavingTheMessage(): void
    {
        $made = $this->tool();
        $messages = ToolRegistrationDocument::messagesForRegistration($made['registration_id']);
        $link = $messages[0];
        $deep = $messages[1];
        $lessons = $this->placementId($link['message_id'], 'lessons');

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'placement_off',
            'message_id' => $link['message_id'],
            'message_placement_id' => $lessons,
            'tool_deployment_id' => $made['tool_deployment_id'],
        ));
        $names = array();
        foreach ( ToolPlacementService::getPlacementsForMessage($link['message_id']) as $row ) {
            $names[] = $row['placement'];
        }
        $this->assertContains('lessons', $names);
        $enabled = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($made['tool_deployment_id']) as $row ) {
            $enabled[] = (int) $row['message_placement_id'];
        }
        $this->assertNotContains($lessons, $enabled);

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'delete_placement',
            'message_id' => $link['message_id'],
            'message_placement_id' => $lessons,
        ));
        $names = array();
        foreach ( ToolPlacementService::getPlacementsForMessage($link['message_id']) as $row ) {
            $names[] = $row['placement'];
        }
        $this->assertNotContains('lessons', $names);
        $document = ToolRegistrationDocument::registrationDocument($made['registration_id']);
        $stored = ToolRegistrationDocument::messagesInDocument($document);
        $this->assertArrayNotHasKey('placements', $stored[0]);
        $this->assertSame('yes', $stored[0]['custom_parameters']['keep']);

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'add_placement',
            'message_id' => $link['message_id'],
            'placement' => 'course_navigation',
        ));
        $added = $this->placementId($link['message_id'], 'course_navigation');
        $enabled = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($made['tool_deployment_id']) as $row ) {
            $enabled[] = (int) $row['message_placement_id'];
        }
        $this->assertContains($added, $enabled);

        $this->expectRejection(function () use ($made, $deep) {
            Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
                'edit_action' => 'add_placement',
                'message_id' => $deep['message_id'],
                'placement' => 'course_navigation',
            ));
        });
    }

    public function testPlacementMenuGroupsExtensionsAndStoresACustomString(): void
    {
        $made = $this->tool();
        $state = Lti13CourseTool::editState($this->id['eecs280'], $made['registration_id']);
        $deep = null;
        $link = null;
        foreach ( $state['messages'] as $message ) {
            if ( $message['message_type'] === 'LtiDeepLinkingRequest' ) {
                $deep = $message;
            }
            if ( $message['message_type'] === 'LtiResourceLinkRequest' ) {
                $link = $message;
            }
        }
        $this->assertNotNull($deep);
        $this->assertNotNull($link);

        $deepGroups = $this->groupValues($deep['placement_groups']);
        $this->assertSame(array('ContentArea', 'RichTextEditor'), $deepGroups['Standard LTI']);
        $this->assertContains('link_selection', $deepGroups['Canvas']);
        $this->assertNotContains('course_navigation', $deepGroups['Canvas']);
        $this->assertNotContains('assignment_selection', $deepGroups['Canvas']);
        $this->assertContains('lessons', $deepGroups['Tsugi']);
        $this->assertContains('content_editor', $deepGroups['Tsugi']);
        $this->assertSame('assignment_selection', $deep['placements'][0]['value']);
        $this->assertSame('Canvas', $deep['placements'][0]['group']);
        $this->assertSame('Assignment selection', $deep['placements'][0]['label']);

        $linkGroups = $this->groupValues($link['placement_groups']);
        $this->assertArrayNotHasKey('Standard LTI', $linkGroups);
        $this->assertContains('course_navigation', $linkGroups['Canvas']);
        $this->assertNotContains('lessons', $linkGroups['Tsugi']);
        $this->assertSame('lessons', $link['placements'][0]['value']);
        $this->assertSame('Tsugi', $link['placements'][0]['group']);

        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'add_placement',
            'message_id' => $deep['message_id'],
            'placement' => 'com.example.sidebar',
        ));
        $names = array();
        foreach ( ToolPlacementService::getPlacementsForMessage($deep['message_id']) as $row ) {
            $names[] = $row['placement'];
        }
        $this->assertContains('com.example.sidebar', $names);
        $document = ToolRegistrationDocument::registrationDocument($made['registration_id']);
        $stored = ToolRegistrationDocument::messagesInDocument($document);
        $this->assertContains('com.example.sidebar', $stored[1]['placements']);
        $this->assertContains('assignment_selection', $stored[1]['placements']);

        $again = Lti13CourseTool::editState($this->id['eecs280'], $made['registration_id']);
        $custom = null;
        foreach ( $again['messages'] as $message ) {
            if ( $message['message_type'] !== 'LtiDeepLinkingRequest' ) {
                continue;
            }
            foreach ( $message['placements'] as $placement ) {
                if ( $placement['value'] === 'com.example.sidebar' ) {
                    $custom = $placement;
                }
            }
        }
        $this->assertNotNull($custom);
        $this->assertSame('com.example.sidebar', $custom['label']);
        $this->assertSame('', $custom['group']);
    }

    public function testSavingAMessageKeepsTheRestOfTheDescriptor(): void
    {
        $made = $this->tool();
        $link = ToolRegistrationDocument::messagesForRegistration($made['registration_id'])[0];
        Lti13CourseTool::apply($this->id['eecs280'], $made['registration_id'], array(
            'edit_action' => 'save_message',
            'message_id' => $link['message_id'],
            'label' => 'Renamed',
            'target_link_uri' => 'https://tool.example/launch',
        ));
        $saved = ToolRegistrationDocument::messagesForRegistration($made['registration_id'])[0];
        $this->assertSame($link['message_id'], $saved['message_id']);
        $this->assertSame('Renamed', $saved['label']);
        $this->assertSame('yes', $saved['message_json']['custom_parameters']['keep']);
        $this->assertSame('lessons', $saved['message_json']['placements'][0]);
    }

    /**
     * @return array{registration_id:int, tool_deployment_id:int}
     */
    private function tool(): array
    {
        return ToolRegistrationService::createLti13ForCourse(
            $this->id['eecs280'],
            null,
            'Widget',
            array(
                'lti13_client_id' => 'client-1',
                'lti13_oidc_login_url' => 'https://tool.example/login',
                'lti13_jwks_url' => 'https://tool.example/jwks',
                'lti13_launch_url' => 'https://tool.example/launch',
                'lti13_redirect_uri' => 'https://tool.example/redirect',
            ),
            array(
                'client_name' => 'Widget',
                'initiate_login_uri' => 'https://tool.example/login',
                'jwks_uri' => 'https://tool.example/jwks',
                'redirect_uris' => array(
                    'https://tool.example/redirect',
                    'https://tool.example/other',
                ),
                'scope' => self::SCORE.' '.self::ROSTER,
                ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                    'target_link_uri' => 'https://tool.example/launch',
                    'claims' => array('iss', 'sub', 'name', 'email'),
                    'messages' => array(
                        array(
                            'type' => 'LtiResourceLinkRequest',
                            'label' => 'Link',
                            'target_link_uri' => 'https://tool.example/launch',
                            'placements' => array('lessons'),
                            'custom_parameters' => array('keep' => 'yes'),
                        ),
                        array(
                            'type' => 'LtiDeepLinkingRequest',
                            'label' => 'Pick',
                            'target_link_uri' => 'https://tool.example/pick',
                            'placements' => array('assignment_selection'),
                        ),
                    ),
                ),
            ),
            'deploy-1'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     * @return array<string, array<int, string>>
     */
    private function groupValues(array $groups): array
    {
        $out = array();
        foreach ( $groups as $group ) {
            $values = array();
            foreach ( $group['placements'] as $row ) {
                $values[] = $row['value'];
            }
            $out[$group['label']] = $values;
        }
        return $out;
    }

    /**
     * @param int $messageId
     * @param string $name
     * @return int
     */
    private function placementId(int $messageId, string $name): int
    {
        foreach ( ToolPlacementService::getPlacementsForMessage($messageId) as $row ) {
            if ( $row['placement'] === $name ) {
                return (int) $row['message_placement_id'];
            }
        }
        $this->fail('Placement was not stored.');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string $value
     * @return array<string, mixed>
     */
    private function rowNamed(array $rows, string $value): array
    {
        foreach ( $rows as $row ) {
            if ( $row['value'] === $value ) {
                return $row;
            }
        }
        $this->fail('Row was not on the edit page.');
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
