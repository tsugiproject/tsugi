<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * Parsed Dynamic Registration state, plus the raw protocol log.
 *
 * registration_json and lti_tool_message hold the current parsed document.
 * lti_tool_message.sequence is the messages[] array index, starting at 0.
 * A message placements array becomes one lti_tool_message_placement row per
 * name. The message row is not repeated for each placement. message_json
 * still keeps the original array.
 * The log stores the exact payload text before any parse, including text
 * that is not valid JSON. Deleting a registration removes its message rows
 * and leaves the log rows, with registration_id set to null. Replacing the
 * document deletes the message rows, which deletes their placements and any
 * deployment enablement of those placements.
 */
class ToolRegistrationDocument {

    const TOOL_CONFIGURATION = 'https://purl.imsglobal.org/spec/lti-tool-configuration';

    /** @var array<int, string> */
    private const CLAIM_ORDER = array('iss', 'sub', 'name', 'given_name', 'family_name', 'email');

    public const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    public const SCOPE_LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    public const SCOPE_LINEITEM_READONLY = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    public const SCOPE_RESULT = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
    public const SCOPE_ROSTER = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    /** @var array<int, string> */
    private const SCOPE_ORDER = array(
        self::SCOPE_SCORE,
        self::SCOPE_LINEITEM,
        self::SCOPE_LINEITEM_READONLY,
        self::SCOPE_RESULT,
        self::SCOPE_ROSTER,
    );

    /**
     * Write the raw payload, then parse it. A parse failure leaves the log
     * row in place and does not change the registration or its messages.
     *
     * @param int $registrationId
     * @param string $direction inbound or outbound
     * @param string $phase
     * @param string $payloadText exact body, not reformatted
     * @param string|null $contentType
     * @param int|null $httpStatus
     * @return void
     */
    public static function acceptPayload($registrationId, $direction, $phase, $payloadText, $contentType = null, $httpStatus = null) {
        $registrationId = self::requireRegistrationId($registrationId);
        if ( ! is_string($payloadText) ) {
            throw new \InvalidArgumentException('Registration payload text is required.');
        }
        self::appendLog($registrationId, $direction, $phase, $payloadText, $contentType, $httpStatus);
        $document = self::decodeObject($payloadText);
        self::storeDocument($registrationId, $document);
    }

    /**
     * The same document shape Dynamic Registration stores, built from LTI 1.1 checkboxes.
     *
     * iss and sub are always requested. Name and email claims are added when asked.
     * scope is the service URIs, space-separated, and is omitted when none are asked.
     *
     * @param string $launchUrl
     * @param array<int, array{type: string, placements: array<int, string>}> $messages
     * @param mixed $claims
     * @param mixed $scopes
     * @return array<string, mixed>
     */
    public static function lti11Document($launchUrl, array $messages, $claims, $scopes) {
        $launchUrl = (string) $launchUrl;
        $rows = array();
        foreach ( $messages as $message ) {
            $row = array(
                'type' => $message['type'],
                'target_link_uri' => $launchUrl,
            );
            if ( count($message['placements']) > 0 ) {
                $row['placements'] = array_values($message['placements']);
            }
            $rows[] = $row;
        }
        $document = array(
            self::TOOL_CONFIGURATION => array(
                'target_link_uri' => $launchUrl,
                'claims' => self::normalizeClaims($claims),
                'messages' => $rows,
            ),
        );
        $scope = self::normalizeScope($scopes);
        if ( $scope !== '' ) {
            $document['scope'] = $scope;
        }
        return $document;
    }

    /**
     * @param mixed $claims
     * @return array<int, string>
     */
    public static function normalizeClaims($claims) {
        $asked = self::uniqueNames($claims, 'Claim');
        $allowed = array_fill_keys(self::CLAIM_ORDER, true);
        foreach ( $asked as $claim ) {
            if ( ! isset($allowed[$claim]) ) {
                throw new \InvalidArgumentException('That claim is not a registration claim.');
            }
        }
        $wanted = array_fill_keys($asked, true);
        $wanted['iss'] = true;
        $wanted['sub'] = true;
        $out = array();
        foreach ( self::CLAIM_ORDER as $claim ) {
            if ( isset($wanted[$claim]) ) {
                $out[] = $claim;
            }
        }
        return $out;
    }

