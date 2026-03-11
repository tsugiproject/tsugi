<?php

namespace Tsugi\Services\Badges;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Badges;

/**
 * Service for minted badge operations (OB2/OB3).
 * Handles badge minting, lookup, baked images, and assertion data.
 * Does not touch legacy /badges/ endpoints.
 */
class BadgeService {

    /** GUID format: m + 32 hex chars */
    const GUID_LENGTH = 33;
    const GUID_PREFIX = 'm';

    /**
     * Get minted badge GUID if it exists (no minting).
     *
     * @return string|null Badge GUID or null if not minted
     */
    public static function getMintedGuidIfExists(
        int $user_id,
        int $context_id,
        string $badge_code
    ): ?string {
        global $CFG, $PDOX;

        if ( ! self::tableExists() ) {
            return null;
        }

        $q = $PDOX->queryReturnError(
            "SELECT badge_guid FROM {$CFG->dbprefix}badges WHERE user_id = :UID AND context_id = :CID AND badge_code = :CODE",
            array(':UID' => $user_id, ':CID' => $context_id, ':CODE' => $badge_code)
        );

        if ( ! $q->success ) {
            return null;
        }

        $row = $q->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['badge_guid'] : null;
    }

    /**
     * Check if an id is a minted badge GUID.
     */
    public static function isMintedGuid(string $id): bool {
        return strlen($id) === self::GUID_LENGTH
            && $id[0] === self::GUID_PREFIX
            && ctype_xdigit(substr($id, 1));
    }

    /**
     * Check if the badges table exists.
     */
    public static function tableExists(): bool {
        global $CFG, $PDOX;
        $tname = $CFG->dbprefix . 'badges';
        $q = $PDOX->queryReturnError(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tname LIMIT 1",
            array(':tname' => $tname)
        );
        return $q->success && $q->rowCount() > 0;
    }

