<?php

namespace Tsugi\Services\Lessons;

use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Core\ReqScope;
use Tsugi\Grades\GradeUtil;
use Tsugi\Services\Quiz1\Quiz1Repository;

/**
 * Due dates on a lesson item, and on the link once that item has one.
 *
 * Before the link exists, the date is only the item's due field. Creating the
 * link copies it. Once the link is published, either editor writes both.
 */
class LessonDue {

    /**
     * Copy this item's due date onto a new or not-yet-published link.
     * An empty item due does not clear a date already stored on the link.
     * $overwrite is for the moment the link is published: the item's date replaces the link's.
     *
     * @param int $contextId
     * @param int $linkId
     * @param bool $overwrite
     * @param array<string, mixed>|null $doc Lesson document to read. Null loads the saved manifest.
     */
    public static function copyOntoLink($contextId, $linkId, $overwrite = false, $doc = null) {
        $contextId = (int) $contextId;
        $linkId = (int) $linkId;
        if ( $contextId < 1 || $linkId < 1 ) {
            return;
        }
        $link = self::linkRow($contextId, $linkId);
        if ( $link === null ) {
            return;
        }
        if ( ! $overwrite && $link['due'] !== '' ) {
            return;
        }
        if ( $doc === null ) {
            $doc = self::loadDocument($contextId);
        }
        if ( ! is_array($doc) ) {
            return;
        }
        $due = self::dueForLink($doc, $contextId, $link['key']);
        if ( $due === null ) {
            return;
        }
        self::writeLinkDue($contextId, $linkId, $due);
    }

    /**
     * After a lesson save, write due dates that changed on the document onto links that already exist.
     * An item that stays and has its date cleared updates the link. Removing the item leaves the link's date alone.
     *
     * @param array<string, mixed>|null $previous
     * @param array<string, mixed> $current
     */
    public static function pushChanges($contextId, $previous, array $current) {
        $contextId = (int) $contextId;
        if ( $contextId < 1 ) {
            return;
        }
        $before = self::indexDues(is_array($previous) ? $previous : array());
        $after = self::indexDues($current);
        $keys = array_keys($after);
        $changed = false;
        foreach ( $keys as $key ) {
            $old = isset($before[$key]) ? $before[$key] : '';
            $new = isset($after[$key]) ? $after[$key] : '';
            if ( $old === $new ) {
                continue;
            }
            $linkId = self::linkIdForKey($contextId, $key);
            if ( $linkId < 1 ) {
                continue;
            }
            self::writeLinkDue($contextId, $linkId, $new);
            $changed = true;
        }
        if ( $changed ) {
            GradeUtil::invalidateDueDatesCache($contextId);
        }
    }