    /**
     * @param mixed $scopes
     * @return string
     */
    public static function normalizeScope($scopes) {
        $asked = self::uniqueNames($scopes, 'Scope');
        $allowed = array_fill_keys(self::SCOPE_ORDER, true);
        foreach ( $asked as $scope ) {
            if ( ! isset($allowed[$scope]) ) {
                throw new \InvalidArgumentException('That scope is not a registration scope.');
            }
        }
        $wanted = array_fill_keys($asked, true);
        $out = array();
        foreach ( self::SCOPE_ORDER as $scope ) {
            if ( isset($wanted[$scope]) ) {
                $out[] = $scope;
            }
        }
        return implode(' ', $out);
    }

    /**
     * Claims a deployment may allow. iss and sub are omitted because they are always sent.
     *
     * @param array<string, mixed> $document
     * @return array<int, string>
     */
    public static function requestedClaims(array $document) {
        $config = self::toolConfiguration($document);
        if ( $config === null || ! isset($config['claims']) || ! is_array($config['claims']) ) {
            return array();
        }
        $out = array();
        foreach ( $config['claims'] as $claim ) {
            if ( ! is_string($claim) ) {
                continue;
            }
            $claim = trim($claim);
            if ( $claim === '' || $claim === 'iss' || $claim === 'sub' ) {
                continue;
            }
            $out[] = $claim;
        }
        return $out;
    }

