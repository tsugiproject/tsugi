<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * Parsed Dynamic Registration state, plus the raw protocol log.
 *
 * registration_json and lti_tool_message hold the current parsed document.
 * lti_tool_message.sequence is the messages[] array index, starting at 0.
 * The log stores the exact payload text before any parse, including text
 * that is not valid JSON. Deleting a registration removes its message rows
 * and leaves the log rows, with registration_id set to null.
 */
class ToolRegistrationDocument {

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
     * Replace the parsed registration document and rebuild its message rows.
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
     * @param int $registrationId
     * @param string $encoded
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    private static function writeDocument($registrationId, $encoded, array $rows) {
        $PDOX = self::db();
        $p = self::prefix();
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
                    (registration_id, sequence, message_type, target_link_uri, label, icon_uri, message_json, created_at)
                 VALUES
                    (:registration_id, :sequence, :message_type, :target_link_uri, :label, :icon_uri, :message_json, NOW())",
                array(
                    ':registration_id' => $registrationId,
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
        }
    }

    /**
     * @param array<string, mixed> $document
     * @return array<int, array<string, mixed>>
     */
    private static function messageList(array $document) {
        if ( ! array_key_exists('messages', $document) || $document['messages'] === null ) {
            return array();
        }
        $messages = $document['messages'];
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
        );
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
            "SELECT registration_id FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
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
