<?php

namespace Tsugi\Services\Cartridge;

/**
 * In-memory Common Cartridge import run: classify, record objects, append log.
 *
 * Persistence is Store. Tests and the future zip walker drive this session
 * without MySQL. Objects are not owned by one import; import_id is last-run.
 */
class Session {

    /** @var int */
    public $context_id;

    /** @var int */
    public $user_id;

    /** @var array<string, mixed>|null Current cc_import row */
    public $import = null;

    /** @var list<array<string, mixed>> */
    public $logs = array();

    /** @var list<array<string, mixed>> Course-local cc_object rows */
    public $objects = array();

    /** @var int */
    private $nextImportId = 1;

    /** @var int */
    private $nextLogId = 1;

    /** @var int */
    private $nextObjectId = 1;

    /**
     * @param int $context_id
     * @param int $user_id
     * @param list<array<string, mixed>> $existingObjects
     */
    public function __construct($context_id, $user_id = 0, array $existingObjects = array()) {
        $this->context_id = (int) $context_id;
        $this->user_id = (int) $user_id;
        foreach ( $existingObjects as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $this->objects[] = $row;
            $oid = isset($row['object_id']) ? (int) $row['object_id'] : 0;
            if ( $oid >= $this->nextObjectId ) {
                $this->nextObjectId = $oid + 1;
            }
        }
    }

    /**
     * @param array<string, mixed> $meta filename, title, manifest_identifier, cc_version, zip_sha256
     * @return array<string, mixed>
     */
    public function begin(array $meta = array()) {
        if ( $this->import !== null && ($this->import['status'] ?? '') === Matcher::STATUS_RUNNING ) {
            throw new \LogicException('An import is already running; call finish() first.');
        }

        $this->import = array(
            'import_id' => $this->nextImportId++,
            'context_id' => $this->context_id,
            'user_id' => $this->user_id > 0 ? $this->user_id : null,
            'filename' => self::metaString($meta, 'filename'),
            'title' => self::metaString($meta, 'title'),
            'manifest_identifier' => self::metaString($meta, 'manifest_identifier'),
            'cc_version' => self::metaString($meta, 'cc_version'),
            'zip_sha256' => self::metaString($meta, 'zip_sha256'),
            'status' => Matcher::STATUS_RUNNING,
            'created_count' => 0,
            'duplicate_count' => 0,
            'copy_count' => 0,
            'error_count' => 0,
            'json' => isset($meta['json']) ? $meta['json'] : null,
            'started_at' => self::now(),
            'finished_at' => null,
        );
        $this->logs = array();
        return $this->import;
    }

    /**
     * Classify one incoming resource against objects already in this course.
     *
     * @param string $resourceIdentifier
     * @param string $resourceType
     * @param string $contentHash
     * @param array<string, mixed> $extra title, item_identifier, identifiers
     * @return Decision
     */
    public function consider($resourceIdentifier, $resourceType, $contentHash, array $extra = array()) {
        $this->requireRunning();
        $resourceIdentifier = is_string($resourceIdentifier) ? $resourceIdentifier : '';
        $resourceType = is_string($resourceType) ? $resourceType : '';
        $contentHash = is_string($contentHash) ? $contentHash : '';
        if ( $resourceIdentifier === '' || $resourceType === '' ) {
            throw new \InvalidArgumentException('resource identifier and type are required');
        }

        $classified = Matcher::classify($this->objects, $resourceIdentifier, $resourceType, $contentHash);
        $d = new Decision();
        $d->action = $classified['action'];
        $d->object = $classified['object'];
        $d->resource_identifier = $resourceIdentifier;
        $d->resource_type = $resourceType;
        $d->content_hash = $contentHash;
        $d->item_identifier = self::metaString($extra, 'item_identifier');
        $d->title = self::metaString($extra, 'title');
        $ids = $extra['identifiers'] ?? array();
        $d->identifiers = is_array($ids) ? $ids : array();
        return $d;
    }

