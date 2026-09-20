<?php

namespace Tsugi\Services\Site;

use \Tsugi\Core\LTIX;

/**
 * Installation-wide site config. One row (site_id = 1).
 *
 * body is the public landing HTML. Empty / NULL means index.php uses its
 * built-in welcome text unless json.use_catalog is true, in which case
 * the public landing page is the course catalog.
 */
class Site {

    const SITE_ID = 1;

    /** @var \HTMLPurifier|null */
    private static $purifier = null;

    /**
     * Stored landing HTML, or null when there is no custom copy.
     *
     * Catalog-as-landing does not count as a custom body.
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
     * True when index.php should render the course catalog instead of HTML.
     */
    public static function useCatalog() {
        $row = self::row();
        if ( $row === null ) {
            return false;
        }
        return self::jsonFlag($row['json'] ?? null, 'use_catalog');
    }

    /**
     * True when $json is an object with a non-empty $key.
     *
     * @param mixed $json
     * @param string $key
     */
    public static function jsonFlag($json, $key) {
        if ( ! is_string($json) || trim($json) === '' ) {
            return false;
        }
        $data = json_decode($json, true);
        return is_array($data) && ! empty($data[$key]);
    }

    /**
     * Save landing HTML and/or catalog-as-landing.
     *
     * Empty HTML with catalog off deletes the row (built-in welcome text).
     * Catalog on keeps a row even when body is empty so the flag persists.
     *
     * @param mixed $html
     * @param bool $use_catalog
     * @return bool True when a site row is stored, false when the default is used.
     */
    public static function save($html, $use_catalog = false) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $use_catalog = (bool) $use_catalog;
        $clean = self::purify(is_string($html) ? $html : '');
        $empty = self::isEmptyHtml($clean);
        if ( $empty && ! $use_catalog ) {
            self::clear();
            return false;
        }

        $body = $empty ? null : $clean;
        $json = $use_catalog ? json_encode(array('use_catalog' => true)) : null;
        $existing = self::row();
        if ( $existing === null ) {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}site (site_id, body, json, created_at, updated_at)
                 VALUES (:id, :body, :json, NOW(), NOW())",
                array(':id' => self::SITE_ID, ':body' => $body, ':json' => $json)
            );
        } else {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}site SET body = :body, json = :json, updated_at = NOW()
                 WHERE site_id = :id",
                array(':id' => self::SITE_ID, ':body' => $body, ':json' => $json)
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
