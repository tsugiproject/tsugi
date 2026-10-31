<?php

namespace Tsugi\Core;

/**
 * Printable guids for the four core LTI rows.
 *
 * The database column is BINARY(16). Callers see 32 lowercase hex characters.
 * A stored guid is never replaced. A null guid is filled by generating a
 * candidate, writing it only while the column is still null, then reading
 * the stored bytes back. The object keeps that read, so two requests that
 * generate different candidates end up with the one row that won.
 */
class Guid {

    private const TABLES = array(
        'lti_user' => 'user_id',
        'lti_context' => 'context_id',
        'lti_key' => 'key_id',
        'lti_link' => 'link_id',
    );

    /** Statements run by ensure() and the read used when a row omitted the column. */
    private static $sqlCount = 0;

    /**
     * Hex guid for a row being turned into an object.
     *
     * No primary key: return null and do not touch the database. That covers a
     * new object and a LEFT JOIN that did not match.
     *
     * The guid column is present and non-null: convert it and run no SQL.
     * The guid column is present and null: fill it if it is still null.
     * The guid column was not selected: read the row first. Fill only when
     * that read shows a real row whose guid is null.
     *
     * @param array<string,mixed> $row
     * @param array<int,string> $idKeys
     * @param array<int,string> $guidKeys
     * @return string|null
     */
    public static function forRow(array $row, $table, $pkColumn, array $idKeys, array $guidKeys) {
        if ( ! self::allowed($table, $pkColumn) ) {
            return null;
        }
        $id = self::rowId($row, $idKeys);
        if ( $id < 1 ) {
            return null;
        }

        $seen = false;
        $binary = null;
        foreach ( $guidKeys as $key ) {
            if ( array_key_exists($key, $row) ) {
                $seen = true;
                $binary = $row[$key];
                break;
            }
        }
        if ( $seen ) {
            return self::ensure($table, $pkColumn, $id, $binary);
        }

        $stored = self::readGuid($table, $pkColumn, $id);
        if ( ! $stored['found'] ) {
            return null;
        }
        $hex = self::hexFromValue($stored['binary']);
        if ( $hex !== null ) {
            return $hex;
        }
        return self::ensure($table, $pkColumn, $id, null);
    }

    /**
     * 32-character lowercase hex for this primary key.
     *
     * A usable guid already in $binary is returned with no SQL. Null means
     * the column is null: generate a candidate, store it only while the
     * column is still null, then SELECT the row and return bin2hex of those
     * stored bytes. The candidate is never returned. Zero updated rows is
     * not a failure; another request may have written first. A missing row
     * or a still-null column returns null.
     *
     * Uses the connection's current transaction. Does not commit.
     *
     * @param string $table lti_user, lti_context, lti_key, or lti_link
     * @param string $pkColumn
     * @param int $id
     * @param string|null $binary BINARY(16), or null
     * @return string|null
     */
    public static function ensure($table, $pkColumn, $id, $binary) {
        $hex = self::hexFromValue($binary);
        if ( $hex !== null ) {
            return $hex;
        }
        $id = (int) $id;
        if ( $id < 1 || ! self::allowed($table, $pkColumn) ) {
            return null;
        }

        $PDOX = LTIX::getConnection();
        $physical = self::physical($table);
        if ( $physical === null ) {
            return null;
        }

        $candidate = self::candidateHex();
        if ( $candidate === null ) {
            return null;
        }

        self::$sqlCount++;
        $stmt = $PDOX->queryDie(
            "UPDATE {$physical} SET guid = UNHEX(:guid)
             WHERE {$pkColumn} = :id AND guid IS NULL",
            array(':guid' => $candidate, ':id' => $id)
        );
        if ( is_object($stmt) && method_exists($stmt, 'closeCursor') ) {
            $stmt->closeCursor();
        }

        $stored = self::readGuid($table, $pkColumn, $id);
        if ( ! $stored['found'] || ! is_string($stored['binary']) || strlen($stored['binary']) !== 16 ) {
            return null;
        }
        return bin2hex($stored['binary']);
    }

    /**
     * One candidate, as 32 lowercase hex characters. Not the value assigned
     * to the object; the following read decides that.
     *
     * @return string|null
     */
    private static function candidateHex() {
        $PDOX = LTIX::getConnection();
        self::$sqlCount++;
        $row = $PDOX->rowDie("SELECT LOWER(REPLACE(UUID(), '-', '')) AS guid_hex");
        if ( ! is_array($row) || ! isset($row['guid_hex']) || ! is_string($row['guid_hex']) ) {
            return null;
        }
        $hex = strtolower($row['guid_hex']);
        if ( ! preg_match('/^[0-9a-f]{32}$/', $hex) ) {
            return null;
        }
        return $hex;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $idKeys
     */
    private static function rowId(array $row, array $idKeys) {
        foreach ( $idKeys as $key ) {
            if ( ! array_key_exists($key, $row) ) {
                continue;
            }
            $value = $row[$key];
            if ( is_numeric($value) && (int) $value > 0 ) {
                return (int) $value;
            }
        }
        return 0;
    }

    /**
     * @return array{found:bool,binary:mixed}
     */
    private static function readGuid($table, $pkColumn, $id) {
        $PDOX = LTIX::getConnection();
        $physical = self::physical($table);
        if ( $physical === null ) {
            return array('found' => false, 'binary' => null);
        }
        self::$sqlCount++;
        $row = $PDOX->rowDie(
            "SELECT guid FROM {$physical} WHERE {$pkColumn} = :id",
            array(':id' => (int) $id)
        );
        if ( ! is_array($row) || ! array_key_exists('guid', $row) ) {
            return array('found' => false, 'binary' => null);
        }
        return array('found' => true, 'binary' => $row['guid']);
    }

    /**
     * BINARY(16) or an already-printable 32-hex string. Anything else is null.
     *
     * @param mixed $binary
     * @return string|null
     */
    private static function hexFromValue($binary) {
        if ( ! is_string($binary) ) {
            return null;
        }
        if ( strlen($binary) === 16 ) {
            return bin2hex($binary);
        }
        if ( preg_match('/^[0-9a-fA-F]{32}$/', $binary) ) {
            return strtolower($binary);
        }
        return null;
    }

    private static function allowed($table, $pkColumn) {
        return isset(self::TABLES[$table]) && self::TABLES[$table] === $pkColumn;
    }

    /**
     * Physical table name, or null when the prefix is not a plain identifier.
     */
    private static function physical($table) {
        global $CFG;
        $prefix = '';
        if ( isset($CFG) && is_object($CFG) && isset($CFG->dbprefix) ) {
            $prefix = (string) $CFG->dbprefix;
        }
        if ( ! preg_match('/^[A-Za-z0-9_]*$/', $prefix) ) {
            return null;
        }
        return $prefix.$table;
    }
}