    /**
     * Persist the decision: log it, and create a cc_object for new/copy.
     *
     * @param string|null $localKind
     * @param int|null $localId
     * @param string|null $localKey
     * @return array<string, mixed> The log row
     */
    public function record(Decision $d, $localKind = null, $localId = null, $localKey = null) {
        $this->requireRunning();
        $action = Matcher::logAction($d->action);
        $object = $d->object;
        $localKind = is_string($localKind) && $localKind !== '' ? $localKind : null;
        $localId = $localId !== null ? (int) $localId : null;
        $localKey = is_string($localKey) && $localKey !== '' ? $localKey : null;

        if ( $d->action === Matcher::NEW || $d->action === Matcher::COPY ) {
            $object = $this->addObject($d, $localKind, $localId, $localKey);
            $d->object = $object;
        } else if ( $d->action === Matcher::DUPLICATE && is_array($object) ) {
            $object['import_id'] = $this->import['import_id'];
            $this->replaceObject($object);
            if ( $localKind === null && isset($object['local_kind']) ) {
                $localKind = $object['local_kind'];
            }
            if ( $localId === null && isset($object['local_id']) ) {
                $localId = $object['local_id'];
            }
            if ( $localKey === null && isset($object['local_key']) ) {
                $localKey = $object['local_key'];
            }
        }

        $objectId = is_array($object) && isset($object['object_id']) ? (int) $object['object_id'] : null;
        return $this->appendLog($action, $d, $localKind, $localId, $objectId, null);
    }

