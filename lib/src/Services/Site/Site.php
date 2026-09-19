<?php

namespace Tsugi\Services\Site;

use \Tsugi\Core\LTIX;

/**
 * Installation-wide site config. One row (site_id = 1).
 *
 * body is the public landing HTML. Empty / NULL means index.php uses its
 * built-in welcome text.
 */
class Site {

    const SITE_ID = 1;

    /** @var \HTMLPurifier|null */
    private static $purifier = null;

    /**
     * Stored landing HTML, or null to use the built-in index.php copy.
     *
     * @return string|null
     */
    public static function body() {
        $row = self::row();
        if ( $row === null ) {
            return null;
        }
        $html = $row['body'] ?? null;
        if ( ! is_string($html) || self::isEmptyHtml($html) ) {
            return null;
        }
        return $html;
    }

    /**
     * Save landing HTML. Empty / whitespace / CKEditor blank deletes the row.
     *
     * @param mixed $html
     * @return bool True when a custom body is stored, false when the default is used.
     */
    public static function save($html) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $clean = self::purify(is_string($html) ? $html : '');
        if ( self::isEmptyHtml($clean) ) {
            self::clear();
            return false;
        }

        $existing = self::row();
        if ( $existing === null ) {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}site (site_id, body, json, created_at, updated_at)
                 VALUES (:id, :body, NULL, NOW(), NOW())",
                array(':id' => self::SITE_ID, ':body' => $clean)
            );
        } else {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}site SET body = :body, updated_at = NOW()
                 WHERE site_id = :id",
                array(':id' => self::SITE_ID, ':body' => $clean)
            );
        }
        return true;
    }

    /**
     * Remove the singleton row so index.php shows the built-in welcome text.
     */
    public static function clear() {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}site WHERE site_id = :id",
            array(':id' => self::SITE_ID),
            false
        );
        if ( is_object($stmt) ) {
            $stmt->closeCursor();
        }
    }

    /**
     * True when the site table exists (after database upgrade).
     */
    public static function tableExists() {
        global $CFG, $PDOX;

        if ( ! isset($PDOX) || $PDOX === false ) {
            return false;
        }
        try {
            LTIX::getConnection();
        } catch ( \Throwable $e ) {
            return false;
        }
        $meta = $PDOX->metadata("{$CFG->dbprefix}site");
        return $meta !== false;
    }

    /**
     * @return array{site_id:mixed,body:?string,json:?string}|null
     */
    private static function row() {
        global $CFG, $PDOX;

        if ( ! isset($PDOX) || $PDOX === false ) {
            return null;
        }
        try {
            LTIX::getConnection();
        } catch ( \Throwable $e ) {
            return null;
        }
        $stmt = $PDOX->queryReturnError(
            "SELECT site_id, body, json FROM {$CFG->dbprefix}site WHERE site_id = :id",
            array(':id' => self::SITE_ID),
            false
        );
        if ( ! is_object($stmt) || ! $stmt->success ) {
            return null;
        }
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ( ! is_array($row) ) {
            return null;
        }
        return $row;
    }

    /**
     * Blank, NULL, or CKEditor empty markup (e.g. &lt;p&gt;&nbsp;&lt;/p&gt;).
     * An image-only body is not empty.
     */
    public static function isEmptyHtml($html) {
        if ( $html === null ) {
            return true;
        }
        $html = (string) $html;
        if ( trim($html) === '' ) {
            return true;
        }
        if ( preg_match('/<(img|iframe|video|audio|object|embed|table|figure)\b/i', $html) ) {
            return false;
        }
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        return $text === '';
    }

    public static function purify($html) {
        if ( $html === null ) {
            return '';
        }
        $html = (string) $html;
        if ( $html === '' ) {
            return '';
        }
        return self::purifier()->purify($html);
    }

    private static function purifier() {
        if ( self::$purifier === null ) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.DefinitionImpl', null);
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier;
    }
}
