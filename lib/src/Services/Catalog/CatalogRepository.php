<?php

namespace Tsugi\Services\Catalog;

use Tsugi\Controllers\Courses;
use Tsugi\Core\ContextImages;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Site-wide course_catalog rows. List queries never SELECT hero BLOBs.
 */
class CatalogRepository {

    const TITLE_MAX = 512;
    const SHORT_MAX = 512;

    /** @var \HTMLPurifier|null */
    private static $purifier = null;

    /**
     * @param array<string, mixed> $post
     * @return array{ok:true,data:array<string,mixed>}|array{ok:false,error:string}
     */
    public static function normalizeInput(array $post, $homeContextId = 0) {
        $title = trim((string) U::get($post, 'title', ''));
        if ( $title === '' ) {
            return array('ok' => false, 'error' => 'Title is required.');
        }
        if ( mb_strlen($title, 'UTF-8') > self::TITLE_MAX ) {
            $title = mb_substr($title, 0, self::TITLE_MAX, 'UTF-8');
        }

        $kind = (string) U::get($post, 'kind', '');
        if ( $kind !== 'course' && $kind !== 'link' ) {
            $has_url = trim((string) U::get($post, 'external_url', '')) !== '';
            $kind = $has_url ? 'link' : 'course';
        }

        $context_id = 0;
        $external_url = null;
        $new_window = 0;
        if ( $kind === 'link' ) {
            $url = trim((string) U::get($post, 'external_url', ''));
            if ( $url === '' ) {
                return array('ok' => false, 'error' => 'A link listing needs a URL.');
            }
            if ( ! self::validHttpUrl($url) ) {
                return array('ok' => false, 'error' => 'URL must start with http:// or https://.');
            }
            $external_url = $url;
            $new_window = empty($post['new_window']) ? 0 : 1;
        } else {
            $context_id = (int) U::get($post, 'context_id', 0);
            if ( $context_id < 1 ) {
                return array('ok' => false, 'error' => 'Select a course, or switch to a link listing.');
            }
            $home = (int) $homeContextId;
            if ( $home > 0 && $context_id === $home ) {
                return array('ok' => false, 'error' => 'The site home course cannot be an enrollable catalog entry. List it as a link to the site home instead.');
            }
        }

        $short = self::plainText((string) U::get($post, 'short_description', ''), self::SHORT_MAX);
        $description = self::purify((string) U::get($post, 'description', ''));
        $published = empty($post['published']) ? 0 : 1;
        $sort_order = (int) U::get($post, 'sort_order', 0);

        return array(
            'ok' => true,
            'data' => array(
                'context_id' => $context_id > 0 ? $context_id : null,
                'external_url' => $external_url,
                'title' => $title,
                'short_description' => $short === '' ? null : $short,
                'description' => $description === '' ? null : $description,
                'published' => $published,
                'sort_order' => $sort_order,
                'new_window' => $new_window,
            ),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listAdmin() {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT CAT.catalog_id, CAT.context_id, CAT.external_url, CAT.title,
                    CAT.short_description, CAT.published, CAT.sort_order, CAT.new_window,
                    CAT.hero_bytes, CAT.hero_updated_at, CAT.updated_at, CAT.created_at,
                    COALESCE(NULLIF(MF.title, ''), C.title) AS context_title
             FROM {$p}course_catalog AS CAT
             LEFT JOIN {$p}lti_context AS C ON CAT.context_id = C.context_id
             LEFT JOIN {$p}manifest AS MF ON C.manifest_id = MF.manifest_id
             ORDER BY CAT.sort_order ASC, CAT.title ASC, CAT.catalog_id ASC"
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Published listings for the public catalog. No BLOBs. Description is
     * used only to set has_detail, then dropped.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listPublished($user_id = 0) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $uid = (int) $user_id;
        $memberJoin = '';
        $memberSelect = 'NULL AS membership_id';
        $params = array();
        if ( $uid > 0 ) {
            $memberSelect = 'M.membership_id';
            $memberJoin = "LEFT JOIN {$p}lti_membership AS M
                ON M.context_id = CAT.context_id AND M.user_id = :UID";
            $params[':UID'] = $uid;
        }
        $rows = $PDOX->allRowsDie(
            "SELECT CAT.catalog_id, CAT.context_id, CAT.external_url, CAT.title,
                    CAT.short_description, CAT.description, CAT.published, CAT.sort_order, CAT.new_window,
                    CAT.hero_bytes, CAT.hero_updated_at,
                    CI.hero_bytes AS context_hero_bytes, CI.hero_updated_at AS context_hero_updated_at,
                    CI.icon_bytes, CI.icon_updated_at,
                    {$memberSelect}
             FROM {$p}course_catalog AS CAT
             LEFT JOIN {$p}context_images AS CI ON CI.context_id = CAT.context_id
             {$memberJoin}
             WHERE CAT.published = 1
             ORDER BY CAT.sort_order ASC, CAT.title ASC, CAT.catalog_id ASC",
            $params
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $out = self::withDisplayUrls($rows);
        foreach ( $out as $i => $row ) {
            unset($out[$i]['description']);
        }
        return $out;
    }

    /**
     * One row without the hero BLOB. $publishedOnly skips unpublished.
     *
     * @return array<string, mixed>|null
     */
    public static function load($catalog_id, $publishedOnly = false, $user_id = 0) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return null;
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $uid = (int) $user_id;
        $memberJoin = '';
        $memberSelect = 'NULL AS membership_id';
        $params = array(':ID' => $id);
        if ( $uid > 0 ) {
            $memberSelect = 'M.membership_id';
            $memberJoin = "LEFT JOIN {$p}lti_membership AS M
                ON M.context_id = CAT.context_id AND M.user_id = :UID";
            $params[':UID'] = $uid;
        }
        $pub = $publishedOnly ? ' AND CAT.published = 1' : '';
        $row = $PDOX->rowDie(
            "SELECT CAT.catalog_id, CAT.context_id, CAT.external_url, CAT.title,
                    CAT.short_description, CAT.description, CAT.published, CAT.sort_order,
                    CAT.new_window, CAT.hero_bytes, CAT.hero_updated_at, CAT.user_id,
                    CAT.created_at, CAT.updated_at,
                    CI.hero_bytes AS context_hero_bytes, CI.hero_updated_at AS context_hero_updated_at,
                    CI.icon_bytes, CI.icon_updated_at,
                    {$memberSelect}
             FROM {$p}course_catalog AS CAT
             LEFT JOIN {$p}context_images AS CI ON CI.context_id = CAT.context_id
             {$memberJoin}
             WHERE CAT.catalog_id = :ID{$pub}",
            $params
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $out = self::withDisplayUrls(array($row));
        return $out[0] ?? null;
    }

    /**
     * Catalog hero, or the linked course hero. Never both in one SELECT.
     *
     * @return array{bytes:string,mime:string,updated_at:?string}|null
     */
    public static function heroBlob($catalog_id) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return null;
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT hero AS content, hero_mime AS mime, hero_bytes AS nbytes,
                    hero_updated_at AS updated_at, context_id
             FROM {$p}course_catalog WHERE catalog_id = :ID",
            array(':ID' => $id)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $bytes = $row['content'] ?? null;
        if ( is_string($bytes) && $bytes !== '' ) {
            return array(
                'bytes' => $bytes,
                'mime' => (isset($row['mime']) && is_string($row['mime']) && $row['mime'] !== '')
                    ? $row['mime'] : ContextImages::MIME,
                'updated_at' => $row['updated_at'] ?? null,
            );
        }
        $cid = isset($row['context_id']) ? (int) $row['context_id'] : 0;
        if ( $cid < 1 ) {
            return null;
        }
        return ContextImages::blob($cid, ContextImages::KIND_HERO);
    }

    /**
     * Square icon from the linked course, if any.
     *
     * @return array{bytes:string,mime:string,updated_at:?string}|null
     */
    public static function iconBlob($catalog_id) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return null;
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT context_id FROM {$p}course_catalog WHERE catalog_id = :ID",
            array(':ID' => $id)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $cid = isset($row['context_id']) ? (int) $row['context_id'] : 0;
        if ( $cid < 1 ) {
            return null;
        }
        return ContextImages::blob($cid, ContextImages::KIND_ICON);
    }

    /**
     * @param array<string, mixed> $data From normalizeInput()
     * @return array{ok:true,catalog_id:int}|array{ok:false,error:string}
     */
    public static function save($catalog_id, array $data, $user_id = 0) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $id = (int) $catalog_id;
        $uid = (int) $user_id;
        $params = array(
            ':title' => $data['title'],
            ':short' => $data['short_description'],
            ':body' => $data['description'],
            ':published' => (int) $data['published'],
            ':sort' => (int) $data['sort_order'],
            ':newwin' => (int) $data['new_window'],
            ':url' => $data['external_url'],
            ':cid' => $data['context_id'],
            ':uid' => $uid > 0 ? $uid : null,
        );

        if ( $id > 0 ) {
            $params[':ID'] = $id;
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$p}course_catalog
                 SET context_id = :cid, external_url = :url, title = :title,
                     short_description = :short, description = :body,
                     published = :published, sort_order = :sort, new_window = :newwin,
                     user_id = :uid, updated_at = NOW()
                 WHERE catalog_id = :ID",
                $params
            );
            if ( ! is_object($stmt) || ! $stmt->success ) {
                return self::saveError($stmt);
            }
            return array('ok' => true, 'catalog_id' => $id);
        }
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}course_catalog
                (context_id, external_url, title, short_description, description,
                 published, sort_order, new_window, user_id, created_at, updated_at)
             VALUES
                (:cid, :url, :title, :short, :body,
                 :published, :sort, :newwin, :uid, NOW(), NOW())",
            $params
        );
        if ( ! is_object($stmt) || ! $stmt->success ) {
            return self::saveError($stmt);
        }
        $newId = (int) $PDOX->lastInsertId();
        if ( $newId < 1 ) {
            return array('ok' => false, 'error' => 'Could not create catalog entry.');
        }
        return array('ok' => true, 'catalog_id' => $newId);
    }

    /**
     * @return string|null Error
     */
    public static function saveHero($catalog_id, $bytes) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return 'Invalid catalog entry.';
        }
        if ( ! is_string($bytes) || $bytes === '' ) {
            return 'Image data was empty.';
        }
        $spec = ContextImages::spec(ContextImages::KIND_HERO);
        if ( $spec === null || strlen($bytes) > (int) $spec['max_bytes'] ) {
            return 'Image is too large after conversion.';
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $len = strlen($bytes);
        $sql = "UPDATE {$p}course_catalog
            SET hero = :BLOB, hero_mime = :MIME, hero_bytes = :LEN,
                hero_updated_at = NOW(), updated_at = NOW()
            WHERE catalog_id = :ID";
        $stmt = $PDOX->prepare($sql);
        $stmt->bindValue(':ID', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':MIME', ContextImages::MIME, \PDO::PARAM_STR);
        $stmt->bindValue(':LEN', $len, \PDO::PARAM_INT);
        $fp = fopen('php://temp', 'r+');
        if ( $fp === false ) {
            return 'Could not buffer the image.';
        }
        fwrite($fp, $bytes);
        rewind($fp);
        $stmt->bindParam(':BLOB', $fp, \PDO::PARAM_LOB);
        $ok = $stmt->execute();
        fclose($fp);
        if ( ! $ok ) {
            return 'Failed to save the image.';
        }
        return null;
    }

    /**
     * @return string|null Error
     */
    public static function clearHero($catalog_id) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return 'Invalid catalog entry.';
        }
        LTIX::getConnection();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}course_catalog
             SET hero = NULL, hero_mime = NULL, hero_bytes = NULL,
                 hero_updated_at = NULL, updated_at = NOW()
             WHERE catalog_id = :ID",
            array(':ID' => $id)
        );
        return null;
    }

    public static function delete($catalog_id) {
        global $CFG, $PDOX;

        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return false;
        }
        LTIX::getConnection();
        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}course_catalog WHERE catalog_id = :ID",
            array(':ID' => $id)
        );
        return true;
    }

    public static function isMember($context_id, $user_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        $uid = (int) $user_id;
        if ( $cid < 1 || $uid < 1 ) {
            return false;
        }
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT membership_id FROM {$CFG->dbprefix}lti_membership
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $cid, ':UID' => $uid)
        );
        return is_array($row) && isset($row['membership_id']);
    }

    /**
     * Insert learner membership when missing. True when the user is a member.
     */
    public static function enrollLearner($context_id, $user_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        $uid = (int) $user_id;
        if ( $cid < 1 || $uid < 1 ) {
            return false;
        }
        if ( self::isMember($cid, $uid) ) {
            return true;
        }
        LTIX::getConnection();
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_membership
                (context_id, user_id, role, created_at, updated_at)
             VALUES
                (:CID, :UID, :ROLE, NOW(), NOW())",
            array(
                ':CID' => $cid,
                ':UID' => $uid,
                ':ROLE' => LTIX::ROLE_LEARNER,
            )
        );
        if ( is_object($stmt) && $stmt->success ) {
            return true;
        }
        return self::isMember($cid, $uid);
    }

    /**
     * @param mixed $stmt
     * @return array{ok:false,error:string}
     */
    private static function saveError($stmt) {
        $msg = is_object($stmt) && isset($stmt->errorImplode) ? (string) $stmt->errorImplode : '';
        if ( stripos($msg, 'Duplicate') !== false || stripos($msg, 'unique') !== false ) {
            return array('ok' => false, 'error' => 'That course is already in the catalog.');
        }
        if ( stripos($msg, 'foreign key') !== false ) {
            return array('ok' => false, 'error' => 'That course was not found.');
        }
        return array('ok' => false, 'error' => 'Could not save catalog entry.');
    }

    /**
     * Courses an admin can attach, excluding the Google home context and
     * courses already listed (except $keepCatalogId's current context).
     *
     * @return array<int, array{context_id:int,title:string}>
     */
    public static function contextChoices($homeContextId = 0, $keepCatalogId = 0) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $params = array();
        $extra = '';
        $home = (int) $homeContextId;
        if ( $home > 0 ) {
            $extra .= ' AND C.context_id != :HOME';
            $params[':HOME'] = $home;
        }
        $keep = (int) $keepCatalogId;
        if ( $keep > 0 ) {
            $extra .= ' AND (CAT.catalog_id IS NULL OR CAT.catalog_id = :KEEP)';
            $params[':KEEP'] = $keep;
        } else {
            $extra .= ' AND CAT.catalog_id IS NULL';
        }
        $rows = $PDOX->allRowsDie(
            "SELECT C.context_id,
                    COALESCE(NULLIF(MF.title, ''), C.title) AS title
             FROM {$p}lti_context AS C
             LEFT JOIN {$p}manifest AS MF ON C.manifest_id = MF.manifest_id
             LEFT JOIN {$p}course_catalog AS CAT ON CAT.context_id = C.context_id
             WHERE 1=1{$extra}
             ORDER BY COALESCE(NULLIF(MF.title, ''), C.title), C.context_id",
            $params
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $out = array();
        foreach ( $rows as $row ) {
            $cid = (int) ($row['context_id'] ?? 0);
            if ( $cid < 1 ) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ( $title === '' ) {
                $title = 'Course '.$cid;
            }
            $out[] = array('context_id' => $cid, 'title' => $title);
        }
        return $out;
    }

    public static function heroUrl($catalog_id, $updated_at = null) {
        global $CFG;
        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return '';
        }
        $base = rtrim((string) $CFG->wwwroot, '/').'/catalog/'.$id.'/image/hero';
        if ( is_string($updated_at) && $updated_at !== '' ) {
            $base .= (strpos($base, '?') === false ? '?' : '&').'v='.rawurlencode($updated_at);
        }
        return $base;
    }

    public static function iconUrl($catalog_id, $updated_at = null) {
        global $CFG;
        $id = (int) $catalog_id;
        if ( $id < 1 ) {
            return '';
        }
        $base = rtrim((string) $CFG->wwwroot, '/').'/catalog/'.$id.'/image/icon';
        if ( is_string($updated_at) && $updated_at !== '' ) {
            $base .= (strpos($base, '?') === false ? '?' : '&').'v='.rawurlencode($updated_at);
        }
        return $base;
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    public static function withDisplayUrls(array $rows) {
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $id = (int) ($row['catalog_id'] ?? 0);
            $catalogBytes = (int) ($row['hero_bytes'] ?? 0);
            $contextBytes = (int) ($row['context_hero_bytes'] ?? 0);
            $heroAt = null;
            if ( $catalogBytes > 0 ) {
                $heroAt = $row['hero_updated_at'] ?? null;
            } elseif ( $contextBytes > 0 ) {
                $heroAt = $row['context_hero_updated_at'] ?? null;
            }
            $row['has_catalog_hero'] = $catalogBytes > 0;
            $row['hero_url'] = ($catalogBytes > 0 || $contextBytes > 0)
                ? self::heroUrl($id, is_string($heroAt) ? $heroAt : null)
                : '';
            $iconBytes = (int) ($row['icon_bytes'] ?? 0);
            $iconAt = $row['icon_updated_at'] ?? null;
            $row['icon_url'] = $iconBytes > 0
                ? self::iconUrl($id, is_string($iconAt) ? $iconAt : null)
                : '';
            $row['enrolled'] = ! empty($row['membership_id']);
            if ( array_key_exists('description', $row) ) {
                $row['has_detail'] = self::hasRichDescription($row['description']);
            }
            unset(
                $row['hero_bytes'],
                $row['hero_updated_at'],
                $row['context_hero_bytes'],
                $row['context_hero_updated_at'],
                $row['icon_bytes'],
                $row['icon_updated_at']
            );
            $out[] = $row;
        }
        return $out;
    }

    public static function validHttpUrl($url) {
        if ( ! is_string($url) || $url === '' ) {
            return false;
        }
        if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return $scheme === 'http' || $scheme === 'https';
    }

    public static function plainText($text, $max) {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);
        $max = (int) $max;
        if ( $max > 0 && mb_strlen($text, 'UTF-8') > $max ) {
            $text = mb_substr($text, 0, $max, 'UTF-8');
        }
        return $text;
    }

    /**
     * True when the catalog long body has real content (not CKEditor blank).
     */
    public static function hasRichDescription($html) {
        return ! \Tsugi\Services\Site\Site::isEmptyHtml($html);
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

    /**
     * Google home context id for catalog rules (0 if none).
     */
    public static function homeContextId() {
        return Courses::siteLoginContextId();
    }
}
