<?php

namespace Tsugi\Core;

use \Tsugi\Util\MCache;
use \Tsugi\Util\U;
use \Tsugi\UI\Lessons;

/**
 * Versioned course manifest, keyed by immutable manifest_id.
 *
 * File-based $CFG->lessons sites are unchanged: a context with no
 * manifest_id keeps using the file. New courses get a manifest row;
 * each save inserts a new row and points lti_context.manifest_id at it.
 *
 * The lessons JSON (column `manifest`) is the legacy pre-manifest document:
 * the same shape as lessons.json (modules, discussions, badges, …). Do not
 * grow that blob with new course-setup features. New Setup fields are
 * independent columns on this row (theme is the first: VARCHAR key, not
 * palette JSON). Navigation and later Setup work follow that pattern.
 *
 * The PHP session holds only the integer manifest_id. The immutable row
 * (lessons JSON plus sibling columns) is loaded on demand and cached in
 * Memcached (MCache) under a key derived from manifest_id, not context_id.
 * Tsugi\Core\Cache is session-based and must not be used for this document.
 * Older cache entries that stored a JSON string only are still accepted.
 *
 * Tools that auto-populate from lessons.json (store import, CC export,
 * peer-grade inherit, Autograder, Google Classroom, admin install repos)
 * stay on $CFG->lessons for now. Manifest-course authoring should use LTI
 * custom / resource-link settings instead; that wiring is deferred.
 *
 * Every new version is test-loaded with Lessons::tryFromJson() before insert.
 * Authoring can export/import a lessons.json file; import is the same save path.
 *
 * Outbound LTI launches from the lessons/discussions catalog (file vs manifest
 * resource_link_id, custom, launch URL) are a later pass. Do not mix those
 * behaviors in casually; parent-site launches and new-course launches will
 * need an explicit design.
 */
class Manifest {

    /** Memcached TTL in seconds; 0 means no expiry. Rows are immutable. */
    const CACHE_TTL = 86400;

    /** @var array<int, string> Request-local JSON strings keyed by manifest_id. */
    private static $requestJson = array();

    /** @var array<int, array<string, mixed>> Request-local manifest rows keyed by manifest_id. */
    private static $requestRows = array();

    /** @var MCache|object|null Injected or lazy MCache; false means "constructed empty". */
    private static $mcache = null;

    /**
     * Drop request-local memoization (tests / identity switch).
     */
    public static function resetRequestCache() {
        self::$requestJson = array();
        self::$requestRows = array();
    }

    /**
     * Replace the MCache used by loadJson (tests). Pass null to rebuild from $CFG.
     *
     * @param MCache|object|null $mcache
     */
    public static function setMCache($mcache) {
        self::$mcache = $mcache;
    }

    /**
     * Memcached key for an immutable manifest row.
     */
    public static function cacheKey($manifest_id) {
        global $CFG;
        $id = (int) $manifest_id;
        $prefix = 'tsugi';
        if ( is_object($CFG) && method_exists($CFG, 'serverPrefix') ) {
            $p = $CFG->serverPrefix();
            if ( is_string($p) && strlen($p) > 0 ) {
                $prefix = $p;
            }
        }
        return $prefix . ':manifest:' . $id;
    }

    /**
     * Minimal valid lessons.json-shaped document for a new course.
     *
     * @return array<string, mixed>
     */
    public static function starter($title) {
        $title = is_string($title) ? trim($title) : '';
        if ( $title === '' ) {
            $title = 'Untitled Course';
        }
        return array(
            'title' => $title,
            'description' => '',
            'count' => true,
            'required_modules' => array(),
            'discussions' => array(),
            'badges' => array(),
            'modules' => array(
                array(
                    'title' => 'Week 1',
                    'anchor' => 'week-1',
                    'description' => '',
                    'items' => array(),
                ),
            ),
        );
    }