    /**
     * Mint a badge or return existing minted badge GUID.
     *
     * @return string|false The badge GUID, or false if table missing/error
     */
    public static function mintOrGet(
        int $user_id,
        int $context_id,
        string $badge_code,
        string $user_displayname,
        string $user_email,
        string $context_title,
        string $badge_title
    ) {
        global $CFG, $PDOX;

        if ( ! self::tableExists() ) {
            return false;
        }

        $q = $PDOX->queryReturnError(
            "SELECT badge_guid FROM {$CFG->dbprefix}badges WHERE user_id = :UID AND context_id = :CID AND badge_code = :CODE",
            array(':UID' => $user_id, ':CID' => $context_id, ':CODE' => $badge_code)
        );

        if ( ! $q->success ) {
            return false;
        }

        $row = $q->fetch(\PDO::FETCH_ASSOC);
        if ( $row ) {
            return $row['badge_guid'];
        }

        $guid = self::GUID_PREFIX . bin2hex(random_bytes(16));
        $issued_at = date('Y-m-d H:i:s');

        $iq = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}badges (badge_guid, user_id, context_id, badge_code, user_displayname, user_email, context_title, badge_title, issued_at) VALUES (:GUID, :UID, :CID, :CODE, :DISPLAYNAME, :EMAIL, :CTITLE, :BTITLE, :ISSUED)",
            array(
                ':GUID' => $guid,
                ':UID' => $user_id,
                ':CID' => $context_id,
                ':CODE' => $badge_code,
                ':DISPLAYNAME' => $user_displayname,
                ':EMAIL' => $user_email,
                ':CTITLE' => $context_title,
                ':BTITLE' => $badge_title,
                ':ISSUED' => $issued_at,
            )
        );

        return $iq->success ? $guid : false;
    }

    /**
     * Load minted badge by GUID.
     *
     * @return array|null Row data or null if not found
     */
    public static function getByGuid(string $guid): ?array {
        global $CFG, $PDOX;

        $row = $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}badges WHERE badge_guid = :GUID",
            array(':GUID' => $guid)
        );

        return $row ?: null;
    }

    /**
     * Get assertion data (row, pieces, badge) for a minted GUID.
     * Used by assertions route to build the shared structure for HTML/JSON handlers.
     *
     * @return array|null ['row' => [...], 'pieces' => [...], 'badge' => object] or null
     */
    public static function getAssertionDataForGuid(string $guid, $lessons): ?array {
        $minted = self::getByGuid($guid);
        if ( ! $minted ) {
            return null;
        }

        $row = array(
            'displayname' => $minted['user_displayname'],
            'email' => $minted['user_email'],
            'issued_at' => $minted['issued_at'],
            'title' => $minted['context_title'],
        );

        $pieces = array(
            $minted['user_id'],
            $minted['badge_code'],
            $minted['context_id'],
        );

        $badge = null;
        foreach ( $lessons->lessons->badges as $b ) {
            if ( $b->image === $minted['badge_code'] . '.png' ) {
                $badge = $b;
                break;
            }
        }
        if ( $badge === null ) {
            $badge = (object)[
                'title' => $minted['badge_title'],
                'image' => $minted['badge_code'] . '.png',
                'completion' => false,
            ];
        }

        return array('row' => $row, 'pieces' => $pieces, 'badge' => $badge);
    }

    /**
     * Get assignments for a badge from badge_assignments table.
     *
     * @return array Array of ['resource_link_id' => ..., 'resource_link_title' => ..., 'seq' => ...]
     */
    public static function getAssignmentsByCode(string $badge_code): array {
        global $CFG, $PDOX;

        if ( ! self::tableExistsFor('badge_assignments') ) {
            return array();
        }

        $stmt = $PDOX->queryReturnError(
            "SELECT resource_link_id, resource_link_title, seq FROM {$CFG->dbprefix}badge_assignments WHERE badge_code = :CODE ORDER BY seq, resource_link_id",
            array(':CODE' => $badge_code)
        );
        if ( ! $stmt->success ) {
            return array();
        }
        $rows = array();
        while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            $rows[] = $row;
        }
        $stmt->closeCursor();
        return $rows;
    }

    /**
     * Get assignments for a badge - from badge_assignments table or fallback to lessons.
     * Returns array of ['resource_link_id' => ..., 'title' => ..., 'href' => ... or null].
     */
    public static function getAssignmentsForEvidence(string $badge_code, $lessons): array {
        global $CFG;
        $base = rtrim($CFG->apphome ?? $CFG->wwwroot ?? '', '/');
        $from_db = self::getAssignmentsByCode($badge_code);
        if ( ! empty($from_db) ) {
            $result = array();
            foreach ( $from_db as $r ) {
                $mod = $lessons->getModuleByRlid($r['resource_link_id']);
                $href = $mod ? $base . '/lessons/' . urlencode($mod->anchor) : null;
                $result[] = array(
                    'resource_link_id' => $r['resource_link_id'],
                    'title' => ! empty($r['resource_link_title']) ? $r['resource_link_title'] : $r['resource_link_id'],
                    'href' => $href,
                );
            }
            return $result;
        }
        $badge = null;
        foreach ( $lessons->lessons->badges as $b ) {
            if ( isset($b->image) && $b->image === $badge_code . '.png' ) {
                $badge = $b;
                break;
            }
        }
        if ( ! $badge || ! isset($badge->assignments) ) {
            return array();
        }
        $result = array();
        foreach ( $badge->assignments as $resource_link_id ) {
            $lti = $lessons->getLtiByRlid($resource_link_id);
            $mod = $lessons->getModuleByRlid($resource_link_id);
            $title = $lti ? $lti->title : $resource_link_id;
            $href = $mod ? $base . '/lessons/' . urlencode($mod->anchor) : null;
            $result[] = array(
                'resource_link_id' => $resource_link_id,
                'title' => $title,
                'href' => $href,
            );
        }
        return $result;
    }

    /**
     * Check if a specific table exists.
     */
    private static function tableExistsFor(string $base_name): bool {
        global $CFG, $PDOX;
        $tname = $CFG->dbprefix . $base_name;
        $q = $PDOX->queryReturnError(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tname LIMIT 1",
            array(':tname' => $tname)
        );
        return $q->success && $q->rowCount() > 0;
    }

}
