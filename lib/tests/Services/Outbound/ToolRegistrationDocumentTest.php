<?php

use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Services\Outbound\ToolRegistrationService;

require_once __DIR__.'/../Org/PlatformSchemaCase.php';

class ToolRegistrationDocumentTest extends PlatformSchemaCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $PDOX;
        $table = $this->p().'lti_tool_registration';
        if ( ! $PDOX->columnExists('registration_json', $table) || $PDOX->metadata($this->p().'lti_tool_message') === false ) {
            $this->markTestSkipped('Dynamic Registration tables are missing. Run php admin/upgrade.php.');
        }
    }

    public function testMessagesKeepSourceOrder(): void
    {
        $registrationId = $this->registration();
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'client_name' => 'Widget',
            'messages' => array(
                array('type' => 'LtiResourceLinkRequest', 'label' => 'First'),
                array('type' => 'LtiDeepLinkingRequest', 'label' => 'Second'),
                array('type' => 'https://example.com/vendor-message', 'label' => 'Third'),
            ),
        ));

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array(0, 1, 2), array_column($messages, 'sequence'));
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest', 'https://example.com/vendor-message'),
            array_column($messages, 'message_type')
        );
        $this->assertSame(array('First', 'Second', 'Third'), array_column($messages, 'label'));
    }

    public function testRetrievalUsesSequenceRatherThanInsertOrder(): void
    {
        global $PDOX;
        $p = $this->p();
        $registrationId = $this->registration();
        foreach ( array(2 => 'Third', 0 => 'First', 1 => 'Second') as $sequence => $label ) {
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$p}lti_tool_message
                    (registration_id, sequence, message_type, label, message_json, created_at)
                 VALUES
                    (:registration_id, :sequence, :message_type, :label, :message_json, NOW())",
                array(
                    ':registration_id' => $registrationId,
                    ':sequence' => $sequence,
                    ':message_type' => 'LtiResourceLinkRequest',
                    ':label' => $label,
                    ':message_json' => '{"type":"LtiResourceLinkRequest","label":"'.$label.'"}',
                )
            );
            $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        }

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array('First', 'Second', 'Third'), array_column($messages, 'label'));
        $this->assertGreaterThan($messages[2]['message_id'], $messages[0]['message_id']);

        $this->assertSqlRejected(
            "INSERT INTO {$p}lti_tool_message
                (registration_id, sequence, message_type, message_json, created_at)
             VALUES
                (:registration_id, 0, 'LtiResourceLinkRequest', '{\"type\":\"LtiResourceLinkRequest\"}', NOW())",
            array(':registration_id' => $registrationId),
            'lti_tool_message_const_1'
        );
    }

    public function testJsonKeepsFieldsThatAreNotColumns(): void
    {
        $registrationId = $this->registration();
        $document = array(
            'client_name' => 'Widget',
            'https://example.com/vendor' => array('keep' => true),
            'messages' => array(
                array(
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => 'https://tool.example/deep',
                    'label' => 'Library',
                    'icon_uri' => 'https://tool.example/icon.png',
                    'placements' => array('lessons', 'site_nav'),
                    'roles' => array('Instructor'),
                    'custom_parameters' => array('chapter' => '1'),
                    'supported_types' => array('ltiResourceLink'),
                    'supported_media_types' => array('text/html'),
                    'https://example.com/ext' => 'leave-this',
                ),
            ),
        );
        ToolRegistrationDocument::storeDocument($registrationId, $document);

        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertSame(array('keep' => true), $stored['https://example.com/vendor']);

        $message = ToolRegistrationDocument::messagesForRegistration($registrationId)[0];
        $this->assertSame('https://tool.example/deep', $message['target_link_uri']);
        $this->assertSame('Library', $message['label']);
        $this->assertSame('https://tool.example/icon.png', $message['icon_uri']);
        $this->assertSame(array('lessons', 'site_nav'), $message['message_json']['placements']);
        $this->assertSame(array('Instructor'), $message['message_json']['roles']);
        $this->assertSame(array('chapter' => '1'), $message['message_json']['custom_parameters']);
        $this->assertSame(array('ltiResourceLink'), $message['message_json']['supported_types']);
        $this->assertSame(array('text/html'), $message['message_json']['supported_media_types']);
        $this->assertSame('leave-this', $message['message_json']['https://example.com/ext']);
    }

    public function testRawLogKeepsMalformedJsonAndSkipsSemanticTables(): void
    {
        $registrationId = $this->registration();
        $raw = '{"client_name":"Widget","messages":[}';
        try {
            ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration', $raw, 'application/json', 200);
            $this->fail('Expected malformed JSON to be rejected.');
        } catch ( \InvalidArgumentException $ex ) {
            $this->assertStringContainsString('not valid JSON', $ex->getMessage());
        }

        $logs = ToolRegistrationDocument::logsForRegistration($registrationId);
        $this->assertCount(1, $logs);
        $this->assertSame($raw, $logs[0]['payload_text']);
        $this->assertSame('inbound', $logs[0]['direction']);
        $this->assertSame('registration', $logs[0]['phase']);
        $this->assertNull(ToolRegistrationDocument::registrationDocument($registrationId));
        $this->assertSame(array(), ToolRegistrationDocument::messagesForRegistration($registrationId));
    }

    public function testRawLogIsNotReformatted(): void
    {
        $registrationId = $this->registration();
        $raw = "{\n  \"client_name\" : \"Widget\" ,\n  \"messages\" : [ { \"type\" : \"LtiResourceLinkRequest\" } ]\n}\n";
        ToolRegistrationDocument::acceptPayload($registrationId, 'outbound', 'registration_response', $raw);

        $logs = ToolRegistrationDocument::logsForRegistration($registrationId);
        $this->assertSame($raw, $logs[0]['payload_text']);
        $this->assertSame('outbound', $logs[0]['direction']);
        $this->assertSame(0, $logs[0]['sequence']);

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array('LtiResourceLinkRequest'), array_column($messages, 'message_type'));
        $this->assertSame(0, $messages[0]['sequence']);
    }

    public function testReplacingADocumentRebuildsMessageRows(): void
    {
        $registrationId = $this->registration();
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array('type' => 'LtiResourceLinkRequest', 'label' => 'Old'),
                array('type' => 'LtiDeepLinkingRequest', 'label' => 'Gone'),
            ),
        ));
        $oldId = ToolRegistrationDocument::messagesForRegistration($registrationId)[0]['message_id'];

        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array('type' => 'https://example.com/other', 'label' => 'New'),
            ),
        ));

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertCount(1, $messages);
        $this->assertSame(0, $messages[0]['sequence']);
        $this->assertSame('https://example.com/other', $messages[0]['message_type']);
        $this->assertSame('New', $messages[0]['label']);
        $this->assertNotSame($oldId, $messages[0]['message_id']);
    }

    public function testDeletingARegistrationDropsMessagesAndKeepsTheLog(): void
    {
        global $PDOX;
        $p = $this->p();
        $registrationId = $this->registration();
        $raw = '{"client_name":"Widget"}';
        ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration', $raw);
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'client_name' => 'Widget',
            'messages' => array(
                array('type' => 'LtiResourceLinkRequest'),
            ),
        ));
        $logId = ToolRegistrationDocument::logsForRegistration($registrationId)[0]['log_id'];

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);

        $this->assertSame(array(), ToolRegistrationDocument::messagesForRegistration($registrationId));
        $log = $PDOX->rowDie(
            "SELECT registration_id, payload_text FROM {$p}lti_tool_registration_log WHERE log_id = :log_id",
            array(':log_id' => $logId)
        );
        $this->assertNull($log['registration_id']);
        $this->assertSame($raw, $log['payload_text']);
    }

    public function testLogCanBeWrittenBeforeARegistrationExists(): void
    {
        $raw = 'not-json-yet';
        $logId = ToolRegistrationDocument::appendLog(null, 'inbound', 'openid_configuration', $raw);
        $logs = ToolRegistrationDocument::logsForRegistration(null);
        $found = null;
        foreach ( $logs as $log ) {
            if ( $log['log_id'] === $logId ) {
                $found = $log;
            }
        }
        $this->assertNotNull($found);
        $this->assertNull($found['registration_id']);
        $this->assertSame($raw, $found['payload_text']);
        $this->assertGreaterThanOrEqual(0, $found['sequence']);
    }

    private function registration(): int
    {
        return ToolRegistrationService::createRegistration($this->id['keyA'], 'Widget');
    }
}
