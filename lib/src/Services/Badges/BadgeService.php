<?php

namespace Tsugi\Services\Badges;

use \Tsugi\Util\U;
use \Tsugi\Core\Manifest;
use \Tsugi\LinkedIn\LinkedIn;
use \Tsugi\UI\BadgeShare\LinkedInShare;
use \Tsugi\Services\Lessons\LessonsService;

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
     * Mint a badge or refresh the snapshot on the existing row.
     *
     * The GUID and issued_at stay put on republish. Name, email, course title,
     * badge title, and accomplishments are rewritten from the live course.
     *
     * @param array<string,mixed>|null $accomplishments
     * @return string|false The badge GUID, or false if table missing/error
     */
    public static function mintOrGet(
        int $user_id,
        int $context_id,
        string $badge_code,
        string $user_displayname,
        string $user_email,
        string $context_title,
        string $badge_title,
        ?array $accomplishments = null
    ) {
        global $CFG, $PDOX;

        if ( ! self::tableExists() ) {
            return false;
        }

        if ( $accomplishments === null ) {
            $accomplishments = self::minimalAccomplishments(array(
                'badge_code' => $badge_code,
                'badge_title' => $badge_title,
                'context_title' => $context_title,
            ));
        }
        $json = self::encodeAccomplishments($accomplishments);

        $q = $PDOX->queryReturnError(
            "SELECT badge_guid FROM {$CFG->dbprefix}badges WHERE user_id = :UID AND context_id = :CID AND badge_code = :CODE",
            array(':UID' => $user_id, ':CID' => $context_id, ':CODE' => $badge_code)
        );

        if ( ! $q->success ) {
            return false;
        }

        $row = $q->fetch(\PDO::FETCH_ASSOC);
        $q->closeCursor();
        if ( $row ) {
            $uq = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}badges
                    SET user_displayname = :DISPLAYNAME,
                        user_email = :EMAIL,
                        context_title = :CTITLE,
                        badge_title = :BTITLE,
                        accomplishments = :ACC
                  WHERE badge_guid = :GUID",
                array(
                    ':DISPLAYNAME' => $user_displayname,
                    ':EMAIL' => $user_email,
                    ':CTITLE' => $context_title,
                    ':BTITLE' => $badge_title,
                    ':ACC' => $json,
                    ':GUID' => $row['badge_guid'],
                )
            );
            return $uq->success ? $row['badge_guid'] : false;
        }

        $guid = self::GUID_PREFIX . bin2hex(random_bytes(16));
        $issued_at = date('Y-m-d H:i:s');

        $iq = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}badges (badge_guid, user_id, context_id, badge_code, user_displayname, user_email, context_title, badge_title, issued_at, accomplishments) VALUES (:GUID, :UID, :CID, :CODE, :DISPLAYNAME, :EMAIL, :CTITLE, :BTITLE, :ISSUED, :ACC)",
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
                ':ACC' => $json,
            )
        );

        return $iq->success ? $guid : false;
    }

    /**
     * Display row for a baked or legacy assertion after the course membership is gone.
     *
     * Prefers a row that still has this context_id, then a row detached by course delete
     * (context_id NULL). Name, email, course title, and issue time come from the badge row.
     *
     * @return array{displayname:string,email:string,user_key:string,login_at:string,title:string,badge_title:string}|null
     */
    public static function mintedDisplayRow(int $user_id, string $badge_code, int $context_id): ?array {
        global $CFG, $PDOX;

        if ( $user_id < 1 || $badge_code === '' || ! self::tableExists() ) {
            return null;
        }

        $q = $PDOX->queryReturnError(
            "SELECT user_displayname, user_email, issued_at, context_title, badge_title
             FROM {$CFG->dbprefix}badges
             WHERE user_id = :UID AND badge_code = :CODE
               AND (context_id = :CID OR context_id IS NULL)
             ORDER BY context_id IS NULL, issued_at DESC
             LIMIT 1",
            array(
                ':UID' => $user_id,
                ':CODE' => $badge_code,
                ':CID' => $context_id,
            )
        );
        if ( ! $q->success ) {
            return null;
        }
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        $q->closeCursor();
        if ( ! is_array($row) ) {
            return null;
        }

        return array(
            'displayname' => (string) ($row['user_displayname'] ?? ''),
            'email' => (string) ($row['user_email'] ?? ''),
            'user_key' => '',
            'login_at' => (string) ($row['issued_at'] ?? ''),
            'title' => (string) ($row['context_title'] ?? ''),
            'badge_title' => (string) ($row['badge_title'] ?? ''),
        );
    }

    /**
     * Badge object from stored title when the course lessons no longer list this image.
     *
     * @return object
     */
    public static function syntheticBadge(string $badge_code, string $badge_title) {
        return (object) array(
            'title' => $badge_title !== '' ? $badge_title : $badge_code,
            'image' => $badge_code.'.png',
            'completion' => false,
        );
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
     * Assertion data for a minted GUID, from the badge row only.
     *
     * @return array{row:array,pieces:array,badge:object,frozen:array}|null
     */
    public static function getAssertionDataForGuid(string $guid): ?array {
        $minted = self::getByGuid($guid);
        if ( ! $minted ) {
            return null;
        }

        $accomplishments = self::accomplishmentsForRow($minted);
        $frozen = $accomplishments;
        $frozen['guid'] = $guid;

        $title = (string) ($minted['badge_title'] ?? '');
        if ( $title === '' ) {
            $title = (string) ($minted['badge_code'] ?? '');
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

        $badge = (object) array(
            'title' => $title,
            'image' => $minted['badge_code'] . '.png',
            'completion' => ! empty($accomplishments['completion']),
        );

        return array(
            'row' => $row,
            'pieces' => $pieces,
            'badge' => $badge,
            'frozen' => $frozen,
        );
    }

    /**
     * Snapshot of the badge definition and its assignment list.
     *
     * Called at publish, while lessons and the course still exist.
     *
     * @param object $badge A lessons badge object
     * @return array<string,mixed>
     */
    public static function captureAccomplishments($lessons, $badge, string $context_title): array {
        global $CFG;

        $code = '';
        if ( isset($badge->image) && is_string($badge->image) ) {
            $code = basename($badge->image, '.png');
        }
        $title = (isset($badge->title) && is_string($badge->title) && $badge->title !== '')
            ? $badge->title
            : $code;
        $servicename = (isset($CFG->servicename) && is_string($CFG->servicename)) ? $CFG->servicename : '';
        $narrative = "Completed {$title} in course {$context_title}";
        if ( $servicename !== '' ) {
            $narrative .= " at {$servicename}";
        }

        $image = self::imageSnapshot($code);
        $evidence = (isset($CFG->apphome) && is_string($CFG->apphome)) ? $CFG->apphome : '';

        $threshold = null;
        if ( isset($badge->threshold) && is_numeric($badge->threshold) ) {
            $threshold = 0 + $badge->threshold;
        }

        $items = array();
        if ( $code !== '' && is_object($lessons) ) {
            $items = self::getAssignmentsForEvidence($code, $lessons);
        }

        return array(
            'completion' => ! empty($badge->completion),
            'threshold' => $threshold,
            'narrative' => $narrative,
            'evidence_url' => $evidence,
            'image_url' => $image['image_url'],
            'image_sha256' => $image['image_sha256'],
            'issuer' => self::issuerSnapshot(),
            'items' => $items,
        );
    }

    /**
     * Public image URL and the SHA-256 of the PNG bytes at badge_path.
     *
     * An empty hash means the file was not readable when the snapshot was written.
     * The key is still stored so a later upgrade does not clear the row again.
     *
     * @return array{image_url:string,image_sha256:string}
     */
    private static function imageSnapshot(string $code): array {
        global $CFG;

        $url = '';
        $sha = '';
        if ( $code !== '' && isset($CFG->badge_url) && is_string($CFG->badge_url) && $CFG->badge_url !== '' ) {
            $url = rtrim($CFG->badge_url, '/') . '/' . $code . '.png';
        }
        if ( $code !== '' && isset($CFG->badge_path) && is_string($CFG->badge_path) && $CFG->badge_path !== '' ) {
            $path = rtrim($CFG->badge_path, '/') . '/' . $code . '.png';
            if ( is_readable($path) ) {
                $hash = hash_file('sha256', $path);
                if ( is_string($hash) ) {
                    $sha = $hash;
                }
            }
        }

        return array(
            'image_url' => $url,
            'image_sha256' => $sha,
        );
    }

    /**
     * Clear snapshots that were written before the image URL and hash were stored.
     *
     * A later upgrade leaves a row alone once image_sha256 is present, including
     * when that hash is an empty string because the PNG was missing.
     *
     * @return int Rows cleared
     */
    public static function clearSnapshotsMissingImageHash(): int {
        global $CFG, $PDOX;

        $table = "{$CFG->dbprefix}badges";
        if ( $PDOX->metadata($table) === false ) {
            return 0;
        }
        if ( $PDOX->columnExists('accomplishments', $table) === false ) {
            return 0;
        }

        $q = $PDOX->queryReturnError(
            "SELECT badge_guid, accomplishments
               FROM {$table}
              WHERE accomplishments IS NOT NULL AND accomplishments <> ''"
        );
        if ( ! $q->success ) {
            return 0;
        }
        $rows = array();
        while ( $row = $q->fetch(\PDO::FETCH_ASSOC) ) {
            $rows[] = $row;
        }
        $q->closeCursor();

        $cleared = 0;
        foreach ( $rows as $row ) {
            $guid = (string) ($row['badge_guid'] ?? '');
            if ( $guid === '' ) {
                continue;
            }
            $decoded = self::decodeAccomplishments($row['accomplishments'] ?? null);
            if ( is_array($decoded) && array_key_exists('image_sha256', $decoded) ) {
                continue;
            }
            $uq = $PDOX->queryReturnError(
                "UPDATE {$table} SET accomplishments = NULL WHERE badge_guid = :GUID",
                array(':GUID' => $guid)
            );
            if ( $uq->success ) {
                $cleared++;
            }
        }
        return $cleared;
    }

    /**
     * Fill accomplishments for rows published before the column existed.
     *
     * Uses the course manifest when the context still has one, otherwise the
     * site lessons file. A row whose lessons are already gone gets a snapshot
     * from the scalars already stored on it.
     */
    public static function backfillMissingAccomplishments(): void {
        global $CFG, $PDOX;

        $table = "{$CFG->dbprefix}badges";
        if ( $PDOX->metadata($table) === false ) {
            return;
        }
        if ( $PDOX->columnExists('accomplishments', $table) === false ) {
            return;
        }

        $q = $PDOX->queryReturnError(
            "SELECT badge_guid, context_id, badge_code, context_title, badge_title
               FROM {$table}
              WHERE accomplishments IS NULL OR accomplishments = ''"
        );
        if ( ! $q->success ) {
            return;
        }
        $rows = array();
        while ( $row = $q->fetch(\PDO::FETCH_ASSOC) ) {
            $rows[] = $row;
        }
        $q->closeCursor();
        if ( count($rows) < 1 ) {
            return;
        }

        $siteLessons = self::siteLessons();
        foreach ( $rows as $row ) {
            $guid = (string) ($row['badge_guid'] ?? '');
            if ( $guid === '' ) {
                continue;
            }
            $lessons = self::lessonsForContext(
                isset($row['context_id']) ? (int) $row['context_id'] : 0,
                $siteLessons
            );
            $badge = self::badgeDefinition($lessons, (string) ($row['badge_code'] ?? ''));
            if ( $badge ) {
                $accomplishments = self::captureAccomplishments(
                    $lessons,
                    $badge,
                    (string) ($row['context_title'] ?? '')
                );
            } else {
                $accomplishments = self::minimalAccomplishments($row);
            }
            $PDOX->queryReturnError(
                "UPDATE {$table} SET accomplishments = :ACC WHERE badge_guid = :GUID",
                array(
                    ':ACC' => self::encodeAccomplishments($accomplishments),
                    ':GUID' => $guid,
                )
            );
        }
    }

    /**
     * Issuer profile frozen at publish. Signing keys stay in config.
     *
     * @return array{name:string,url:string,email:string,logo?:string,linkedin_url?:string}
     */
    public static function issuerSnapshot(): array {
        global $CFG;

        $name = '';
        if ( isset($CFG->badge_organization) && is_string($CFG->badge_organization) && $CFG->badge_organization !== '' ) {
            $name = $CFG->badge_organization;
        } elseif ( is_object($CFG) && method_exists($CFG, 'getBadgeOrganization') ) {
            $name = (string) $CFG->getBadgeOrganization();
        } elseif ( isset($CFG->servicename) && is_string($CFG->servicename) ) {
            $name = $CFG->servicename;
        }

        $url = '';
        if ( isset($CFG->badge_organization_url) && is_string($CFG->badge_organization_url) && $CFG->badge_organization_url !== '' ) {
            $url = $CFG->badge_organization_url;
        } elseif ( isset($CFG->apphome) && is_string($CFG->apphome) ) {
            $url = $CFG->apphome;
        }

        $email = '';
        if ( isset($CFG->badge_issuer_email) && is_string($CFG->badge_issuer_email) && $CFG->badge_issuer_email !== '' ) {
            $email = $CFG->badge_issuer_email;
        }

        $snap = array(
            'name' => $name,
            'url' => $url,
            'email' => $email,
        );
        if ( isset($CFG->badge_organization_logo) && is_string($CFG->badge_organization_logo) && $CFG->badge_organization_logo !== '' ) {
            $snap['logo'] = $CFG->badge_organization_logo;
        }
        if ( isset($CFG->linkedin_url) && is_string($CFG->linkedin_url) && $CFG->linkedin_url !== '' ) {
            $snap['linkedin_url'] = $CFG->linkedin_url;
        }
        return $snap;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function accomplishmentsForRow(array $row): array {
        $decoded = self::decodeAccomplishments($row['accomplishments'] ?? null);
        if ( is_array($decoded) ) {
            return $decoded;
        }
        return self::minimalAccomplishments($row);
    }

    /**
     * Snapshot from the scalars already on the row, when lessons are gone.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function minimalAccomplishments(array $row): array {
        global $CFG;

        $code = (string) ($row['badge_code'] ?? '');
        $title = (string) ($row['badge_title'] ?? '');
        if ( $title === '' ) {
            $title = $code;
        }
        $context_title = (string) ($row['context_title'] ?? '');
        $servicename = (isset($CFG->servicename) && is_string($CFG->servicename)) ? $CFG->servicename : '';
        $narrative = "Completed {$title} in course {$context_title}";
        if ( $servicename !== '' ) {
            $narrative .= " at {$servicename}";
        }
        $image = self::imageSnapshot($code);
        $evidence = (isset($CFG->apphome) && is_string($CFG->apphome)) ? $CFG->apphome : '';

        return array(
            'completion' => false,
            'threshold' => null,
            'narrative' => $narrative,
            'evidence_url' => $evidence,
            'image_url' => $image['image_url'],
            'image_sha256' => $image['image_sha256'],
            'issuer' => self::issuerSnapshot(),
            'items' => array(),
        );
    }

    /**
     * @param array<string,mixed> $accomplishments
     */
    private static function encodeAccomplishments(array $accomplishments): string {
        $json = json_encode($accomplishments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '{}';
    }

    /**
     * @param mixed $raw
     * @return array<string,mixed>|null
     */
    private static function decodeAccomplishments($raw): ?array {
        if ( ! is_string($raw) || trim($raw) === '' ) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if ( ! is_array($decoded) ) {
            return null;
        }
        return $decoded;
    }

    /**
     * @return LessonsService|null
     */
    private static function siteLessons() {
        global $CFG;
        if ( ! isset($CFG->lessons) || ! is_string($CFG->lessons) || ! is_readable($CFG->lessons) ) {
            return null;
        }
        $json = file_get_contents($CFG->lessons);
        if ( ! is_string($json) || $json === '' ) {
            return null;
        }
        $loaded = LessonsService::tryFromJson($json);
        return is_object($loaded) ? $loaded : null;
    }

    /**
     * @param LessonsService|null $siteLessons
     * @return LessonsService|null
     */
    private static function lessonsForContext(int $context_id, $siteLessons) {
        global $CFG, $PDOX;

        if ( $context_id > 0 ) {
            $q = $PDOX->queryReturnError(
                "SELECT manifest_id FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
            $manifest_id = 0;
            if ( $q->success ) {
                $context = $q->fetch(\PDO::FETCH_ASSOC);
                $q->closeCursor();
                if ( is_array($context) && isset($context['manifest_id']) ) {
                    $manifest_id = (int) $context['manifest_id'];
                }
            }
            if ( $manifest_id > 0 ) {
                $json = Manifest::loadJson($manifest_id);
                if ( is_string($json) && $json !== '' ) {
                    $loaded = LessonsService::tryFromJson($json);
                    if ( is_object($loaded) ) {
                        return $loaded;
                    }
                }
            }
        }
        return $siteLessons;
    }

    /**
     * @param LessonsService|null $lessons
     * @return object|null
     */
    private static function badgeDefinition($lessons, string $badge_code) {
        if ( ! is_object($lessons) || $badge_code === '' || ! isset($lessons->lessons->badges) || ! is_iterable($lessons->lessons->badges) ) {
            return null;
        }
        foreach ( $lessons->lessons->badges as $badge ) {
            if ( isset($badge->image) && $badge->image === $badge_code . '.png' ) {
                return $badge;
            }
        }
        return null;
    }

    /**
     * Assignment list for the Evidence section, from the current lessons.
     *
     * @return array<int, array{resource_link_id:string, title:string, href:?string}>
     */
    public static function getAssignmentsForEvidence(string $badge_code, $lessons): array {
        global $CFG;
        $base = rtrim($CFG->apphome ?? $CFG->wwwroot ?? '', '/');
        $badge = null;
        if ( is_object($lessons) && isset($lessons->lessons->badges) && is_iterable($lessons->lessons->badges) ) {
            foreach ( $lessons->lessons->badges as $b ) {
                if ( isset($b->image) && $b->image === $badge_code . '.png' ) {
                    $badge = $b;
                    break;
                }
            }
        }
        if ( ! $badge || ! isset($badge->assignments) || ! is_iterable($badge->assignments) ) {
            return array();
        }
        $result = array();
        foreach ( $badge->assignments as $resource_link_id ) {
            $resource_link_id = (string) $resource_link_id;
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
     * URL that records a LinkedIn click then redirects to LinkedIn.
     */
    public static function linkedInTrackingUrl(string $guid, bool $share = false): string {
        global $CFG;
        $url = rtrim($CFG->wwwroot, '/') . '/assertions/linkedin?id=' . rawurlencode($guid);
        if ( $share ) {
            $url .= '&share=1';
        }
        return $url;
    }

    /**
     * Increment linkedin_clicks for a minted badge.
     */
    public static function recordLinkedInClick(string $guid): bool {
        global $CFG, $PDOX;

        if ( ! self::isMintedGuid($guid) || ! self::tableExists() ) {
            return false;
        }

        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}badges
                SET linkedin_clicks = COALESCE(linkedin_clicks, 0) + 1
                WHERE badge_guid = :GUID",
            array(':GUID' => $guid)
        );

        return $q->success && $q->rowCount() > 0;
    }

    /**
     * Build the external LinkedIn URL for a minted badge (credential add or share post).
     *
     * @return string|null LinkedIn URL or null if the badge cannot be shared this way
     */
    public static function linkedInDestinationUrl(string $guid, bool $share): ?string {
        global $CFG;

        $data = self::getAssertionDataForGuid($guid);
        if ( ! $data ) {
            return null;
        }

        $row = $data['row'];
        $badge = $data['badge'];
        $landing_url = rtrim($CFG->wwwroot, '/') . '/assertions/' . rawurlencode($guid);

        if ( $share ) {
            $text = sprintf('I earned the "%s" badge', $badge->title ?? '');
            $course_title = U::get($row, 'title', '');
            if ( is_string($course_title) && strlen($course_title) > 0 ) {
                $text .= ' from ' . $course_title;
            }
            $text .= '!';
            $platform = new LinkedInShare();
            return $platform->getShareUrl($landing_url, $text);
        }

        $completion_badge = isset($badge->completion) ? $badge->completion : false;
        if ( ! $completion_badge ) {
            return null;
        }

        $issued_on = U::iso8601(U::get($row, 'issued_at'));
        $issueYear = null;
        $issueMonth = null;
        if ( is_string($issued_on) && strlen($issued_on) > 0 ) {
            try {
                $date_obj = new \DateTime($issued_on);
                $issueYear = (int) $date_obj->format('Y');
                $issueMonth = (int) $date_obj->format('m');
            } catch (\Exception $e) {
                // LinkedIn accepts certification without dates
            }
        }

        $credential_id = self::credentialIdFromRow($row);
        $linkedin = LinkedIn::fromConfig($CFG);
        return $linkedin->buildAddCertificationUrl(
            $badge->title ?? '',
            $landing_url,
            $credential_id,
            $issueYear,
            $issueMonth
        );
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function credentialIdFromRow(array $row): ?string {
        $displayname = U::get($row, 'displayname', '');
        $email = U::get($row, 'email', '');
        if ( ! is_string($displayname) && ! is_string($email) ) {
            return null;
        }

        $output = '';
        if ( is_string($displayname) && strlen($displayname) > 0 ) {
            $output .= $displayname;
        }
        if ( is_string($displayname) && strlen($displayname) > 0
            && is_string($email) && strlen($email) > 0 ) {
            $output .= ' ';
        }
        if ( is_string($email) && strlen($email) > 0 ) {
            $output .= $email;
        }
        if ( strlen($output) < 1 ) {
            return null;
        }

        return substr(md5('42 ' . $output), 0, 5);
    }

}
