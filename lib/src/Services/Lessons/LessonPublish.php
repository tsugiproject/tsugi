<?php

namespace Tsugi\Services\Lessons;

use Tsugi\Core\LTIX;
use Tsugi\Grades\GradeUtil;
use Tsugi\Services\Outbound\LtiContentService;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Util\U;

/**
 * Student access for a lesson assignment. The link's published flag is the
 * value the lesson, the grade book, and Manage due dates all read.
 */
class LessonPublish {

    /**
     * Publish or unpublish the assignment with this link key.
     *
     * A quiz uses its quiz link. A content launch uses that row, which updates
     * the link once a grade column exists. Any other key updates the link,
     * creating an unpublished row when the lesson item does not have one yet.
     *
     * @param int $contextId
     * @param string $linkKey
     * @param bool $published
     * @param string $title Used only when a missing link row is created
     */
    public static function apply($contextId, $linkKey, $published, $title = '') {
        $contextId = (int) $contextId;
        $linkKey = trim((string) $linkKey);
        if ( $contextId < 1 || $linkKey === '' ) {
            return false;
        }
        $quizId = self::quizIdForLinkKey($contextId, $linkKey);
        if ( $quizId > 0 ) {
            if ( $published ) {
                return Quiz1Repository::publish($quizId, $contextId) !== null;
            }
            return Quiz1Repository::unpublish($quizId, $contextId);
        }
        if ( Quiz1Repository::isLegacyLinkKey($linkKey) ) {
            return false;
        }
        $contentId = self::contentIdForLink($contextId, $linkKey);
        if ( $contentId > 0 ) {
            return LtiContentService::setPublished($contextId, $contentId, $published);
        }
        return self::setLink($contextId, $linkKey, $published, $title);
    }

    /**
     * Quiz that owns this resource link id in the course.
     * A stored key wins, including a legacy quiz1:{id} that was imported
     * onto a quiz with a different primary key. quiz1:{id} still means
     * that quiz when the quiz has no link yet.
     *
     * @param int $contextId
     * @param string $linkKey
     */
    private static function quizIdForLinkKey($contextId, $linkKey) {
        $quizId = Quiz1Repository::quizIdByLinkKey($contextId, $linkKey);
        if ( $quizId > 0 ) {
            return $quizId;
        }
        if ( ! Quiz1Repository::isLegacyLinkKey($linkKey) ) {
            return 0;
        }
        $legacyId = (int) substr($linkKey, strlen('quiz1:'));
        if ( $legacyId < 1 ) {
            return 0;
        }
        $stored = Quiz1Repository::storedLinkKey($legacyId, $contextId);
        if ( $stored !== '' && $stored !== $linkKey ) {
            return 0;
        }
        return $legacyId;
    }

    /**
     * Set published on this course's link, creating the row when it is missing.
     *
     * @param int $contextId
     * @param string $resourceLinkId
     * @param bool $published
     * @param string $title
     */
    public static function setLink($contextId, $resourceLinkId, $published, $title = '') {
        global $CFG, $PDOX;
        $contextId = (int) $contextId;
        $resourceLinkId = trim((string) $resourceLinkId);
        if ( $contextId < 1 || $resourceLinkId === '' ) {
            return false;
        }
        LTIX::getConnection();
        $sha = U::lti_sha256($resourceLinkId);
        $flag = $published ? 1 : 0;
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :context_id AND link_sha256 = :link_sha256",
            array(
                ':context_id' => $contextId,
                ':link_sha256' => $sha,
            )
        );
        if ( is_array($row) && isset($row['link_id']) ) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_link
                 SET published = :published, deleted = 0, updated_at = NOW()
                 WHERE link_id = :link_id AND context_id = :context_id",
                array(
                    ':published' => $flag,
                    ':link_id' => (int) $row['link_id'],
                    ':context_id' => $contextId,
                )
            );
            if ( $published ) {
                LessonDue::copyOntoLink($contextId, (int) $row['link_id'], true);
            }
            return true;
        }
        $storedTitle = trim((string) $title);
        if ( $storedTitle === '' ) {
            $storedTitle = $resourceLinkId;
        }
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_link
                (link_key, link_sha256, title, context_id, published, created_at, updated_at)
             VALUES
                (:link_key, :link_sha256, :title, :context_id, :published, NOW(), NOW())",
            array(
                ':link_key' => $resourceLinkId,
                ':link_sha256' => $sha,
                ':title' => $storedTitle,
                ':context_id' => $contextId,
                ':published' => $flag,
            )
        );
        if ( $published ) {
            LessonDue::copyOntoLink($contextId, (int) $PDOX->lastInsertId(), true);
        }
        GradeUtil::invalidateDueDatesCache($contextId);
        return true;
    }

    /**
     * @param int $contextId
     * @param string $linkKey
     */
    private static function contentIdForLink($contextId, $linkKey) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT content_id FROM {$CFG->dbprefix}lti_content
             WHERE context_id = :CID AND resource_link_id = :RK
             LIMIT 1",
            array(
                ':CID' => (int) $contextId,
                ':RK' => $linkKey,
            )
        );
        if ( ! is_array($row) || empty($row['content_id']) ) {
            return 0;
        }
        return (int) $row['content_id'];
    }
}