    /**
     * Named course palettes (legacy $CFG->theme shape).
     *
     * Keys are stored in manifest.theme. NULL / empty means the site $CFG->theme.
     * Samples taken from peer tsugi_settings.php files (DJ4E, PG4E/CC4E, WS2),
     * PY4E-AI ($CFG->theme_base / --ai-accent), plus the Tsugi default primary
     * from Theme::defaults().
     *
     * @return array<string, array<string, string>>
     */
    public static function palettes() {
        return array(
            'tsugi' => array(
                'label' => 'Tsugi Blue',
                'primary' => '#0D47A1',
                'secondary' => '#EEEEEE',
                'text' => '#111111',
                'text-light' => '#5E5E5E',
                'font-family' => 'sans-serif',
                'font-size' => '14px',
            ),
            'django' => array(
                'label' => 'Django Green',
                'primary' => '#0a4b33',
                'secondary' => '#EEEEEE',
                'text' => '#111111',
                'text-light' => '#5E5E5E',
                'font-url' => 'https://fonts.googleapis.com/css?family=Roboto:400',
                'font-family' => "'Roboto', Corbel, Avenir, 'Lucida Grande', 'Lucida Sans', sans-serif",
                'font-size' => '14px',
            ),
            'postgres' => array(
                'label' => 'Postgres Blue',
                'primary' => '#336791',
                'secondary' => '#EEEEEE',
                'text' => '#111111',
                'text-light' => '#5E5E5E',
                'font-url' => 'https://fonts.googleapis.com/css2?family=Open+Sans',
                'font-family' => "'Open Sans', Corbel, Avenir, 'Lucida Grande', 'Lucida Sans', sans-serif",
                'font-size' => '14px',
            ),
            'navy' => array(
                'label' => 'Navy',
                'primary' => '#000060',
                'secondary' => '#EEEEEE',
                'text' => '#111111',
                'text-light' => '#5E5E5E',
                'font-url' => 'https://fonts.googleapis.com/css?family=Roboto:400',
                'font-family' => "'Roboto', Corbel, Avenir, 'Lucida Grande', 'Lucida Sans', sans-serif",
                'font-size' => '14px',
            ),
            'electric' => array(
                'label' => 'Electric',
                'primary' => '#7c3aed',
                'secondary' => '#EEEEEE',
                'text' => '#1e1b4b',
                'text-light' => '#4338ca',
                'font-family' => 'sans-serif',
                'font-size' => '14px',
            ),
            'grey' => array(
                'label' => 'Light Grey',
                'primary' => '#D0D0D0',
                'secondary' => '#111111',
                'text' => '#111111',
                'text-light' => '#5E5E5E',
                'font-family' => 'sans-serif',
                'font-size' => '14px',
            ),
        );
    }

    /**
     * Palette array for a theme key, without the display label. Null if unknown or site default.
     *
     * @return array<string, string>|null
     */
    public static function palette($key) {
        $norm = self::normalizeThemeKey($key);
        if ( ! is_string($norm) ) {
            return null;
        }
        $all = self::palettes();
        if ( ! isset($all[$norm]) || ! is_array($all[$norm]) ) {
            return null;
        }
        $p = $all[$norm];
        unset($p['label']);
        return $p;
    }

    /**
     * Validate a posted theme key.
     *
     * @return string|null|false Named key, null for site default, false if unknown.
     */
    public static function normalizeThemeKey($key) {
        if ( $key === null ) {
            return null;
        }
        if ( ! is_string($key) ) {
            return false;
        }
        $key = strtolower(trim($key));
        if ( $key === '' || $key === 'default' || $key === 'site' ) {
            return null;
        }
        $all = self::palettes();
        if ( ! isset($all[$key]) ) {
            return false;
        }
        return $key;
    }

