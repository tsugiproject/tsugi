<?php

namespace Tsugi\Services\Pages;

use Tsugi\Core\LTIX;
require_once __DIR__ . '/../Files/FileRepository.php';
use Tsugi\Services\Files\FileRepository;
use Tsugi\Util\CCFileBase;

/**
 * Load and persist course wiki pages and page_history. Does not emit HTTP.
 */
class PageRepository {

    /** Stored URL prefix; must match Controllers\Pages::ROUTE. */
    const HREF_PREFIX = '/pages';

    /**
     * Path form of a course page URL (/pages/{logical_key}).
     *
     * @param mixed $logical_key
     * @return string|null
     */
    public static function hrefForLogicalKey($logical_key) {
        if ( ! is_string($logical_key) || trim($logical_key) === '' ) {
            return null;
        }
        return self::HREF_PREFIX . '/' . rawurlencode($logical_key);
    }

    /**
     * HTML document for Common Cartridge wiki_content. Canvas reads the page
     * name from the <title> tag.
     *
     * @param mixed $title
     * @param mixed $body HTML fragment or full document
     * @return string
     */
    public static function cartridgeDocument($title, $body) {
        $title_esc = htmlspecialchars(is_string($title) ? $title : '', ENT_QUOTES, 'UTF-8');
        $inner = is_string($body) ? $body : '';
        if ( preg_match('/<html[\s>]/i', $inner) ) {
            if ( preg_match('/<title\b[^>]*>.*?<\/title>/is', $inner) ) {
                return (string) preg_replace('/<title\b[^>]*>.*?<\/title>/is', '<title>'.$title_esc.'</title>', $inner, 1);
            }
            if ( preg_match('/<head\b[^>]*>/i', $inner) ) {
                return (string) preg_replace('/<head\b[^>]*>/i', '$0<title>'.$title_esc.'</title>', $inner, 1);
            }
            return $inner;
        }
        return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
            .'<title>'.$title_esc.'</title></head><body>'.$inner.'</body></html>';
    }