    /**
     * Scope URIs a deployment may allow.
     *
     * @param array<string, mixed> $document
     * @return array<int, string>
     */
    public static function requestedScopes(array $document) {
        if ( ! isset($document['scope']) || ! is_string($document['scope']) ) {
            return array();
        }
        $parts = preg_split('/\s+/', trim($document['scope']));
        if ( ! is_array($parts) ) {
            return array();
        }
        $out = array();
        foreach ( $parts as $scope ) {
            if ( $scope !== '' ) {
                $out[] = $scope;
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>|null
     */
    private static function toolConfiguration(array $document) {
        if ( ! isset($document[self::TOOL_CONFIGURATION]) || ! is_array($document[self::TOOL_CONFIGURATION]) ) {
            return null;
        }
        $config = $document[self::TOOL_CONFIGURATION];
        if ( array_is_list($config) ) {
            return null;
        }
        return $config;
    }

    /**
     * @param mixed $values
     * @param string $label
     * @return array<int, string>
     */
    private static function uniqueNames($values, $label) {
        if ( $values === null ) {
            $values = array();
        }
        if ( ! is_array($values) || ! array_is_list($values) ) {
            throw new \InvalidArgumentException($label.' requests must be a list.');
        }
        $out = array();
        $seen = array();
        foreach ( $values as $value ) {
            if ( ! is_string($value) ) {
                throw new \InvalidArgumentException('Each '.$label.' request must be a string.');
            }
            $value = trim($value);
            if ( $value === '' ) {
                throw new \InvalidArgumentException('Each '.$label.' request must be a string.');
            }
            if ( isset($seen[$value]) ) {
                throw new \InvalidArgumentException('That '.$label.' request is already selected.');
            }
            $seen[$value] = true;
            $out[] = $value;
        }
        return $out;
    }

    /**
     * Replace the parsed registration document and rebuild its message rows.
     *
     * Grants on deployments of this registration are reduced to claims and
     * scopes that are still in the new document.
     *
     * @param int $registrationId
     * @param array<string, mixed> $document parsed JSON object
     * @return void
     */
    public static function storeDocument($registrationId, array $document) {
        $registrationId = self::requireRegistrationId($registrationId);
        $messages = self::messageList($document);
        $encoded = self::encodeJson($document);
        $rows = array();
        foreach ( $messages as $sequence => $message ) {
            $rows[] = self::messageRow($sequence, $message);
        }

        $PDOX = self::db();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            self::writeDocument($registrationId, $encoded, $rows);
            ToolDeploymentGrant::retain(
                $registrationId,
                self::requestedClaims($document),
                self::requestedScopes($document)
            );
            if ( $owns ) {
                $PDOX->commit();
            }
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Messages in source-document order.
     *
     * @param int $registrationId
     * @return array<int, array<string, mixed>>
     */
    public static function messagesForRegistration($registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            return array();
        }
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT message_id, registration_id, sequence, message_type,
                    target_link_uri, label, icon_uri, message_json
             FROM {$p}lti_tool_message
             WHERE registration_id = :registration_id
             ORDER BY sequence ASC, message_id ASC",
            array(':registration_id' => $registrationId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'message_id' => (int) $row['message_id'],
                'registration_id' => (int) $row['registration_id'],
                'sequence' => (int) $row['sequence'],
                'message_type' => (string) $row['message_type'],
                'target_link_uri' => $row['target_link_uri'] === null ? null : (string) $row['target_link_uri'],
                'label' => $row['label'] === null ? null : (string) $row['label'],
                'icon_uri' => $row['icon_uri'] === null ? null : (string) $row['icon_uri'],
                'message_json' => self::decodeStoredJson($row['message_json']),
            );
        }
        return $out;
    }

    /**
     * The parsed registration document, or null when none has been stored.
     *
     * @param int $registrationId
     * @return array<string, mixed>|null
     */
    public static function registrationDocument($registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_json FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        if ( ! is_array($row) || $row['registration_json'] === null ) {
            return null;
        }
        return self::decodeStoredJson($row['registration_json']);
    }

    /**
     * Append one raw payload. registration_id may be null before a registration row exists.
     * The text is stored unchanged.
     *
     * @param int|null $registrationId
     * @param string $direction inbound or outbound
     * @param string $phase
     * @param string $payloadText
     * @param string|null $contentType
     * @param int|null $httpStatus
     * @return int log_id
     */
    public static function appendLog($registrationId, $direction, $phase, $payloadText, $contentType = null, $httpStatus = null) {
        $PDOX = self::db();
        $p = self::prefix();
        $registrationId = self::optionalRegistrationId($registrationId);
        $direction = self::direction($direction);
        $phase = self::phase($phase);
        if ( ! is_string($payloadText) ) {
            throw new \InvalidArgumentException('Registration payload text is required.');
        }
        $contentType = self::optionalLabel($contentType, 255, 'Content type');
        $httpStatus = self::optionalStatus($httpStatus);
        $sequence = self::nextLogSequence($registrationId);

        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_registration_log
                (registration_id, sequence, direction, phase, content_type, http_status, payload_text, created_at)
             VALUES
                (:registration_id, :sequence, :direction, :phase, :content_type, :http_status, :payload_text, NOW())",
            array(
                ':registration_id' => $registrationId,
                ':sequence' => $sequence,
                ':direction' => $direction,
                ':phase' => $phase,
                ':content_type' => $contentType,
                ':http_status' => $httpStatus,
                ':payload_text' => $payloadText,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not append registration log. '.$detail);
        }
        $id = (int) $PDOX->lastInsertId();
        if ( $id < 1 ) {
            throw new \RuntimeException('Could not append registration log.');
        }
        return $id;
    }

    /**
     * Log rows for one registration, in the order they were appended.
     * Null returns rows that were written before a registration existed,
     * or whose registration has since been deleted. Those rows share a null
     * registration_id, and each registration numbers sequence from zero, so
     * this combined result is ordered by log_id. Ordering it by sequence
     * would interleave unrelated exchanges.
     *
     * @param int|null $registrationId
     * @return array<int, array<string, mixed>>
     */
    public static function logsForRegistration($registrationId) {
        $p = self::prefix();
        if ( $registrationId === null ) {
            $sql = "SELECT log_id, registration_id, sequence, direction, phase, content_type, http_status, payload_text
                FROM {$p}lti_tool_registration_log
                WHERE registration_id IS NULL
                ORDER BY log_id ASC";
            $params = array();
        } else {
            $registrationId = (int) $registrationId;
            if ( $registrationId < 1 ) {
                return array();
            }
            $sql = "SELECT log_id, registration_id, sequence, direction, phase, content_type, http_status, payload_text
                FROM {$p}lti_tool_registration_log
                WHERE registration_id = :registration_id
                ORDER BY sequence ASC, log_id ASC";
            $params = array(':registration_id' => $registrationId);
        }
        $rows = self::db()->allRowsDie($sql, $params);
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'log_id' => (int) $row['log_id'],
                'registration_id' => $row['registration_id'] === null ? null : (int) $row['registration_id'],
                'sequence' => (int) $row['sequence'],
                'direction' => (string) $row['direction'],
                'phase' => (string) $row['phase'],
                'content_type' => $row['content_type'] === null ? null : (string) $row['content_type'],
                'http_status' => $row['http_status'] === null ? null : (int) $row['http_status'],
                'payload_text' => (string) $row['payload_text'],
            );
        }
        return $out;
    }