    /**
     * @param string $message
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function error($message, array $extra = array()) {
        $this->requireRunning();
        $d = new Decision();
        $d->action = Matcher::ACTION_ERROR;
        $d->resource_identifier = self::metaString($extra, 'resource_identifier');
        $d->resource_type = self::metaString($extra, 'resource_type');
        $d->item_identifier = self::metaString($extra, 'item_identifier');
        $d->title = self::metaString($extra, 'title');
        return $this->appendLog(Matcher::ACTION_ERROR, $d, null, null, null, (string) $message);
    }

    /**
     * Local edit: this object no longer blocks re-import of the original.
     *
     * @param int $object_id
     * @return bool
     */
    public function markDiverged($object_id) {
        $object_id = (int) $object_id;
        foreach ( $this->objects as $i => $row ) {
            if ( (int) ($row['object_id'] ?? 0) === $object_id ) {
                $this->objects[$i]['diverged'] = 1;
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $localKind
     * @param int $localId
     * @return bool
     */
    public function markDivergedByLocal($localKind, $localId) {
        $localKind = (string) $localKind;
        $localId = (int) $localId;
        $found = false;
        foreach ( $this->objects as $i => $row ) {
            if ( (string) ($row['local_kind'] ?? '') === $localKind
                && (int) ($row['local_id'] ?? 0) === $localId ) {
                $this->objects[$i]['diverged'] = 1;
                $found = true;
            }
        }
        return $found;
    }

    /**
     * Local delete: drop the origin row so the original can import as new.
     *
     * @param int $object_id
     * @return bool
     */
    public function forget($object_id) {
        $object_id = (int) $object_id;
        $kept = array();
        $found = false;
        foreach ( $this->objects as $row ) {
            if ( (int) ($row['object_id'] ?? 0) === $object_id ) {
                $found = true;
                continue;
            }
            $kept[] = $row;
        }
        $this->objects = $kept;
        return $found;
    }

    /**
     * @param string|null $status
     * @return array<string, mixed>
     */
    public function finish($status = null) {
        $this->requireRunning();
        $this->recount();
        if ( $status === null ) {
            $status = $this->defaultStatus();
        }
        $this->import['status'] = (string) $status;
        $this->import['finished_at'] = self::now();
        return $this->import;
    }

    /**
     * @return array<string, int>
     */
    public function recount() {
        $this->requireRunning();
        $created = 0;
        $duplicate = 0;
        $copy = 0;
        $error = 0;
        foreach ( $this->logs as $row ) {
            $action = $row['action'] ?? '';
            if ( $action === Matcher::ACTION_CREATED ) {
                $created++;
            } else if ( $action === Matcher::ACTION_DUPLICATE ) {
                $duplicate++;
            } else if ( $action === Matcher::ACTION_COPY ) {
                $copy++;
            } else if ( $action === Matcher::ACTION_ERROR ) {
                $error++;
            }
        }
        $this->import['created_count'] = $created;
        $this->import['duplicate_count'] = $duplicate;
        $this->import['copy_count'] = $copy;
        $this->import['error_count'] = $error;
        return array(
            'created_count' => $created,
            'duplicate_count' => $duplicate,
            'copy_count' => $copy,
            'error_count' => $error,
        );
    }

    /**
     * @return string
     */
    private function defaultStatus() {
        $errors = (int) $this->import['error_count'];
        $ok = (int) $this->import['created_count']
            + (int) $this->import['duplicate_count']
            + (int) $this->import['copy_count'];
        if ( $errors > 0 && $ok > 0 ) {
            return Matcher::STATUS_PARTIAL;
        }
        if ( $errors > 0 ) {
            return Matcher::STATUS_FAIL;
        }
        return Matcher::STATUS_OK;
    }

    /**
     * @param string $action
     * @param string|null $localKind
     * @param int|null $localId
     * @param int|null $objectId
     * @param string|null $message
     * @return array<string, mixed>
     */
    private function appendLog($action, Decision $d, $localKind, $localId, $objectId, $message) {
        $row = array(
            'log_id' => $this->nextLogId++,
            'import_id' => $this->import['import_id'],
            'resource_identifier' => $d->resource_identifier !== '' ? $d->resource_identifier : null,
            'item_identifier' => $d->item_identifier !== '' ? $d->item_identifier : null,
            'resource_type' => $d->resource_type !== '' ? $d->resource_type : null,
            'title' => $d->title !== '' ? $d->title : null,
            'action' => $action,
            'local_kind' => $localKind,
            'local_id' => $localId,
            'object_id' => $objectId,
            'message' => $message,
        );
        $this->logs[] = $row;
        return $row;
    }

    /**
     * @param string|null $localKind
     * @param int|null $localId
     * @param string|null $localKey
     * @return array<string, mixed>
     */
    private function addObject(Decision $d, $localKind, $localId, $localKey) {
        $row = array(
            'object_id' => $this->nextObjectId++,
            'context_id' => $this->context_id,
            'import_id' => $this->import['import_id'],
            'resource_identifier' => $d->resource_identifier,
            'item_identifier' => $d->item_identifier !== '' ? $d->item_identifier : null,
            'resource_type' => $d->resource_type,
            'identifiers' => $d->identifiers,
            'local_kind' => $localKind,
            'local_id' => $localId,
            'local_key' => $localKey,
            'content_hash' => $d->content_hash,
            'diverged' => 0,
        );
        $this->objects[] = $row;
        return $row;
    }

    /**
     * @param array<string, mixed> $object
     */
    private function replaceObject(array $object) {
        $oid = (int) ($object['object_id'] ?? 0);
        foreach ( $this->objects as $i => $row ) {
            if ( (int) ($row['object_id'] ?? 0) === $oid ) {
                $this->objects[$i] = $object;
                return;
            }
        }
    }

    private function requireRunning() {
        if ( $this->import === null || ($this->import['status'] ?? '') !== Matcher::STATUS_RUNNING ) {
            throw new \LogicException('begin() an import before recording resources.');
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @param string $key
     * @return string
     */
    private static function metaString(array $meta, $key) {
        if ( ! isset($meta[$key]) || ! is_string($meta[$key]) ) {
            return '';
        }
        return $meta[$key];
    }

    /**
     * @return string
     */
    private static function now() {
        return gmdate('Y-m-d H:i:s');
    }
}
