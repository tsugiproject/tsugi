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
                    (registration_id, key_id, sequence, message_type, label, message_json, created_at)
                 VALUES
                    (:registration_id, :key_id, :sequence, :message_type, :label, :message_json, NOW())",
                array(
                    ':registration_id' => $registrationId,
                    ':key_id' => $this->id['keyA'],
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
                (registration_id, key_id, sequence, message_type, message_json, created_at)
             VALUES
                (:registration_id, :key_id, 0, 'LtiResourceLinkRequest', '{\"type\":\"LtiResourceLinkRequest\"}', NOW())",
            array(
                ':registration_id' => $registrationId,
                ':key_id' => $this->id['keyA'],
            ),
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

    public function testOrphanedLogsStayInAppendOrder(): void
    {
        global $PDOX;
        $p = $this->p();
        $first = $this->registration();
        $second = $this->registration();
        $a0 = ToolRegistrationDocument::appendLog($first, 'inbound', 'registration', 'A0');
        $a1 = ToolRegistrationDocument::appendLog($first, 'inbound', 'registration', 'A1');
        $b0 = ToolRegistrationDocument::appendLog($second, 'inbound', 'registration', 'B0');

        foreach ( array($first, $second) as $registrationId ) {
            $stmt = $PDOX->queryReturnError(
                "DELETE FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
                array(':registration_id' => $registrationId)
            );
            $this->assertTrue((bool) $stmt->success, (string) $stmt->errorImplode);
        }

        $wanted = array($a0 => 'A0', $a1 => 'A1', $b0 => 'B0');
        $seen = array();
        foreach ( ToolRegistrationDocument::logsForRegistration(null) as $log ) {
            if ( isset($wanted[$log['log_id']]) ) {
                $seen[] = $wanted[$log['log_id']];
                $this->assertNull($log['registration_id']);
            }
        }
        $this->assertSame(array('A0', 'A1', 'B0'), $seen);
    }

    public function testSpecRegistrationReadsNestedMessages(): void
    {
        $registrationId = $this->registration();
        ToolRegistrationDocument::storeDocument($registrationId, array(
            'messages' => array(
                array('type' => 'LtiResourceLinkRequest', 'label' => 'Old'),
            ),
        ));

        $raw = json_encode(array(
            'client_name' => 'Widget',
            'https://purl.imsglobal.org/spec/lti-tool-configuration' => array(
                'domain' => 'tool.example',
                'claims' => array('iss', 'sub'),
                'messages' => array(
                    array(
                        'type' => 'LtiResourceLinkRequest',
                        'label' => 'Library',
                        'target_link_uri' => 'https://tool.example/link',
                    ),
                    array(
                        'type' => 'LtiDeepLinkingRequest',
                        'label' => 'Deep',
                    ),
                ),
            ),
        ), JSON_UNESCAPED_SLASHES);
        ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration', $raw);

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array(0, 1), array_column($messages, 'sequence'));
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'),
            array_column($messages, 'message_type')
        );
        $this->assertSame(array('Library', 'Deep'), array_column($messages, 'label'));
        $this->assertSame('https://tool.example/link', $messages[0]['target_link_uri']);

        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $config = $stored['https://purl.imsglobal.org/spec/lti-tool-configuration'];
        $this->assertSame('tool.example', $config['domain']);
        $this->assertSame(array('iss', 'sub'), $config['claims']);
        $this->assertSame('Library', $config['messages'][0]['label']);
    }

    public function testSpecOpenIdConfigurationExample(): void
    {
        $registrationId = $this->registration();
        $raw = $this->spec('2.1.3-openid-configuration.json');
        ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'openid_configuration', $raw);

        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertSame('https://server.example.com', $stored['issuer']);
        $platform = $stored['https://purl.imsglobal.org/spec/lti-platform-configuration'];
        $this->assertSame('ExampleLMS', $platform['product_family_code']);
        $this->assertSame(
            array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'),
            array_column($platform['messages_supported'], 'type')
        );
        $this->assertSame(array(), ToolRegistrationDocument::messagesForRegistration($registrationId));
        $this->assertSame($raw, ToolRegistrationDocument::logsForRegistration($registrationId)[0]['payload_text']);
    }

    public function testSpecToolConfigurationFromThePlatform(): void
    {
        $registrationId = $this->registration();
        $raw = $this->spec('2.3-tool-configuration-from-platform.json');
        ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration_response', $raw);

        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertSame('Virtual Garden', $stored['client_name']);
        $this->assertSame(array('implict', 'client_credentials'), $stored['grant_types']);
        $config = $stored['https://purl.imsglobal.org/spec/lti-tool-configuration'];
        $this->assertSame('client.example.org', $config['domain']);
        $this->assertSame('$Context.id.history', $config['custom_parameters']['context_history']);
        $this->assertSame(
            array('iss', 'sub', 'name', 'given_name', 'family_name'),
            $config['claims']
        );

        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertCount(1, $messages);
        $this->assertSame(0, $messages[0]['sequence']);
        $this->assertSame('LtiDeepLinkingRequest', $messages[0]['message_type']);
        $this->assertSame('Add a virtual garden', $messages[0]['label']);
        $this->assertSame('https://client.example.org/lti/dl', $messages[0]['target_link_uri']);
        $this->assertSame($raw, ToolRegistrationDocument::logsForRegistration($registrationId)[0]['payload_text']);
    }

    public function testSpecSuccessfulRegistrationResponse(): void
    {
        $registrationId = $this->registration();
        $raw = $this->spec('3.6.1-successful-registration.json');
        ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration_response', $raw);

        $stored = ToolRegistrationDocument::registrationDocument($registrationId);
        $this->assertSame('709sdfnjkds12', $stored['client_id']);
        $this->assertSame('iDPzMyKHMX_4CkTpwLDCK', $stored['registration_access_token']);
        $config = $stored['https://purl.imsglobal.org/spec/lti-tool-configuration'];
        $this->assertSame(array('iss', 'sub'), $config['claims']);
        $messages = ToolRegistrationDocument::messagesForRegistration($registrationId);
        $this->assertSame(array('LtiDeepLinkingRequest'), array_column($messages, 'message_type'));
        $this->assertSame('Add a virtual garden', $messages[0]['label']);
    }

    /**
     * These two samples are copied unchanged from the published spec, and both
     * are illegal JSON. Section 2.2.5 omits the comma before supported_types.
     * Section 3.5.2 has a trailing comma after label#ja. Neither sample uses
     * an ellipsis or a placeholder. The text is complete and IMS published it
     * this way. The log keeps that text. No message rows are stored.
     */
    public function testSpecExamplesThatAreNotValidJsonStayInTheLog(): void
    {
        foreach ( array('2.2.5-tool-configuration.json', '3.5.2-client-registration-request.json') as $name ) {
            $registrationId = $this->registration();
            $raw = $this->spec($name);
            try {
                ToolRegistrationDocument::acceptPayload($registrationId, 'inbound', 'registration', $raw);
                $this->fail($name.' is published as invalid JSON and must be rejected.');
            } catch ( \InvalidArgumentException $ex ) {
                $this->assertStringContainsString('not valid JSON', $ex->getMessage());
            }
            $this->assertNull(ToolRegistrationDocument::registrationDocument($registrationId));
            $this->assertSame(array(), ToolRegistrationDocument::messagesForRegistration($registrationId));
            $this->assertSame($raw, ToolRegistrationDocument::logsForRegistration($registrationId)[0]['payload_text']);
        }
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

    /**
     * Exact JSON from the published Dynamic Registration 1.0 page.
     * Files in spec/ are the example text, not a cleaned-up copy.
     *
     * 2.1.3-openid-configuration.json
     *     https://www.imsglobal.org/spec/lti-dr/v1p0#non-normative-example
     * 2.2.5-tool-configuration.json
     *     https://www.imsglobal.org/spec/lti-dr/v1p0#non-normative-example-0
     * 2.3-tool-configuration-from-platform.json
     *     https://www.imsglobal.org/spec/lti-dr/v1p0#tool-configuration-from-the-platform
     * 3.5.2-client-registration-request.json
     *     https://www.imsglobal.org/spec/lti-dr/v1p0#client-registration-request
     * 3.6.1-successful-registration.json
     *     https://www.imsglobal.org/spec/lti-dr/v1p0#successful-registration
     *
     * 2.2.5 and 3.5.2 do not parse. See testSpecExamplesThatAreNotValidJsonStayInTheLog().
     * Section 3.7 is a JavaScript postMessage, so it is not in spec/.
     */
    private function spec(string $name): string
    {
        $path = __DIR__.'/spec/'.$name;
        $raw = file_get_contents($path);
        $this->assertIsString($raw, $name);
        return $raw;
    }
}