    /**
     * Nav primary color for the site default (no named course theme).
     *
     * Matches Output::get_theme() when manifest.theme is NULL: theme_base,
     * then $CFG->theme primary, then the Tsugi default.
     */
    public static function siteDefaultPrimary() {
        global $CFG;
        if ( is_object($CFG) && isset($CFG->theme_base) && is_string($CFG->theme_base)
                && U::isValidCSSColor($CFG->theme_base) ) {
            return $CFG->theme_base;
        }
        if ( is_object($CFG) && isset($CFG->theme) && is_array($CFG->theme)
                && isset($CFG->theme['primary']) && is_string($CFG->theme['primary'])
                && U::isValidCSSColor($CFG->theme['primary']) ) {
            return $CFG->theme['primary'];
        }
        return '#0D47A1';
    }

    /**
     * Theme key stored on the active manifest row (empty string = site default).
     */
    public static function currentThemeKey() {
        $id = self::activeId();
        if ( $id < 1 ) {
            return '';
        }
        $row = self::loadRow($id);
        if ( ! is_array($row) ) {
            return '';
        }
        $key = $row['theme'] ?? '';
        return is_string($key) ? $key : '';
    }

    /**
     * Raw palette for the active named theme, or null to keep $CFG->theme.
     *
     * @return array<string, string>|null
     */
    public static function currentThemeArray() {
        $key = self::currentThemeKey();
        if ( $key === '' ) {
            return null;
        }
        return self::palette($key);
    }

    /**
     * Theme key currently pointed at by a context, or null.
     *
     * @return string|null
     */
    public static function themeKeyForContext($context_id) {
        global $CFG;
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT m.theme AS theme
             FROM {$p}lti_context c
             LEFT JOIN {$p}manifest m ON m.manifest_id = c.manifest_id
             WHERE c.context_id = :CID",
            array(':CID' => $cid)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $key = $row['theme'] ?? null;
        if ( ! is_string($key) || $key === '' ) {
            return null;
        }
        $norm = self::normalizeThemeKey($key);
        return is_string($norm) ? $norm : null;
    }

    /**
     * Encode manifest JSON the same way file authoring does.
     *
     * @param array<string, mixed>|object $data
     */
    public static function encode($data) {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Test-load a JSON string as Lessons. Returns an error message, or null if valid.
     *
     * @return string|null
     */
    public static function validateJson($json) {
        if ( ! is_string($json) || trim($json) === '' ) {
            return 'Document is empty';
        }
        $loaded = Lessons::tryFromJson($json);
        if ( $loaded instanceof Lessons ) {
            return null;
        }
        if ( is_string($loaded) && strlen($loaded) > 0 ) {
            return $loaded;
        }
        return 'Invalid lessons document';
    }

    /**
     * Download filename for an exported lessons.json.
     *
     * Includes V{n} when a manifest version is known, and the export date.
     */
    public static function exportFilename($title, $version = 0, $date = null) {
        $title = is_string($title) ? $title : '';
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $title);
        $slug = trim($slug, '-');
        $version = (int) $version;
        if ( ! is_string($date) || $date === '' ) {
            $date = date('Y-m-d');
        }
        $date = preg_replace('/[^0-9-]/', '', $date);
        $parts = array();
        if ( $slug !== '' ) {
            $parts[] = $slug;
        } else {
            $parts[] = 'lessons';
        }
        if ( $version > 0 ) {
            $parts[] = 'V' . $version;
        }
        if ( $date !== '' ) {
            $parts[] = $date;
        }
        if ( $slug !== '' ) {
            return implode('-', $parts) . '-lessons.json';
        }
        return implode('-', $parts) . '.json';
    }