    /**
     * Course page for Common Cartridge export, or null if it cannot be loaded.
     *
     * @param mixed $page_id
     * @param mixed $logical_key
     * @param int $context_id
     * @return array{title:string,logical_key:string,body:string,html:string}|null
     */
    public static function readExportPayload($page_id, $logical_key, $context_id) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        $pid = is_numeric($page_id) ? (int) $page_id : 0;
        $key = is_string($logical_key) ? trim($logical_key) : '';
        if ( $pid < 1 && $key === '' ) {
            return null;
        }
        try {
            LTIX::getConnection();
        } catch ( \Throwable $e ) {
            return null;
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            return null;
        }
        $p = $CFG->dbprefix;
        $row = null;
        if ( $pid > 0 && $cid > 0 ) {
            $row = $PDOX->rowDie(
                "SELECT page_id, title, logical_key, body
                 FROM {$p}pages
                 WHERE page_id = :PID AND context_id = :CID",
                array(':PID' => $pid, ':CID' => $cid)
            );
        }
        if ( ! is_array($row) && $key !== '' && $cid > 0 ) {
            $row = $PDOX->rowDie(
                "SELECT page_id, title, logical_key, body
                 FROM {$p}pages
                 WHERE logical_key = :KEY AND context_id = :CID",
                array(':KEY' => $key, ':CID' => $cid)
            );
        }
        if ( ! is_array($row) ) {
            return null;
        }
        $title = isset($row['title']) && is_string($row['title']) ? $row['title'] : '';
        $lk = isset($row['logical_key']) && is_string($row['logical_key']) ? $row['logical_key'] : $key;
        $body = isset($row['body']) && is_string($row['body']) ? $row['body'] : '';
        return array(
            'title' => $title,
            'logical_key' => $lk,
            'body' => $body,
            'html' => self::cartridgeDocument($title, $body),
        );
    }

    /**
     * Generate a logical key from a title.
     *
     * @param string $title
     * @return string
     */
    public static function generateLogicalKey($title) {
        $key = strtolower($title);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = preg_replace('/\s+/', ' ', $key);
        $key = str_replace(' ', '-', $key);
        $key = trim($key, '-');
        if ( strlen($key) > 99 ) {
            $key = substr($key, 0, 99);
            $key = rtrim($key, '-');
        }
        if ( empty($key) ) {
            $key = 'page-' . time();
        }
        return $key;
    }

    /**
     * Unique logical_key for add/edit (suffix -1, -2, …).
     *
     * @param int $context_id
     * @param string $logical_key
     * @param int $exclude_page_id
     * @return string
     */
    public static function uniqueLogicalKey($context_id, $logical_key, $exclude_page_id = 0) {
        global $CFG, $PDOX;
        $existing = self::logicalKeyRow($context_id, $logical_key, $exclude_page_id);
        if ( ! $existing ) {
            return $logical_key;
        }
        $counter = 1;
        $original_key = $logical_key;
        while ( $existing ) {
            $logical_key = $original_key . '-' . $counter;
            if ( strlen($logical_key) > 99 ) {
                $logical_key = substr($original_key, 0, 99 - strlen('-' . $counter)) . '-' . $counter;
            }
            $existing = self::logicalKeyRow($context_id, $logical_key, $exclude_page_id);
            $counter++;
        }
        return $logical_key;
    }

    /**
     * @return array<string, mixed>|false
     */
    private static function logicalKeyRow($context_id, $logical_key, $exclude_page_id = 0) {
        global $CFG, $PDOX;
        if ( $exclude_page_id > 0 ) {
            return $PDOX->rowDie(
                "SELECT page_id FROM {$CFG->dbprefix}pages
                 WHERE context_id = :CID AND logical_key = :KEY AND page_id != :PID",
                array(':CID' => $context_id, ':KEY' => $logical_key, ':PID' => $exclude_page_id)
            );
        }
        return $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages
             WHERE context_id = :CID AND logical_key = :KEY",
            array(':CID' => $context_id, ':KEY' => $logical_key)
        );
    }

    /**
     * Expand canonical FILEBASE URLs for the editor or browser.
     *
     * @param string $html
     * @param string $fileBaseUrl
     * @param string[] $localPrefixes
     * @return string
     */
    public static function expandHtml($html, $fileBaseUrl, array $localPrefixes) {
        return FileRepository::forceFileAnchorsNewTab(
            CCFileBase::expand($html, $fileBaseUrl, $localPrefixes)
        );
    }

    /**
     * Convert current-course URLs to $IMS-CC-FILEBASE$ before storing HTML.
     *
     * @param string $html
     * @param string $fileBaseUrl
     * @param string[] $localPrefixes
     * @param int $context_id
     * @return string
     */
    public static function canonicalizeHtml($html, $fileBaseUrl, array $localPrefixes, $context_id) {
        return FileRepository::rewriteDownloadHrefsToPaths(
            CCFileBase::canonicalize($html, $fileBaseUrl, $localPrefixes),
            $context_id
        );
    }

    /**
     * @param int $context_id
     * @param string $logical_key
     * @param bool $published_only
     * @return array<string, mixed>|false
     */
    public static function loadByLogicalKey($context_id, $logical_key, $published_only = false) {
        global $CFG, $PDOX;
        $sql = "SELECT page_id, title, body, published, is_main
                FROM {$CFG->dbprefix}pages
                WHERE context_id = :CID AND logical_key = :KEY";
        if ( $published_only ) {
            $sql .= " AND published = 1";
        }
        return $PDOX->rowDie($sql, array(':CID' => $context_id, ':KEY' => $logical_key));
    }

    /**
     * @param int $context_id
     * @param bool $published_only
     * @return array<string, mixed>|false
     */
    public static function loadMain($context_id, $published_only = false) {
        global $CFG, $PDOX;
        $sql = "SELECT page_id, title, body, published, is_main
                FROM {$CFG->dbprefix}pages
                WHERE context_id = :CID AND is_main = 1";
        if ( $published_only ) {
            $sql .= " AND published = 1";
        }
        return $PDOX->rowDie($sql, array(':CID' => $context_id));
    }

    /**
     * @param int $context_id
     * @param bool $published_only
     * @return list<array<string, mixed>>
     */
    public static function listForView($context_id, $published_only = false) {
        global $CFG, $PDOX;
        $sql = "SELECT page_id, title, body, published, is_main
                FROM {$CFG->dbprefix}pages
                WHERE context_id = :CID";
        if ( $published_only ) {
            $sql .= " AND published = 1";
        }
        return $PDOX->allRowsDie($sql, array(':CID' => $context_id));
    }

    /**
     * @param int $context_id
     * @param bool $published_only
     * @return list<array<string, mixed>>
     */
    public static function listForPicker($context_id, $published_only = false) {
        global $CFG, $PDOX;
        $sql = "SELECT page_id, title, logical_key
                FROM {$CFG->dbprefix}pages
                WHERE context_id = :CID";
        if ( $published_only ) {
            $sql .= " AND published = 1";
        }
        $sql .= " ORDER BY title ASC";
        return $PDOX->allRowsDie($sql, array(':CID' => $context_id));
    }

    /**
     * @param int $page_id
     * @param int $context_id
     * @return array<string, mixed>|false
     */
    public static function load($page_id, $context_id) {
        global $CFG, $PDOX;
        return $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}pages
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
    }

    public static function countForContext($context_id) {
        global $CFG, $PDOX;
        $row = $PDOX->rowDie(
            "SELECT COUNT(*) as cnt FROM {$CFG->dbprefix}pages WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        return is_array($row) ? (int) $row['cnt'] : 0;
    }

    public static function clearMain($context_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}pages SET is_main = 0 WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
    }

    public static function clearFront($context_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}pages SET is_front_page = 0 WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
    }

    /**
     * @return bool
     */
    public static function insert($context_id, $user_id, $title, $logical_key, $body, $published, $is_main, $is_front_page) {
        global $CFG, $PDOX;
        $q = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}pages
                (context_id, title, logical_key, body, published, is_main, is_front_page, user_id, created_at, updated_at)
             VALUES
                (:CID, :title, :key, :body, :published, :main, :front_page, :UID, NOW(), NOW())",
            array(
                ':CID' => $context_id,
                ':title' => $title,
                ':key' => $logical_key,
                ':body' => $body,
                ':published' => $published,
                ':main' => $is_main,
                ':front_page' => $is_front_page,
                ':UID' => $user_id
            )
        );
        return (bool) $q->success;
    }

    /**
     * @return bool
     */
    public static function update($page_id, $context_id, $title, $logical_key, $body, $published, $is_main, $is_front_page) {
        global $CFG, $PDOX;
        $current = $PDOX->rowDie(
            "SELECT title, body FROM {$CFG->dbprefix}pages WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        $content_changed = ($current && ($current['title'] !== $title || $current['body'] !== $body));

        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}pages
             SET title = :title, logical_key = :key, body = :body,
                 published = :published, is_main = :main, is_front_page = :front_page, updated_at = NOW()
             WHERE page_id = :PID AND context_id = :CID",
            array(
                ':title' => $title,
                ':key' => $logical_key,
                ':body' => $body,
                ':published' => $published,
                ':main' => $is_main,
                ':front_page' => $is_front_page,
                ':PID' => $page_id,
                ':CID' => $context_id
            )
        );
        if ( ! $q->success ) {
            return false;
        }
        if ( $content_changed && $current ) {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}page_history (page_id, title, body) VALUES (:PID, :title, :body)",
                array(':PID' => $page_id, ':title' => $current['title'], ':body' => $current['body'])
            );
            self::trimHistory($page_id);
        }
        return true;
    }

    /**
     * @return list<int>
     */
    public static function pageIdsWithHistory($context_id) {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT DISTINCT ph.page_id FROM {$CFG->dbprefix}page_history ph
             JOIN {$CFG->dbprefix}pages p ON ph.page_id = p.page_id
             WHERE p.context_id = :CID",
            array(':CID' => $context_id)
        );
        return array_column($rows, 'page_id');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listForManage($context_id) {
        global $CFG, $PDOX;
        return $PDOX->allRowsDie(
            "SELECT page_id, title, logical_key, published, is_main, is_front_page, created_at, updated_at
             FROM {$CFG->dbprefix}pages
             WHERE context_id = :CID
             ORDER BY is_main DESC, is_front_page DESC, title ASC",
            array(':CID' => $context_id)
        );
    }

    /**
     * @return bool|null true deleted, false error, null not found
     */
    public static function delete($page_id, $context_id) {
        global $CFG, $PDOX;
        $check = $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        if ( ! $check ) {
            return null;
        }
        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}pages
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        return (bool) $q->success;
    }

    /**
     * @return bool
     */
    public static function togglePublished($page_id, $context_id) {
        global $CFG, $PDOX;
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}pages
             SET published = NOT published
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        return (bool) $q->success;
    }

    /**
     * @param int $page_id
     * @param int $context_id
     * @return array<string, mixed>|false
     */
    public static function loadForHistory($page_id, $context_id) {
        global $CFG, $PDOX;
        return $PDOX->rowDie(
            "SELECT page_id, title, body, logical_key, updated_at FROM {$CFG->dbprefix}pages
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listHistory($page_id) {
        global $CFG, $PDOX;
        return $PDOX->allRowsDie(
            "SELECT history_id, title, body, saved_at FROM {$CFG->dbprefix}page_history
             WHERE page_id = :PID ORDER BY saved_at DESC",
            array(':PID' => $page_id)
        );
    }

    /**
     * Restore a history row. Returns an error message or null on success.
     *
     * @param int $page_id
     * @param int $history_id
     * @param int $context_id
     * @param string $fileBaseUrl
     * @param string[] $localPrefixes
     * @return string|null
     */
    public static function restoreHistory($page_id, $history_id, $context_id, $fileBaseUrl, array $localPrefixes) {
        global $CFG, $PDOX;
        $page = $PDOX->rowDie(
            "SELECT page_id, title, body, logical_key FROM {$CFG->dbprefix}pages
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        $hist = $PDOX->rowDie(
            "SELECT history_id, title, body FROM {$CFG->dbprefix}page_history
             WHERE history_id = :HID AND page_id = :PID",
            array(':HID' => $history_id, ':PID' => $page_id)
        );
        if ( ! $page || ! $hist ) {
            return 'Page or history entry not found';
        }

        $logical_key = self::generateLogicalKey($hist['title']);
        $body = self::canonicalizeHtml($hist['body'], $fileBaseUrl, $localPrefixes, $context_id);

        $PDOX->beginTransaction();
        try {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}page_history (page_id, title, body) VALUES (:PID, :title, :body)",
                array(':PID' => $page_id, ':title' => $page['title'], ':body' => $page['body'])
            );
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET title = :title, logical_key = :key, body = :body, updated_at = NOW() WHERE page_id = :PID AND context_id = :CID",
                array(':title' => $hist['title'], ':key' => $logical_key, ':body' => $body, ':PID' => $page_id, ':CID' => $context_id)
            );
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}page_history WHERE history_id = :HID",
                array(':HID' => $history_id)
            );
            self::trimHistory($page_id);
            $PDOX->commit();
        } catch ( \Exception $e ) {
            $PDOX->rollBack();
            return 'Error restoring: ' . $e->getMessage();
        }
        return null;
    }

    private static function trimHistory($page_id) {
        global $CFG, $PDOX;
        $ids = $PDOX->allRowsDie(
            "SELECT history_id FROM {$CFG->dbprefix}page_history WHERE page_id = :PID ORDER BY saved_at DESC",
            array(':PID' => $page_id)
        );
        if ( count($ids) > 5 ) {
            foreach ( array_slice($ids, 5) as $row ) {
                $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}page_history WHERE history_id = :HID", array(':HID' => $row['history_id']));
            }
        }
    }

    /**
     * Insert a wiki/HTML page from a cartridge. Returns page_id and logical_key.
     *
     * @return array{page_id:int,logical_key:string,title:string}
     */
    public static function importHtml($title, $body, $logical_key, $context_id, $user_id) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        $title = is_string($title) && trim($title) !== '' ? trim($title) : 'Page';
        $body = is_string($body) ? $body : '';
        $logical_key = is_string($logical_key) ? trim($logical_key) : '';
        if ( $logical_key === '' ) {
            $logical_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? 'page');
            $logical_key = trim($logical_key, '-');
        }
        if ( $logical_key === '' ) {
            $logical_key = 'page';
        }
        if ( strlen($logical_key) > 99 ) {
            $logical_key = substr($logical_key, 0, 99);
        }
        $original = $logical_key;
        $counter = 2;
        while ( true ) {
            $existing = $PDOX->rowDie(
                "SELECT page_id FROM {$CFG->dbprefix}pages
                 WHERE context_id = :CID AND logical_key = :KEY",
                array(':CID' => $context_id, ':KEY' => $logical_key)
            );
            if ( ! $existing ) {
                break;
            }
            $suffix = '-'.$counter;
            $logical_key = substr($original, 0, 99 - strlen($suffix)).$suffix;
            $counter++;
        }

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}pages
                (context_id, title, logical_key, body, published, is_main, is_front_page, user_id, created_at, updated_at)
             VALUES
                (:CID, :title, :key, :body, 1, 0, 0, :UID, NOW(), NOW())",
            array(
                ':CID' => $context_id,
                ':title' => $title,
                ':key' => $logical_key,
                ':body' => $body,
                ':UID' => $user_id > 0 ? $user_id : 0,
            )
        );
        return array(
            'page_id' => (int) $PDOX->lastInsertId(),
            'logical_key' => $logical_key,
            'title' => $title,
        );
    }
}