    /**
     * A published link's due date was edited. Store that same day on the lesson item.
     *
     * @param array<int, string> $linkKeys
     */
    public static function pullPublished($contextId, array $linkKeys) {
        $contextId = (int) $contextId;
        if ( $contextId < 1 || count($linkKeys) < 1 ) {
            return;
        }
        $doc = self::loadDocument($contextId);
        if ( ! is_array($doc) ) {
            return;
        }
        $changed = false;
        foreach ( $linkKeys as $key ) {
            $key = trim((string) $key);
            if ( $key === '' ) {
                continue;
            }
            $link = self::linkByKey($contextId, $key);
            if ( $link === null || ! $link['published'] ) {
                continue;
            }
            if ( self::setItemDue($doc, $contextId, $key, $link['due']) ) {
                $changed = true;
            }
        }
        if ( ! $changed ) {
            return;
        }
        $doc = LessonsNormalize::normalizeDocument($doc);
        Manifest::saveNewVersion($contextId, $doc, ReqScope::loggedInUserId(), 'Due date');
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>|null
     */
    public static function loadDocument($contextId) {
        $id = Manifest::idForContext((int) $contextId);
        if ( $id < 1 ) {
            return null;
        }
        $json = Manifest::loadJson($id);
        if ( ! is_string($json) || $json === '' ) {
            return null;
        }
        $doc = json_decode($json, true);
        return is_array($doc) ? $doc : null;
    }

    /**
     * @param array<string, mixed> $doc
     * @return array<string, string> Stable item key => YYYY-MM-DD or empty
     */
    private static function indexDues(array $doc) {
        $out = array();
        foreach ( self::items($doc) as $item ) {
            $key = self::itemKey($item);
            if ( $key === '' ) {
                continue;
            }
            $out[$key] = self::itemDue($item);
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $doc
     * @return string|null YYYY-MM-DD, or null when no item matches
     */
    private static function dueForLink(array $doc, $contextId, $linkKey) {
        foreach ( self::items($doc) as $item ) {
            if ( ! self::itemMatchesLink($item, $contextId, $linkKey) ) {
                continue;
            }
            $due = self::itemDue($item);
            return $due === '' ? null : $due;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $doc
     */
    private static function setItemDue(array &$doc, $contextId, $linkKey, $due) {
        if ( ! isset($doc['modules']) || ! is_array($doc['modules']) ) {
            return false;
        }
        $changed = false;
        foreach ( $doc['modules'] as $mi => $mod ) {
            if ( ! is_array($mod) || ! isset($mod['items']) || ! is_array($mod['items']) ) {
                continue;
            }
            foreach ( $mod['items'] as $ii => $item ) {
                if ( ! is_array($item) || ! self::itemMatchesLink($item, $contextId, $linkKey) ) {
                    continue;
                }
                $current = self::itemDue($item);
                if ( $current === $due ) {
                    continue;
                }
                if ( $due === '' ) {
                    unset($doc['modules'][$mi]['items'][$ii]['due']);
                } else {
                    $doc['modules'][$mi]['items'][$ii]['due'] = $due;
                }
                $changed = true;
            }
        }
        return $changed;
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function itemMatchesLink(array $item, $contextId, $linkKey) {
        $quizId = isset($item['quiz_id']) ? (int) $item['quiz_id'] : 0;
        if ( $quizId > 0 ) {
            $stored = Quiz1Repository::storedLinkKey($quizId, $contextId);
            if ( $stored !== '' ) {
                return $stored === $linkKey;
            }
            if ( $linkKey === Quiz1Repository::linkKey($quizId) ) {
                return true;
            }
        }
        $resource = isset($item['resource_link_id']) ? trim((string) $item['resource_link_id']) : '';
        if ( $resource !== '' && $resource === $linkKey ) {
            return true;
        }
        $contentId = isset($item['content_id']) ? (int) $item['content_id'] : 0;
        if ( $contentId < 1 ) {
            return false;
        }
        $mapped = self::contentLinkKey($contextId, $contentId);
        return $mapped !== '' && $mapped === $linkKey;
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function itemKey(array $item) {
        $quizId = isset($item['quiz_id']) ? (int) $item['quiz_id'] : 0;
        if ( $quizId > 0 ) {
            return 'quiz:'.$quizId;
        }
        $contentId = isset($item['content_id']) ? (int) $item['content_id'] : 0;
        if ( $contentId > 0 ) {
            return 'content:'.$contentId;
        }
        $resource = isset($item['resource_link_id']) ? trim((string) $item['resource_link_id']) : '';
        return $resource === '' ? '' : 'link:'.$resource;
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function itemDue(array $item) {
        $due = isset($item['due']) && is_string($item['due']) ? trim($item['due']) : '';
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? $due : '';
    }

    /**
     * @param array<string, mixed> $doc
     * @return array<int, array<string, mixed>>
     */
    private static function items(array $doc) {
        $out = array();
        if ( ! isset($doc['modules']) || ! is_array($doc['modules']) ) {
            return $out;
        }
        foreach ( $doc['modules'] as $mod ) {
            if ( ! is_array($mod) || ! isset($mod['items']) || ! is_array($mod['items']) ) {
                continue;
            }
            foreach ( $mod['items'] as $item ) {
                if ( is_array($item) ) {
                    $out[] = $item;
                }
            }
        }
        return $out;
    }

    /**
     * @return array{key:string,due:string,published:bool}|null
     */
    private static function linkRow($contextId, $linkId) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT link_key, end_datetime, published FROM {$CFG->dbprefix}lti_link
             WHERE link_id = :LID AND context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':LID' => (int) $linkId, ':CID' => (int) $contextId)
        );
        if ( ! is_array($row) || ! isset($row['link_key']) ) {
            return null;
        }
        $key = trim((string) $row['link_key']);
        if ( $key === '' ) {
            return null;
        }
        return array(
            'key' => $key,
            'due' => GradeUtil::dueDateInputValue($row['end_datetime'] ?? null),
            'published' => (int) ($row['published'] ?? 0) === 1,
        );
    }

    /**
     * @return array{due:string,published:bool}|null
     */
    private static function linkByKey($contextId, $linkKey) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT end_datetime, published FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => (int) $contextId, ':LK' => $linkKey)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'due' => GradeUtil::dueDateInputValue($row['end_datetime'] ?? null),
            'published' => (int) ($row['published'] ?? 0) === 1,
        );
    }

    /**
     * @param int $contextId
     * @param string $linkKey
     */
    private static function linkIdForKey($contextId, $linkKey) {
        global $CFG, $PDOX;
        if ( strncmp($linkKey, 'quiz:', 5) === 0 ) {
            $quizId = (int) substr($linkKey, 5);
            $row = $PDOX->rowDie(
                "SELECT link_id FROM {$CFG->dbprefix}quiz1_quiz
                 WHERE quiz_id = :QID AND context_id = :CID",
                array(':QID' => $quizId, ':CID' => (int) $contextId)
            );
            return is_array($row) && ! empty($row['link_id']) ? (int) $row['link_id'] : 0;
        }
        if ( strncmp($linkKey, 'content:', 8) === 0 ) {
            $contentId = (int) substr($linkKey, 8);
            $row = $PDOX->rowDie(
                "SELECT link_id FROM {$CFG->dbprefix}lti_content
                 WHERE content_id = :ID AND context_id = :CID",
                array(':ID' => $contentId, ':CID' => (int) $contextId)
            );
            return is_array($row) && ! empty($row['link_id']) ? (int) $row['link_id'] : 0;
        }
        if ( strncmp($linkKey, 'link:', 5) !== 0 ) {
            return 0;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => (int) $contextId, ':LK' => substr($linkKey, 5))
        );
        return is_array($row) && ! empty($row['link_id']) ? (int) $row['link_id'] : 0;
    }

    /**
     * @param int $contextId
     * @param int $contentId
     */
    private static function contentLinkKey($contextId, $contentId) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT resource_link_id FROM {$CFG->dbprefix}lti_content
             WHERE content_id = :ID AND context_id = :CID",
            array(':ID' => (int) $contentId, ':CID' => (int) $contextId)
        );
        if ( ! is_array($row) || ! isset($row['resource_link_id']) ) {
            return '';
        }
        return trim((string) $row['resource_link_id']);
    }

    /**
     * @param int $contextId
     * @param int $linkId
     * @param string $due YYYY-MM-DD or empty to clear
     */
    private static function writeLinkDue($contextId, $linkId, $due) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $end = $due === '' ? null : $due.' 23:59:59';
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link
             SET end_datetime = :E, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(
                ':E' => $end,
                ':LID' => (int) $linkId,
                ':CID' => (int) $contextId,
            )
        );
        GradeUtil::invalidateDueDatesCache($contextId);
    }
}
