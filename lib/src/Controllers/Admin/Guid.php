<?php

namespace Tsugi\Controllers\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tsugi\Controllers\Admin;
use Tsugi\Controllers\Tool;
use Tsugi\Lumos\Application;
use Tsugi\Util\U;

/**
 * GUID column status for the core LTI tables, and a bounded null fill.
 */
class Guid extends Admin {

    const BATCH = 1000;
    const LIMIT = 5000;
    const SECONDS = 15;

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('guid'));
    }

    public function index() {
        if ( $r = $this->gate('admin.guid') ) return $r;

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $back = U::addSession($this->here());
            if ( ! Tool::csrfOk() ) {
                U::flashError('Missing or invalid CSRF token');
                return new RedirectResponse($back);
            }
            if ( (string) U::get($_POST, 'backfill', '') !== '1' ) {
                U::flashError('Unknown request.');
                return new RedirectResponse($back);
            }
            $result = self::fillAll();
            if ( $result['ok'] ) {
                U::flashSuccess($result['text']);
            } else {
                U::flashError($result['text']);
            }
            return new RedirectResponse($back);
        }

        $guid_rows = self::report();
        $guid_sql = array();
        foreach ( $guid_rows as $row ) {
            if ( empty($row['fillable']) ) {
                continue;
            }
            $sql = self::fillSql((string) $row['table']);
            if ( $sql !== '' ) {
                $guid_sql[] = $sql;
            }
        }
        $this->view('check/guid.php', array(
            'guid_rows' => $guid_rows,
            'guid_healthy' => self::healthy($guid_rows),
            'guid_sql' => $guid_sql,
            'guid_limit' => self::LIMIT,
            'guid_seconds' => self::SECONDS,
            'guid_commit' => self::BATCH,
        ));
    }

    /**
     * Unprefixed table => primary key. Only these tables are queried or updated.
     *
     * @return array<string, string>
     */
    public static function tables() {
        return array(
            'lti_user' => 'user_id',
            'lti_context' => 'context_id',
            'lti_key' => 'key_id',
            'lti_link' => 'link_id',
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public static function healthy(array $rows) {
        foreach ( $rows as $row ) {
            if ( ! empty($row['problem']) ) {
                return false;
            }
        }
        return count($rows) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function report() {
        global $PDOX;

        $prefix = self::prefix();
        if ( $prefix === false ) {
            return array(self::problemRow('', 'The database prefix is not a plain identifier.'));
        }

        $rows = array();
        foreach ( self::tables() as $table => $pk ) {
            $physical = $prefix.$table;
            if ( $PDOX->metadata($physical) === false ) {
                $rows[] = self::problemRow($table, 'Table is missing.');
                continue;
            }
            $column = $PDOX->rowDie(
                "SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = 'guid'",
                array(':table_name' => $physical)
            );
            if ( ! is_array($column) ) {
                $rows[] = self::problemRow($table, 'guid column is missing.');
                continue;
            }
            $type = strtolower((string) $column['column_type']);
            $nullable = strtoupper((string) $column['is_nullable']) === 'YES';
            $indexes = array();
            $index_rows = $PDOX->allRowsDie(
                "SELECT DISTINCT INDEX_NAME AS index_name
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = 'guid'
                 ORDER BY INDEX_NAME",
                array(':table_name' => $physical)
            );
            foreach ( $index_rows as $index_row ) {
                $name = (string) ($index_row['index_name'] ?? '');
                if ( $name !== '' ) {
                    $indexes[] = $name;
                }
            }
            $counts = $PDOX->rowDie(
                "SELECT COALESCE(SUM(CASE WHEN guid IS NULL THEN cnt ELSE 0 END), 0) AS nulls,
                        COALESCE(SUM(CASE WHEN guid IS NOT NULL THEN cnt ELSE 0 END), 0) AS filled,
                        COALESCE(SUM(CASE WHEN guid IS NOT NULL AND cnt > 1 THEN 1 ELSE 0 END), 0) AS duplicate_guids
                 FROM (
                     SELECT guid, COUNT(*) AS cnt
                     FROM {$physical}
                     GROUP BY guid
                 ) AS grouped"
            );
            $nulls = is_array($counts) ? (int) $counts['nulls'] : 0;
            $filled = is_array($counts) ? (int) $counts['filled'] : 0;
            $duplicates = is_array($counts) ? (int) $counts['duplicate_guids'] : 0;
            $type_ok = ($type === 'binary(16)');
            $problem = ! $type_ok || $nulls > 0 || $duplicates > 0;
            $note = '';
            if ( ! $type_ok ) {
                $note = 'guid is '.$type.', expected binary(16).';
            }
            $rows[] = array(
                'table' => $table,
                'present' => true,
                'type' => $type,
                'nullable' => $nullable,
                'indexes' => $indexes,
                'nulls' => $nulls,
                'filled' => $filled,
                'duplicates' => $duplicates,
                'problem' => $problem,
                'fillable' => $type_ok,
                'note' => $note,
            );
        }
        return $rows;
    }

    /**
     * Backfill every fillable table. Nothing here runs unless the admin button posts.
     *
     * @return array{ok: bool, text: string}
     */
    public static function fillAll() {
        $notes = array();
        $ran = false;
        $deadline = microtime(true) + self::SECONDS;
        $budget = self::LIMIT;
        $rows = self::report();
        foreach ( $rows as $index => $row ) {
            if ( empty($row['fillable']) ) {
                continue;
            }
            if ( $budget < 1 || microtime(true) >= $deadline ) {
                if ( self::rowsStillNull($rows, $index) ) {
                    $notes[] = 'More null GUIDs remain. Run Backfill again.';
                }
                break;
            }
            $ran = true;
            $result = self::fill((string) $row['table'], $deadline, $budget);
            $notes[] = $result['text'];
            if ( ! $result['ok'] ) {
                return array('ok' => false, 'text' => implode(' ', $notes));
            }
            $budget -= (int) $result['updated'];
            if ( empty($result['done']) ) {
                $notes[] = 'More null GUIDs remain. Run Backfill again.';
                break;
            }
        }
        if ( ! $ran ) {
            return array('ok' => false, 'text' => 'No guid columns to backfill.');
        }
        return array('ok' => true, 'text' => implode(' ', $notes));
    }

    /**
     * Fill null guids until the row budget or the deadline. Existing values are left alone.
     *
     * @param float|null $deadline
     * @return array{ok: bool, text: string, updated: int, done: bool}
     */
    public static function fill($table, $deadline = null, $rowLimit = null) {
        global $PDOX;

        $tables = self::tables();
        if ( ! isset($tables[$table]) ) {
            return array('ok' => false, 'text' => 'Unknown table.');
        }
        $prefix = self::prefix();
        if ( $prefix === false ) {
            return array('ok' => false, 'text' => 'The database prefix is not a plain identifier.');
        }
        $physical = $prefix.$table;
        if ( $PDOX->metadata($physical) === false || ! $PDOX->columnExists('guid', $physical) ) {
            return array('ok' => false, 'text' => $table.' has no guid column.');
        }
        $column = $PDOX->rowDie(
            "SELECT COLUMN_TYPE AS column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = 'guid'",
            array(':table_name' => $physical)
        );
        $type = is_array($column) ? strtolower((string) $column['column_type']) : '';
        if ( $type !== 'binary(16)' ) {
            return array('ok' => false, 'text' => $table.'.guid is '.$type.', expected binary(16).');
        }
        $pk = $tables[$table];
        $limit = $rowLimit === null ? self::LIMIT : (int) $rowLimit;
        $run = self::fillUntil($physical, $pk, $limit, $deadline, self::BATCH);
        if ( ! $run['ok'] ) {
            return array(
                'ok' => false,
                'text' => $table.': '.$run['text'],
                'updated' => (int) $run['updated'],
                'done' => false,
            );
        }
        $updated = (int) $run['updated'];
        return array(
            'ok' => true,
            'text' => $table.': filled '.$updated.' null GUID'.($updated === 1 ? '' : 's').'.',
            'updated' => $updated,
            'done' => ! empty($run['done']),
        );
    }

    /**
     * Commit every $commitSize rows until $rowLimit, $deadline, or the table is done.
     * $deadline is a microtime(true) instant, or null when there is no time limit.
     *
     * @param float|null $deadline
     * @param callable|null $onChunk
     * @return array{ok: bool, updated: int, done: bool, stopped: string, text: string}
     */
    public static function fillUntil($physical, $pk, $rowLimit, $deadline, $commitSize, $onChunk = null) {
        $rowLimit = (int) $rowLimit;
        $commitSize = (int) $commitSize;
        $stopped = array('ok' => false, 'updated' => 0, 'done' => false, 'stopped' => 'error', 'text' => '');
        if ( $rowLimit < 1 || $commitSize < 1 ) {
            $stopped['text'] = 'Row limit and commit size must be at least 1.';
            return $stopped;
        }
        if ( $deadline !== null && microtime(true) >= (float) $deadline ) {
            return array('ok' => true, 'updated' => 0, 'done' => false, 'stopped' => 'time', 'text' => '');
        }
        $after = -1;
        $updated = 0;
        $batchNo = 0;
        while ( true ) {
            $room = $rowLimit - $updated;
            $time_up = ($deadline !== null && microtime(true) >= (float) $deadline);
            if ( $room < 1 || $time_up ) {
                if ( $updated === 0 ) {
                    return array('ok' => true, 'updated' => 0, 'done' => false, 'stopped' => 'time', 'text' => '');
                }
                $more = self::nullRemains($physical, $pk, $after);
                $reason = $room < 1 ? 'rows' : 'time';
                return array(
                    'ok' => true,
                    'updated' => $updated,
                    'done' => ! $more,
                    'stopped' => $more ? $reason : 'done',
                    'text' => '',
                );
            }
            $chunkLimit = $commitSize < $room ? $commitSize : $room;
            $batchStarted = microtime(true);
            $chunk = self::fillChunk($physical, $pk, $chunkLimit, $after);
            if ( ! $chunk['ok'] ) {
                $text = $chunk['text'];
                if ( $updated > 0 ) {
                    $text .= ' Committed '.$updated.' before this batch.';
                }
                return array('ok' => false, 'updated' => $updated, 'done' => false, 'stopped' => 'error', 'text' => $text);
            }
            if ( (int) $chunk['selected'] === 0 ) {
                return array('ok' => true, 'updated' => $updated, 'done' => true, 'stopped' => 'done', 'text' => '');
            }
            $updated += (int) $chunk['updated'];
            $after = (int) $chunk['hi'];
            $batchNo++;
            if ( is_callable($onChunk) ) {
                $onChunk(array(
                    'batch' => $batchNo,
                    'updated' => (int) $chunk['updated'],
                    'cumulative' => $updated,
                    'lo' => (int) $chunk['lo'],
                    'hi' => (int) $chunk['hi'],
                    'elapsed' => microtime(true) - $batchStarted,
                ));
            }
            if ( (int) $chunk['selected'] < $chunkLimit ) {
                return array('ok' => true, 'updated' => $updated, 'done' => true, 'stopped' => 'done', 'text' => '');
            }
        }
    }

    /**
     * True when a null guid remains after $after.
     */
    private static function nullRemains($physical, $pk, $after) {
        global $PDOX;

        $q = $PDOX->queryReturnError(
            "SELECT `{$pk}` AS pk FROM `{$physical}`
             WHERE guid IS NULL AND `{$pk}` > :after
             ORDER BY `{$pk}`
             LIMIT 1",
            array(':after' => (int) $after)
        );
        if ( ! $q->success ) {
            $q->closeCursor();
            return true;
        }
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        $q->closeCursor();
        return is_array($row);
    }

    /**
     * One transaction of up to $limit rows with primary key greater than $after.
     * Each row is its own UPDATE. The guid is stored as a literal.
     *
     * @return array{ok: bool, selected: int, updated: int, lo: int, hi: int, text: string}
     */
    public static function fillChunk($physical, $pk, $limit, $after = -1) {
        global $PDOX;

        $limit = (int) $limit;
        $after = (int) $after;
        $empty = array('ok' => false, 'selected' => 0, 'updated' => 0, 'lo' => 0, 'hi' => 0, 'text' => '');
        if ( $limit < 1 ) {
            $empty['text'] = 'Batch size must be at least 1.';
            return $empty;
        }
        if ( ! self::plainIdentifier($physical) || ! self::plainIdentifier($pk) ) {
            $empty['text'] = 'Unsafe table or key.';
            return $empty;
        }
        try {
            if ( ! $PDOX->beginTransaction() ) {
                $empty['text'] = 'Could not start a transaction.';
                return $empty;
            }
        } catch ( \Throwable $e ) {
            $empty['text'] = 'Could not start a transaction.';
            return $empty;
        }
        $q = $PDOX->queryReturnError(
            "SELECT `{$pk}` AS pk FROM `{$physical}`
             WHERE guid IS NULL AND `{$pk}` > :after
             ORDER BY `{$pk}`
             LIMIT {$limit}",
            array(':after' => $after)
        );
        if ( ! $q->success ) {
            $empty['text'] = (string) $q->errorImplode.'. Rolled back this batch.';
            $q->closeCursor();
            self::rollBackQuietly();
            return $empty;
        }
        $ids = $q->fetchAll(\PDO::FETCH_ASSOC);
        $q->closeCursor();
        if ( ! is_array($ids) || count($ids) === 0 ) {
            try {
                if ( ! $PDOX->commit() ) {
                    $empty['text'] = 'Commit failed. Rolled back this batch.';
                    self::rollBackQuietly();
                    return $empty;
                }
            } catch ( \Throwable $e ) {
                $empty['text'] = 'Commit failed. Rolled back this batch.';
                self::rollBackQuietly();
                return $empty;
            }
            $empty['ok'] = true;
            return $empty;
        }
        $lo = (int) $ids[0]['pk'];
        $hi = (int) $ids[count($ids) - 1]['pk'];
        $updated = 0;
        foreach ( $ids as $id_row ) {
            $result = self::fillOneRow($physical, $pk, (int) $id_row['pk']);
            if ( ! $result['ok'] ) {
                $empty['text'] = $result['text'].' Rolled back this batch.';
                self::rollBackQuietly();
                return $empty;
            }
            $updated += (int) $result['updated'];
        }
        try {
            if ( ! $PDOX->commit() ) {
                $empty['text'] = 'Commit failed. Rolled back this batch.';
                self::rollBackQuietly();
                return $empty;
            }
        } catch ( \Throwable $e ) {
            $empty['text'] = 'Commit failed. Rolled back this batch.';
            self::rollBackQuietly();
            return $empty;
        }
        return array(
            'ok' => true,
            'selected' => count($ids),
            'updated' => $updated,
            'lo' => $lo,
            'hi' => $hi,
            'text' => '',
        );
    }

    private static function rollBackQuietly() {
        global $PDOX;
        try {
            if ( $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
        } catch ( \Throwable $e ) {
            // The connection close rolls the batch back.
        }
    }

    /**
     * @return array{ok: bool, updated: int, text: string}
     */
    private static function fillOneRow($physical, $pk, $id) {
        global $PDOX;

        if ( ! self::plainIdentifier($physical) || ! self::plainIdentifier($pk) ) {
            return array('ok' => false, 'updated' => 0, 'text' => 'Unsafe table or key.');
        }
        $hex = self::newGuidHex();
        if ( $hex === '' ) {
            return array('ok' => false, 'updated' => 0, 'text' => 'Could not generate a guid.');
        }
        $q = $PDOX->queryReturnError(
            "UPDATE `{$physical}` SET guid = UNHEX(:hex) WHERE `{$pk}` = :id AND guid IS NULL",
            array(':hex' => $hex, ':id' => (int) $id)
        );
        if ( ! $q->success ) {
            $error = (string) $q->errorImplode;
            $q->closeCursor();
            return array('ok' => false, 'updated' => 0, 'text' => $error);
        }
        $updated = (int) $q->rowCount();
        $q->closeCursor();
        return array('ok' => true, 'updated' => $updated, 'text' => '');
    }

    /**
     * 32 hex characters from MySQL UUID(), or empty when that value is unusable.
     */
    private static function newGuidHex() {
        global $PDOX;

        $q = $PDOX->queryReturnError("SELECT REPLACE(UUID(), '-', '') AS guid_hex");
        if ( ! $q->success ) {
            $q->closeCursor();
            return '';
        }
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        $q->closeCursor();
        $hex = '';
        if ( is_array($row) ) {
            foreach ( $row as $key => $value ) {
                if ( strcasecmp((string) $key, 'guid_hex') === 0 ) {
                    $hex = strtolower((string) $value);
                }
            }
        }
        if ( ! preg_match('/^[0-9a-f]{32}$/', $hex) ) {
            return '';
        }
        return $hex;
    }

    /**
     * The per-row statement fill() runs. Empty when the table or prefix is not usable.
     */
    public static function fillSql($table) {
        $tables = self::tables();
        if ( ! isset($tables[$table]) ) {
            return '';
        }
        $prefix = self::prefix();
        if ( $prefix === false ) {
            return '';
        }
        $physical = $prefix.$table;
        $pk = $tables[$table];
        return "SELECT REPLACE(UUID(), '-', '')\n"
            ."UPDATE {$physical}\n"
            ."SET guid = UNHEX('<hex from the SELECT>')\n"
            ."WHERE {$pk} = <id> AND guid IS NULL";
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private static function rowsStillNull(array $rows, $from) {
        for ( $i = (int) $from; $i < count($rows); $i++ ) {
            if ( ! empty($rows[$i]['fillable']) && (int) $rows[$i]['nulls'] > 0 ) {
                return true;
            }
        }
        return false;
    }

    private static function plainIdentifier($name) {
        return preg_match('/^[A-Za-z0-9_]+$/', (string) $name) === 1;
    }

    /**
     * @return string|false
     */
    private static function prefix() {
        global $CFG;
        $prefix = (string) $CFG->dbprefix;
        if ( $prefix !== '' && ! preg_match('/^[A-Za-z0-9_]+$/', $prefix) ) {
            return false;
        }
        return $prefix;
    }

    /**
     * @return array<string, mixed>
     */
    private static function problemRow($table, $note) {
        return array(
            'table' => $table,
            'present' => false,
            'type' => '',
            'nullable' => false,
            'indexes' => array(),
            'nulls' => 0,
            'filled' => 0,
            'duplicates' => 0,
            'problem' => true,
            'fillable' => false,
            'note' => $note,
        );
    }
}