    /**
     * Point a log row that was written before the registration existed at that registration.
     *
     * @param int $logId
     * @param int $registrationId
     * @param int|null $httpStatus
     * @return void
     */
    public static function attachLog($logId, $registrationId, $httpStatus = null) {
        $registrationId = self::requireRegistrationId($registrationId);
        $logId = (int) $logId;
        if ( $logId < 1 ) {
            throw new \InvalidArgumentException('Registration log was not found.');
        }
        $httpStatus = self::optionalStatus($httpStatus);
        $PDOX = self::db();
        $p = self::prefix();
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$p}lti_tool_registration_log
             SET registration_id = :registration_id, http_status = COALESCE(:http_status, http_status)
             WHERE log_id = :log_id AND registration_id IS NULL",
            array(
                ':registration_id' => $registrationId,
                ':http_status' => $httpStatus,
                ':log_id' => $logId,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not attach registration log. '.$detail);
        }
        if ( $stmt->rowCount() < 1 ) {
            throw new \RuntimeException('Could not attach registration log.');
        }
    }

    /**
     * @param int $registrationId
     * @param string $encoded
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    private static function writeDocument($registrationId, $encoded, array $rows) {
        $PDOX = self::db();
        $p = self::prefix();
        $registration = self::findRegistration($registrationId);
        if ( $registration === null ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        $keyId = (int) $registration['key_id'];
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$p}lti_tool_registration
             SET registration_json = :registration_json, updated_at = NOW()
             WHERE registration_id = :registration_id",
            array(
                ':registration_json' => $encoded,
                ':registration_id' => $registrationId,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not store registration JSON. '.$detail);
        }
        $deleted = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_tool_message WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        if ( ! $deleted->success ) {
            $detail = isset($deleted->errorImplode) ? (string) $deleted->errorImplode : 'database error';
            throw new \RuntimeException('Could not replace registration messages. '.$detail);
        }
        foreach ( $rows as $row ) {
            $inserted = $PDOX->queryReturnError(
                "INSERT INTO {$p}lti_tool_message
                    (registration_id, key_id, sequence, message_type, target_link_uri, label, icon_uri, message_json, created_at)
                 VALUES
                    (:registration_id, :key_id, :sequence, :message_type, :target_link_uri, :label, :icon_uri, :message_json, NOW())",
                array(
                    ':registration_id' => $registrationId,
                    ':key_id' => $keyId,
                    ':sequence' => $row['sequence'],
                    ':message_type' => $row['message_type'],
                    ':target_link_uri' => $row['target_link_uri'],
                    ':label' => $row['label'],
                    ':icon_uri' => $row['icon_uri'],
                    ':message_json' => $row['message_json'],
                )
            );
            if ( ! $inserted->success ) {
                $detail = isset($inserted->errorImplode) ? (string) $inserted->errorImplode : 'database error';
                throw new \RuntimeException('Could not store registration message. '.$detail);
            }
            $messageId = (int) $PDOX->lastInsertId();
            foreach ( $row['placements'] as $placement ) {
                ToolPlacementService::addPlacementToMessage($messageId, $placement);
            }
        }
    }

    /**
     * Messages from a spec registration live inside the LTI tool configuration
     * object. A document that has that object uses its messages array, including
     * when the array is absent or empty. A document without that object may
     * carry messages at the root. registration_json still stores the whole
     * document either way.
     *
     * @param array<string, mixed> $document
     * @return array<int, array<string, mixed>>
     */
    private static function messageList(array $document) {
        $messages = self::messagesValue($document);
        if ( $messages === null ) {
            return array();
        }
        if ( ! is_array($messages) || ! array_is_list($messages) ) {
            throw new \InvalidArgumentException('Registration messages must be a JSON array.');
        }
        $out = array();
        foreach ( $messages as $message ) {
            if ( ! is_array($message) || array_is_list($message) ) {
                throw new \InvalidArgumentException('Each registration message must be a JSON object.');
            }
            $out[] = $message;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $document
     * @return mixed null when this document declares no messages
     */
    private static function messagesValue(array $document) {
        if ( array_key_exists(self::TOOL_CONFIGURATION, $document) && $document[self::TOOL_CONFIGURATION] !== null ) {
            $config = $document[self::TOOL_CONFIGURATION];
            if ( ! is_array($config) || array_is_list($config) ) {
                throw new \InvalidArgumentException('LTI tool configuration must be a JSON object.');
            }
            if ( ! array_key_exists('messages', $config) || $config['messages'] === null ) {
                return null;
            }
            return $config['messages'];
        }
        if ( ! array_key_exists('messages', $document) || $document['messages'] === null ) {
            return null;
        }
        return $document['messages'];
    }

    /**
     * @param int $sequence
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    private static function messageRow($sequence, array $message) {
        if ( ! array_key_exists('type', $message) || ! is_string($message['type']) || $message['type'] === '' ) {
            throw new \InvalidArgumentException('Each message needs a type.');
        }
        if ( strlen($message['type']) > 255 ) {
            throw new \InvalidArgumentException('Message type is too long.');
        }
        return array(
            'sequence' => (int) $sequence,
            'message_type' => $message['type'],
            'target_link_uri' => self::scalarString($message, 'target_link_uri'),
            'label' => self::scalarString($message, 'label'),
            'icon_uri' => self::scalarString($message, 'icon_uri'),
            'message_json' => self::encodeJson($message),
            'placements' => self::placementNames($message, $message['type']),
        );
    }

    /**
     * Placement names from one message object. Absent or null means none.
     * The names are checked here so a bad document fails before any row is written.
     *
     * @param array<string, mixed> $message
     * @param string $messageType
     * @return array<int, string>
     */
    private static function placementNames(array $message, $messageType) {
        if ( ! array_key_exists('placements', $message) || $message['placements'] === null ) {
            return array();
        }
        $placements = $message['placements'];
        if ( ! is_array($placements) || ! array_is_list($placements) ) {
            throw new \InvalidArgumentException('Message placements must be a JSON array.');
        }
        $out = array();
        foreach ( $placements as $placement ) {
            if ( ! is_string($placement) ) {
                throw new \InvalidArgumentException('Each message placement must be a string.');
            }
            $placement = ToolMessagePlacement::assertCompatible($messageType, $placement);
            if ( isset($out[$placement]) ) {
                throw new \InvalidArgumentException('That placement is already on this message.');
            }
            $out[$placement] = $placement;
        }
        return array_values($out);
    }

    /**
     * @param array<string, mixed> $message
     * @param string $key
     * @return string|null
     */
    private static function scalarString(array $message, $key) {
        if ( ! array_key_exists($key, $message) || $message[$key] === null ) {
            return null;
        }
        if ( ! is_string($message[$key]) ) {
            return null;
        }
        return $message[$key];
    }

    /**
     * Decode a JSON object into an associative array.
     *
     * The true argument stays. false would return stdClass, and the is_array
     * checks in this service would reject a valid document. An empty object
     * and an empty array are the same PHP array, so encode writes []. In
     * this spec that is an empty custom_parameters. A lookup by name or by
     * position finds nothing either way. A non-empty object or array keeps
     * the type the spec names. The raw log still has the original text.
     * Leave this flag alone unless outbound JSON is rebuilt from
     * registration_json or message_json.
     *
     * @param string $payloadText
     * @return array<string, mixed>
     */
    private static function decodeObject($payloadText) {
        $decoded = json_decode($payloadText, true);
        if ( ! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE || array_is_list($decoded) ) {
            throw new \InvalidArgumentException('Registration payload is not valid JSON.');
        }
        return $decoded;
    }

    /**
     * Same array decode as decodeObject().
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function decodeStoredJson($value) {
        if ( is_array($value) ) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        if ( ! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE ) {
            throw new \RuntimeException('Stored registration JSON could not be read.');
        }
        return $decoded;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function encodeJson($value) {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ( ! is_string($encoded) ) {
            throw new \InvalidArgumentException('Registration JSON could not be encoded.');
        }
        return $encoded;
    }

    /**
     * @param int|null $registrationId
     * @return int
     */
    private static function nextLogSequence($registrationId) {
        $p = self::prefix();
        if ( $registrationId === null ) {
            $sql = "SELECT COALESCE(MAX(sequence), -1) AS max_sequence
                FROM {$p}lti_tool_registration_log
                WHERE registration_id IS NULL";
            $params = array();
        } else {
            $sql = "SELECT COALESCE(MAX(sequence), -1) AS max_sequence
                FROM {$p}lti_tool_registration_log
                WHERE registration_id = :registration_id";
            $params = array(':registration_id' => $registrationId);
        }
        $row = self::db()->rowDie($sql, $params);
        $max = is_array($row) ? (int) $row['max_sequence'] : -1;
        return $max + 1;
    }

    /**
     * @param mixed $direction
     * @return string
     */
    private static function direction($direction) {
        $direction = (string) $direction;
        if ( $direction !== 'inbound' && $direction !== 'outbound' ) {
            throw new \InvalidArgumentException('Registration log direction must be inbound or outbound.');
        }
        return $direction;
    }

    /**
     * @param mixed $phase
     * @return string
     */
    private static function phase($phase) {
        $phase = trim((string) $phase);
        if ( $phase === '' ) {
            throw new \InvalidArgumentException('Registration log phase is required.');
        }
        if ( strlen($phase) > 64 ) {
            throw new \InvalidArgumentException('Registration log phase is too long.');
        }
        return $phase;
    }

    /**
     * @param mixed $value
     * @param int $max
     * @param string $label
     * @return string|null
     */
    private static function optionalLabel($value, $max, $label) {
        if ( $value === null ) {
            return null;
        }
        $value = trim((string) $value);
        if ( $value === '' ) {
            return null;
        }
        if ( strlen($value) > $max ) {
            throw new \InvalidArgumentException($label.' is too long.');
        }
        return $value;
    }

    /**
     * @param mixed $status
     * @return int|null
     */
    private static function optionalStatus($status) {
        if ( $status === null || $status === '' ) {
            return null;
        }
        if ( is_bool($status) || ! is_numeric($status) ) {
            throw new \InvalidArgumentException('Registration log HTTP status must be an integer.');
        }
        $status = (int) $status;
        if ( $status < 100 || $status > 599 ) {
            throw new \InvalidArgumentException('Registration log HTTP status must be an integer.');
        }
        return $status;
    }

    /**
     * @param mixed $registrationId
     * @return int
     */
    private static function requireRegistrationId($registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 || self::findRegistration($registrationId) === null ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        return $registrationId;
    }

    /**
     * @param mixed $registrationId
     * @return int|null
     */
    private static function optionalRegistrationId($registrationId) {
        if ( $registrationId === null ) {
            return null;
        }
        return self::requireRegistrationId($registrationId);
    }

    /**
     * @param int $registrationId
     * @return array<string, mixed>|null
     */
    private static function findRegistration($registrationId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_id, key_id FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $registrationId)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        global $CFG, $PDOX;
        LTIX::getConnection();
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return (string) $CFG->dbprefix;
    }
}