    /**
     * Append a course-level discussion to a decoded lessons document.
     *
     * @param array<string, mixed> $data
     * @return array{data: array<string, mixed>, resource_link_id: string}
     */
    public static function appendDiscussion($data, $title, $launch = 'mod/tdiscus/') {
        if ( ! is_array($data) ) {
            throw new \InvalidArgumentException('Document must be an array');
        }
        $title = is_string($title) ? trim($title) : '';
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Title is required');
        }
        $launch = is_string($launch) && trim($launch) !== '' ? trim($launch) : 'mod/tdiscus/';
        if ( ! isset($data['discussions']) || ! is_array($data['discussions']) ) {
            $data['discussions'] = array();
        }
        $used = self::collectResourceLinkIds($data);
        $base = self::discussionRlidBase($title);
        $rlid = $base;
        $n = 2;
        while ( isset($used[$rlid]) ) {
            $rlid = $base . '_' . $n;
            $n++;
            if ( $n > 50 ) {
                $rlid = $base . '_' . bin2hex(random_bytes(3));
                break;
            }
        }
        $data['discussions'][] = array(
            'title' => $title,
            'launch' => $launch,
            'resource_link_id' => $rlid,
        );
        if ( isset($data['discussion_order']) && is_array($data['discussion_order']) ) {
            $data['discussion_order'][] = $rlid;
        }
        return array('data' => $data, 'resource_link_id' => $rlid);
    }

    /**
     * Reorder course discussions to match a list of resource_link_id values.
     *
     * Sets discussion_order (catalog display) and permutes the top-level
     * discussions array. Unknown ids are ignored; discussions not in the
     * list keep their relative order at the end.
     *
     * @param array<string, mixed> $data
     * @param array<int, mixed> $ordered_rlids
     * @return array<string, mixed>
     */
    public static function reorderDiscussions($data, $ordered_rlids) {
        if ( ! is_array($data) ) {
            throw new \InvalidArgumentException('Document must be an array');
        }
        if ( ! is_array($ordered_rlids) ) {
            throw new \InvalidArgumentException('Order is required');
        }
        $seen = array();
        $order = array();
        foreach ( $ordered_rlids as $id ) {
            if ( is_int($id) || is_float($id) ) {
                $id = (string) $id;
            }
            if ( ! is_string($id) ) {
                continue;
            }
            $id = trim($id);
            if ( $id === '' || isset($seen[$id]) ) {
                continue;
            }
            $seen[$id] = true;
            $order[] = $id;
        }
        if ( count($order) < 1 ) {
            throw new \InvalidArgumentException('Order is required');
        }
        $data['discussion_order'] = $order;

        if ( isset($data['discussions']) && is_array($data['discussions']) ) {
            $by_id = array();
            $no_id = array();
            foreach ( $data['discussions'] as $d ) {
                if ( ! is_array($d) ) {
                    continue;
                }
                $rid = isset($d['resource_link_id']) && is_string($d['resource_link_id'])
                    ? $d['resource_link_id'] : '';
                if ( $rid === '' ) {
                    $no_id[] = $d;
                    continue;
                }
                if ( ! isset($by_id[$rid]) ) {
                    $by_id[$rid] = $d;
                }
            }
            $new = array();
            foreach ( $order as $rid ) {
                if ( isset($by_id[$rid]) ) {
                    $new[] = $by_id[$rid];
                    unset($by_id[$rid]);
                }
            }
            foreach ( $by_id as $d ) {
                $new[] = $d;
            }
            foreach ( $no_id as $d ) {
                $new[] = $d;
            }
            $data['discussions'] = $new;
        }
        return $data;
    }

    /**
     * resource_link_id values already used in a decoded lessons document.
     *
     * @param array<string, mixed> $data
     * @return array<string, true>
     */
    public static function collectResourceLinkIds($data) {
        $ids = array();
        if ( ! is_array($data) ) {
            return $ids;
        }
        self::collectRlidsFromList(isset($data['discussions']) ? $data['discussions'] : null, $ids);
        self::collectRlidsFromList(isset($data['launches']) ? $data['launches'] : null, $ids);
        if ( isset($data['modules']) && is_array($data['modules']) ) {
            foreach ( $data['modules'] as $mod ) {
                if ( ! is_array($mod) ) {
                    continue;
                }
                self::collectRlidsFromList(isset($mod['lti']) ? $mod['lti'] : null, $ids);
                self::collectRlidsFromList(isset($mod['discussions']) ? $mod['discussions'] : null, $ids);
                self::collectRlidsFromList(isset($mod['items']) ? $mod['items'] : null, $ids);
            }
        }
        return $ids;
    }

    /**
     * @param mixed $list
     * @param array<string, true> $ids
     */
    private static function collectRlidsFromList($list, &$ids) {
        if ( ! is_array($list) ) {
            return;
        }
        foreach ( $list as $item ) {
            if ( is_array($item) && isset($item['resource_link_id'])
                    && is_string($item['resource_link_id']) && $item['resource_link_id'] !== '' ) {
                $ids[$item['resource_link_id']] = true;
            }
        }
    }

    private static function discussionRlidBase($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        if ( $slug === '' ) {
            $slug = 'topic';
        }
        if ( strlen($slug) > 40 ) {
            $slug = substr($slug, 0, 40);
            $slug = rtrim($slug, '_');
        }
        return 'discussion_' . $slug;
    }

    /**
     * Active manifest_id for this request (session only; 0 if file-backed or none).
     */
    public static function activeId() {
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ) {
            $id = self::positiveId($_SESSION[$ltiKey]['manifest_id'] ?? 0);
            if ( $id > 0 ) {
                return $id;
            }
        }
        return self::positiveId($_SESSION['manifest_id'] ?? 0);
    }

    /**
     * Copy manifest_id into the session LTI blob (integer only).
     */
    public static function rememberInSession($manifest_id) {
        $id = (int) $manifest_id;
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( $id < 1 ) {
            unset($_SESSION['manifest_id']);
            if ( isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ) {
                unset($_SESSION[$ltiKey]['manifest_id']);
            }
            return;
        }
        $_SESSION['manifest_id'] = $id;
        if ( ! isset($_SESSION[$ltiKey]) || ! is_array($_SESSION[$ltiKey]) ) {
            $_SESSION[$ltiKey] = array();
        }
        $_SESSION[$ltiKey]['manifest_id'] = $id;
    }

    /**
     * JSON string for a manifest_id, or false if missing.
     *
     * Lookup: request memo, then MCache, then database. Does not parse into Lessons.
     *
     * @return string|false
     */
    public static function loadJson($manifest_id) {
        $id = (int) $manifest_id;
        if ( $id < 1 ) {
            return false;
        }
        if ( isset(self::$requestJson[$id]) && is_string(self::$requestJson[$id]) ) {
            return self::$requestJson[$id];
        }
        if ( isset(self::$requestRows[$id]) ) {
            $json = self::$requestRows[$id]['manifest'] ?? '';
            if ( is_string($json) && $json !== '' ) {
                self::$requestJson[$id] = $json;
                return $json;
            }
        }

        $cache = self::mcache();
        if ( $cache && method_exists($cache, 'isEnabled') && $cache->isEnabled()
                && method_exists($cache, 'get') ) {
            $cached = $cache->get(self::cacheKey($id));
            $row = self::rowFromCacheValue($cached);
            if ( is_array($row) ) {
                if ( ! isset($row['manifest_id']) ) {
                    $row['manifest_id'] = $id;
                }
                self::rememberRequest($id, $row);
                return $row['manifest'];
            }
            if ( is_string($cached) && strlen($cached) > 0 ) {
                self::$requestJson[$id] = $cached;
                return $cached;
            }
        }

        return self::loadJsonFromDatabase($id);
    }

    /**
     * True if this request has a course document (active manifest or readable $CFG->lessons).
     */
    public static function hasCurrent() {
        if ( self::activeId() > 0 ) {
            return true;
        }
        global $CFG;
        return isset($CFG->lessons) && is_string($CFG->lessons) && strlen($CFG->lessons) > 0
            && is_readable($CFG->lessons);
    }

    /**
     * Like {@see currentLessons()} but dies if neither a manifest nor a file is available.
     *
     * @return Lessons
     */
    public static function requireCurrentLessons($anchor = null) {
        $l = self::currentLessons($anchor);
        if ( ! $l ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons) or an active course manifest');
        }
        return $l;
    }

    /**
     * Lessons for the current context: manifest JSON if active, else $CFG->lessons file.
     *
     * @return Lessons|false
     */
    public static function currentLessons($anchor = null) {
        $id = self::activeId();
        if ( $id > 0 ) {
            $json = self::loadJson($id);
            if ( is_string($json) ) {
                return Lessons::fromJson($json, $anchor);
            }
            return false;
        }
        global $CFG;
        if ( isset($CFG->lessons) && is_string($CFG->lessons) && strlen($CFG->lessons) > 0
                && is_readable($CFG->lessons) ) {
            return new Lessons($CFG->lessons, $anchor);
        }
        return false;
    }

    /**
     * Raw JSON for authoring: manifest row or $CFG->lessons file.
     *
     * @return array{json: string, label: string, manifest_id: int, version: int}|false
     */
    public static function currentDocument() {
        $id = self::activeId();
        if ( $id > 0 ) {
            $json = self::loadJson($id);
            if ( ! is_string($json) ) {
                return false;
            }
            $row = self::loadRow($id);
            $ver = is_array($row) && isset($row['version']) ? (int) $row['version'] : 0;
            $label = 'manifest #' . $id;
            if ( $ver > 0 ) {
                $label .= ' (v' . $ver . ')';
            }
            return array('json' => $json, 'label' => $label, 'manifest_id' => $id, 'version' => $ver);
        }
        global $CFG;
        if ( isset($CFG->lessons) && is_string($CFG->lessons) && is_readable($CFG->lessons) ) {
            $json = file_get_contents($CFG->lessons);
            if ( $json === false ) {
                return false;
            }
            return array('json' => $json, 'label' => $CFG->lessons, 'manifest_id' => 0, 'version' => 0);
        }
        return false;
    }

    /**
     * Insert an immutable new version and point lti_context.manifest_id at it.
     *
     * @param array<string, mixed>|object|string $data Decoded document or JSON string
     * @param string|null $theme Theme key. Null copies the previous version for this
     *        context. Empty string stores NULL (site $CFG->theme).
     * @return int New manifest_id
     */
    public static function saveNewVersion($context_id, $data, $user_id = null, $comment = null, $theme = null) {
        global $CFG;
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            throw new \InvalidArgumentException('saveNewVersion requires a context_id');
        }
        if ( is_string($data) ) {
            $decoded = json_decode($data, true);
            if ( ! is_array($decoded) ) {
                throw new \InvalidArgumentException('saveNewVersion: invalid JSON');
            }
            $data = $decoded;
        } elseif ( is_object($data) ) {
            $data = json_decode(json_encode($data), true);
            if ( ! is_array($data) ) {
                throw new \InvalidArgumentException('saveNewVersion: invalid document');
            }
        }
        if ( ! is_array($data) ) {
            throw new \InvalidArgumentException('saveNewVersion: expected array or JSON');
        }

        $json = self::encode($data);
        if ( ! is_string($json) ) {
            throw new \InvalidArgumentException('saveNewVersion: JSON encode failed');
        }
        $loadError = self::validateJson($json);
        if ( $loadError !== null ) {
            throw new \InvalidArgumentException($loadError);
        }
        $title = isset($data['title']) && is_string($data['title']) ? $data['title'] : null;
        $uid = $user_id === null ? null : (int) $user_id;
        if ( $uid !== null && $uid < 1 ) {
            $uid = null;
        }
        $comment = is_string($comment) && strlen(trim($comment)) > 0 ? trim($comment) : null;

        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;

        if ( $theme === null ) {
            $themeToStore = self::themeKeyForContext($cid);
        } else {
            $norm = self::normalizeThemeKey($theme);
            if ( $norm === false ) {
                throw new \InvalidArgumentException('Unknown theme');
            }
            $themeToStore = $norm;
        }

        $next = $PDOX->rowDie(
            "SELECT COALESCE(MAX(version), 0) + 1 AS next_version
             FROM {$p}manifest WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        $version = is_array($next) ? (int) $next['next_version'] : 1;
        if ( $version < 1 ) {
            $version = 1;
        }

        $PDOX->queryDie(
            "INSERT INTO {$p}manifest
                (context_id, version, title, theme, manifest, comment, user_id, created_at)
             VALUES
                (:context_id, :version, :title, :theme, :manifest, :comment, :user_id, NOW())",
            array(
                ':context_id' => $cid,
                ':version' => $version,
                ':title' => $title,
                ':theme' => $themeToStore,
                ':manifest' => $json,
                ':comment' => $comment,
                ':user_id' => $uid,
            )
        );
        $manifest_id = (int) $PDOX->lastInsertId();
        self::setActive($cid, $manifest_id);
        self::rememberCachedRow(array(
            'manifest_id' => $manifest_id,
            'context_id' => $cid,
            'version' => $version,
            'title' => $title,
            'theme' => $themeToStore,
            'manifest' => $json,
            'comment' => $comment,
            'user_id' => $uid,
        ));
        return $manifest_id;
    }

    /**
     * Point a context at an existing manifest row (must belong to that context).
     */
    public static function setActive($context_id, $manifest_id) {
        global $CFG;
        $cid = (int) $context_id;
        $mid = (int) $manifest_id;
        if ( $cid < 1 || $mid < 1 ) {
            throw new \InvalidArgumentException('setActive requires context_id and manifest_id');
        }
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT manifest_id FROM {$p}manifest
             WHERE manifest_id = :MID AND context_id = :CID",
            array(':MID' => $mid, ':CID' => $cid)
        );
        if ( ! $row ) {
            throw new \InvalidArgumentException('manifest does not belong to this context');
        }
        $PDOX->queryDie(
            "UPDATE {$p}lti_context SET manifest_id = :MID, updated_at = NOW()
             WHERE context_id = :CID",
            array(':MID' => $mid, ':CID' => $cid)
        );
        if ( U::currentContextId() === $cid ) {
            self::rememberInSession($mid);
        }
    }

    /**
     * Create an LTI context, instructor membership, and starter manifest version 1.
     *
     * @return array{ok: bool, context_id?: int, manifest_id?: int, error?: string}
     */
    public static function createCourse($title, $user_id, $key_id) {
        global $CFG;
        $title = is_string($title) ? trim($title) : '';
        if ( $title === '' ) {
            return array('ok' => false, 'error' => 'Title is required.');
        }
        $user_id = (int) $user_id;
        $key_id = (int) $key_id;
        if ( $user_id < 1 ) {
            return array('ok' => false, 'error' => 'Must be logged in.');
        }
        if ( $key_id < 1 ) {
            return array('ok' => false, 'error' => 'Missing consumer key.');
        }

        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $context_key = 'course:' . bin2hex(random_bytes(16));

        $PDOX->queryDie(
            "INSERT INTO {$p}lti_context
                (context_key, context_sha256, title, key_id, user_id, created_at, updated_at)
             VALUES
                (:context_key, :context_sha256, :title, :key_id, :user_id, NOW(), NOW())",
            array(
                ':context_key' => $context_key,
                ':context_sha256' => lti_sha256($context_key),
                ':title' => $title,
                ':key_id' => $key_id,
                ':user_id' => $user_id,
            )
        );
        $context_id = (int) $PDOX->lastInsertId();
        if ( $context_id < 1 ) {
            return array('ok' => false, 'error' => 'Could not create course.');
        }

        $PDOX->queryDie(
            "INSERT INTO {$p}lti_membership
                (context_id, user_id, role, created_at, updated_at)
             VALUES
                (:context_id, :user_id, :role, NOW(), NOW())",
            array(
                ':context_id' => $context_id,
                ':user_id' => $user_id,
                ':role' => LTIX::ROLE_INSTRUCTOR,
            )
        );

        $manifest_id = self::saveNewVersion(
            $context_id,
            self::starter($title),
            $user_id,
            'Created course'
        );
        return array(
            'ok' => true,
            'context_id' => $context_id,
            'manifest_id' => $manifest_id,
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    public static function loadRow($manifest_id) {
        global $CFG;
        $id = (int) $manifest_id;
        if ( $id < 1 ) {
            return false;
        }
        if ( isset(self::$requestRows[$id]) ) {
            return self::$requestRows[$id];
        }

        $cache = self::mcache();
        if ( $cache && method_exists($cache, 'isEnabled') && $cache->isEnabled()
                && method_exists($cache, 'get') ) {
            $cached = $cache->get(self::cacheKey($id));
            $row = self::rowFromCacheValue($cached);
            if ( is_array($row) ) {
                if ( ! isset($row['manifest_id']) ) {
                    $row['manifest_id'] = $id;
                }
                self::rememberRequest($id, $row);
                return $row;
            }
        }

        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT manifest_id, context_id, version, title, theme, manifest, comment, user_id, created_at
             FROM {$p}manifest WHERE manifest_id = :MID",
            array(':MID' => $id)
        );
        if ( ! is_array($row) ) {
            return false;
        }
        self::rememberCachedRow($row);
        return $row;
    }

    /**
     * Cached MCache payload is a row array; older entries were a JSON string.
     *
     * @param mixed $cached
     * @return array<string, mixed>|false
     */
    private static function rowFromCacheValue($cached) {
        if ( ! is_array($cached) ) {
            return false;
        }
        $json = $cached['manifest'] ?? '';
        if ( ! is_string($json) || $json === '' ) {
            return false;
        }
        return $cached;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function rememberRequest($id, $row) {
        self::$requestRows[$id] = $row;
        $json = $row['manifest'] ?? '';
        if ( is_string($json) && $json !== '' ) {
            self::$requestJson[$id] = $json;
        }
    }

    /**
     * Request memo plus MCache. Payload is the immutable row (JSON + theme).
     *
     * @param array<string, mixed> $row
     */
    private static function rememberCachedRow($row) {
        $id = (int) ($row['manifest_id'] ?? 0);
        if ( $id < 1 ) {
            return;
        }
        self::rememberRequest($id, $row);
        $cache = self::mcache();
        if ( $cache && method_exists($cache, 'isEnabled') && $cache->isEnabled()
                && method_exists($cache, 'set') ) {
            $cache->set(self::cacheKey($id), $row, self::CACHE_TTL);
        }
    }

    /**
     * @return string|false
     */
    private static function loadJsonFromDatabase($manifest_id) {
        $row = self::loadRow($manifest_id);
        if ( ! is_array($row) ) {
            return false;
        }
        $json = $row['manifest'] ?? '';
        return is_string($json) && strlen($json) > 0 ? $json : false;
    }

    /**
     * @return int
     */
    private static function positiveId($value) {
        if ( function_exists('_tsugiNormalizePositiveId') ) {
            return _tsugiNormalizePositiveId($value);
        }
        if ( $value === null || $value === false || $value === '' || is_bool($value) ) {
            return 0;
        }
        if ( ! is_numeric($value) ) {
            return 0;
        }
        $intval = (int) $value;
        return $intval > 0 ? $intval : 0;
    }

    /**
     * @return MCache|object|null
     */
    private static function mcache() {
        global $CFG;
        if ( self::$mcache !== null ) {
            return self::$mcache;
        }
        if ( ! is_object($CFG) ) {
            return null;
        }
        self::$mcache = new MCache($CFG);
        return self::$mcache;
    }
}
