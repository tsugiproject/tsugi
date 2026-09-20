<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Core\LTIX;

/**
 * Persist cc_import / cc_import_log / cc_object rows.
 *
 * Session is the matcher; this writes what a finished (or in-flight) session
 * recorded. import_id on cc_object is last-run, not ownership.
 */
class Store {

    /**
     * @param int $context_id
     * @param int $user_id
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public static function startImport($context_id, $user_id, array $meta = array()) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $user_id = (int) $user_id;
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}cc_import
                (context_id, user_id, filename, title, manifest_identifier, cc_version,
                 zip_sha256, status, json, started_at, created_at, updated_at)
             VALUES
                (:cid, :uid, :filename, :title, :mid, :ver,
                 :zip, :status, :json, NOW(), NOW(), NOW())",
            array(
                ':cid' => (int) $context_id,
                ':uid' => $user_id > 0 ? $user_id : null,
                ':filename' => self::nullableString($meta['filename'] ?? null),
                ':title' => self::nullableString($meta['title'] ?? null),
                ':mid' => self::nullableString($meta['manifest_identifier'] ?? null),
                ':ver' => self::nullableString($meta['cc_version'] ?? null),
                ':zip' => self::nullableString($meta['zip_sha256'] ?? null),
                ':status' => Matcher::STATUS_RUNNING,
                ':json' => self::encodeJson($meta['json'] ?? null),
            )
        );
        $id = (int) $PDOX->lastInsertId();
        $row = self::loadImport($id);
        if ( ! is_array($row) ) {
            throw new \RuntimeException('cc_import insert did not return a row');
        }
        return $row;
    }

    /**
     * @param int $import_id
     * @return array<string, mixed>|null
     */
    public static function loadImport($import_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT import_id, context_id, user_id, filename, title, manifest_identifier,
                    cc_version, zip_sha256, status, created_count, duplicate_count,
                    copy_count, error_count, json, started_at, finished_at
             FROM {$CFG->dbprefix}cc_import
             WHERE import_id = :id",
            array(':id' => (int) $import_id)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $context_id
     * @return list<array<string, mixed>>
     */
    public static function loadObjects($context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT object_id, context_id, import_id, resource_identifier, item_identifier,
                    resource_type, identifiers, local_kind, local_id, local_key,
                    content_hash, diverged, json, created_at, updated_at
             FROM {$CFG->dbprefix}cc_object
             WHERE context_id = :cid
             ORDER BY object_id ASC",
            array(':cid' => (int) $context_id)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = self::decodeObjectRow($row);
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function insertObject(array $row) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $ids = $row['identifiers'] ?? null;
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}cc_object
                (context_id, import_id, resource_identifier, item_identifier, resource_type,
                 identifiers, local_kind, local_id, local_key, content_hash, diverged, json,
                 created_at, updated_at)
             VALUES
                (:cid, :iid, :rid, :item, :rtype,
                 :idents, :kind, :lid, :lkey, :hash, :div, :json,
                 NOW(), NOW())",
            array(
                ':cid' => (int) $row['context_id'],
                ':iid' => isset($row['import_id']) && $row['import_id'] !== null ? (int) $row['import_id'] : null,
                ':rid' => (string) $row['resource_identifier'],
                ':item' => self::nullableString($row['item_identifier'] ?? null),
                ':rtype' => (string) $row['resource_type'],
                ':idents' => self::encodeJson($ids),
                ':kind' => self::nullableString($row['local_kind'] ?? null),
                ':lid' => isset($row['local_id']) && $row['local_id'] !== null ? (int) $row['local_id'] : null,
                ':lkey' => self::nullableString($row['local_key'] ?? null),
                ':hash' => (string) $row['content_hash'],
                ':div' => ! empty($row['diverged']) ? 1 : 0,
                ':json' => self::encodeJson($row['json'] ?? null),
            )
        );
        $row['object_id'] = (int) $PDOX->lastInsertId();
        return $row;
    }

    /**
     * @param int $import_id
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function insertLog($import_id, array $row) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}cc_import_log
                (import_id, resource_identifier, item_identifier, resource_type, title,
                 action, local_kind, local_id, object_id, message, created_at)
             VALUES
                (:iid, :rid, :item, :rtype, :title,
                 :action, :kind, :lid, :oid, :msg, NOW())",
            array(
                ':iid' => (int) $import_id,
                ':rid' => self::nullableString($row['resource_identifier'] ?? null),
                ':item' => self::nullableString($row['item_identifier'] ?? null),
                ':rtype' => self::nullableString($row['resource_type'] ?? null),
                ':title' => self::nullableString($row['title'] ?? null),
                ':action' => (string) $row['action'],
                ':kind' => self::nullableString($row['local_kind'] ?? null),
                ':lid' => isset($row['local_id']) && $row['local_id'] !== null ? (int) $row['local_id'] : null,
                ':oid' => isset($row['object_id']) && $row['object_id'] !== null ? (int) $row['object_id'] : null,
                ':msg' => self::nullableString($row['message'] ?? null),
            )
        );
        $row['log_id'] = (int) $PDOX->lastInsertId();
        $row['import_id'] = (int) $import_id;
        return $row;
    }

    /**
     * @param int $object_id
     * @return bool
     */
    public static function markDiverged($object_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}cc_object SET diverged = 1, updated_at = NOW()
             WHERE object_id = :id",
            array(':id' => (int) $object_id)
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * @param int $import_id
     * @param string $status
     * @param array<string, int> $counts
     * @return void
     */
    public static function finishImport($import_id, $status, array $counts = array()) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}cc_import
             SET status = :status,
                 created_count = :created,
                 duplicate_count = :dup,
                 copy_count = :copy,
                 error_count = :err,
                 finished_at = NOW(),
                 updated_at = NOW()
             WHERE import_id = :id",
            array(
                ':id' => (int) $import_id,
                ':status' => (string) $status,
                ':created' => (int) ($counts['created_count'] ?? 0),
                ':dup' => (int) ($counts['duplicate_count'] ?? 0),
                ':copy' => (int) ($counts['copy_count'] ?? 0),
                ':err' => (int) ($counts['error_count'] ?? 0),
            )
        );
    }

    public static function persistSession(Session $session, array $knownObjectIds = array()) {
        if ( $session->import === null ) {
            throw new \LogicException('Session has no import to persist');
        }
        $meta = $session->import;
        $row = self::startImport(
            (int) $session->context_id,
            (int) $session->user_id,
            array(
                'filename' => $meta['filename'] ?? '',
                'title' => $meta['title'] ?? '',
                'manifest_identifier' => $meta['manifest_identifier'] ?? '',
                'cc_version' => $meta['cc_version'] ?? '',
                'zip_sha256' => $meta['zip_sha256'] ?? '',
            )
        );
        $dbImportId = (int) $row['import_id'];
        $known = array();
        foreach ( $knownObjectIds as $id ) {
            $known[(int) $id] = true;
        }
        $map = array();
        foreach ( $session->objects as $obj ) {
            $old = (int) ($obj['object_id'] ?? 0);
            if ( $old > 0 && isset($known[$old]) ) {
                $map[$old] = $old;
                if ( (int) ($obj['import_id'] ?? 0) === (int) ($meta['import_id'] ?? 0) ) {
                    self::touchObjectImport($old, $dbImportId);
                }
                continue;
            }
            $obj['context_id'] = $session->context_id;
            $obj['import_id'] = $dbImportId;
            $inserted = self::insertObject($obj);
            $map[$old] = (int) $inserted['object_id'];
        }
        foreach ( $session->logs as $log ) {
            $oid = isset($log['object_id']) ? (int) $log['object_id'] : 0;
            if ( $oid > 0 && isset($map[$oid]) ) {
                $log['object_id'] = $map[$oid];
            }
            self::insertLog($dbImportId, $log);
        }
        self::finishImport($dbImportId, (string) ($meta['status'] ?? Matcher::STATUS_OK), array(
            'created_count' => (int) ($meta['created_count'] ?? 0),
            'duplicate_count' => (int) ($meta['duplicate_count'] ?? 0),
            'copy_count' => (int) ($meta['copy_count'] ?? 0),
            'error_count' => (int) ($meta['error_count'] ?? 0),
        ));
        $loaded = self::loadImport($dbImportId);
        return is_array($loaded) ? $loaded : $row;
    }

    /**
     * Touch last-import on a duplicate match (object already exists).
     *
     * @param int $object_id
     * @param int $import_id
     * @return void
     */
    public static function touchObjectImport($object_id, $import_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}cc_object SET import_id = :iid, updated_at = NOW()
             WHERE object_id = :id",
            array(
                ':id' => (int) $object_id,
                ':iid' => (int) $import_id,
            )
        );
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function nullableString($value) {
        if ( $value === null ) {
            return null;
        }
        if ( ! is_string($value) ) {
            return null;
        }
        return $value === '' ? null : $value;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function encodeJson($value) {
        if ( $value === null || $value === '' ) {
            return null;
        }
        if ( is_string($value) ) {
            return $value;
        }
        if ( is_array($value) && count($value) < 1 ) {
            return null;
        }
        $json = json_encode($value);
        return $json === false ? null : $json;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function decodeObjectRow(array $row) {
        $raw = $row['identifiers'] ?? null;
        if ( is_string($raw) && $raw !== '' ) {
            $decoded = json_decode($raw, true);
            $row['identifiers'] = is_array($decoded) ? $decoded : array();
        } else {
            $row['identifiers'] = array();
        }
        $row['diverged'] = ! empty($row['diverged']) ? 1 : 0;
        return $row;
    }
}
