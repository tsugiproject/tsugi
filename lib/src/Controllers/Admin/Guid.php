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

    const BATCH = 5000;

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
        $this->view('check/guid.php', array(
            'guid_rows' => $guid_rows,
            'guid_healthy' => self::healthy($guid_rows),
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
                "SELECT SUM(CASE WHEN guid IS NULL THEN 1 ELSE 0 END) AS nulls,
                        SUM(CASE WHEN guid IS NOT NULL THEN 1 ELSE 0 END) AS filled
                 FROM {$physical}"
            );
            $nulls = is_array($counts) ? (int) $counts['nulls'] : 0;
            $filled = is_array($counts) ? (int) $counts['filled'] : 0;
            $type_ok = ($type === 'binary(16)');
            $problem = ! $type_ok || $nulls > 0;
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
        foreach ( self::report() as $row ) {
            if ( empty($row['fillable']) ) {
                continue;
            }
            $ran = true;
            $result = self::fill((string) $row['table']);
            $notes[] = $result['text'];
            if ( ! $result['ok'] ) {
                return array('ok' => false, 'text' => implode(' ', $notes));
            }
        }
        if ( ! $ran ) {
            return array('ok' => false, 'text' => 'No guid columns to backfill.');
        }
        return array('ok' => true, 'text' => implode(' ', $notes));
    }

    /**
     * Fill at most BATCH null guid values. Existing values are left alone.
     *
     * @return array{ok: bool, text: string}
     */
    public static function fill($table) {
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
        $limit = (int) self::BATCH;
        // Same statement as admin/backfill-guid.php. Valid on MySQL 5.6/5.7 and 8.
        $sql = "UPDATE {$physical}
            SET guid = UNHEX(REPLACE(UUID(), '-', ''))
            WHERE guid IS NULL
            ORDER BY {$pk}
            LIMIT {$limit}";
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            return array('ok' => false, 'text' => 'Could not fill '.$table.' GUIDs: '.$q->errorImplode);
        }
        $updated = (int) $q->rowCount();
        return array(
            'ok' => true,
            'text' => $table.': filled '.$updated.' null GUID'.($updated === 1 ? '' : 's').'.',
        );
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
            'problem' => true,
            'fillable' => false,
            'note' => $note,
        );
    }
}
