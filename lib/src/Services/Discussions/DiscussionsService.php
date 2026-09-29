<?php

namespace Tsugi\Services\Discussions;

use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Core\ReqScope;
use Tsugi\Util\U;

/**
 * Thread and comment storage for one discussion.
 *
 * The discussion is an lti_link. Callers put that link on ReqScope first.
 * A cookie session and an LTI launch of tool/tdiscus share one link when
 * link_key is the lessons resource_link_id.
 */
class DiscussionsService {

    const default_page_size = 20;

    /**
     * Find or create the lti_link for a lessons discussion in the current course.
     *
     * link_key is the lessons resource_link_id, the same key an LTI launch stores.
     *
     * @param string $resourceLinkId
     * @return int link_id, or 0 when the id is not a discussion in the current lessons
     */
    public static function ensureLessonLink($resourceLinkId) {
        global $CFG, $PDOX;

        if ( ! is_string($resourceLinkId) || $resourceLinkId === '' ) {
            return 0;
        }
        $contextId = ReqScope::currentContextId();
        if ( $contextId < 1 ) {
            return 0;
        }

        LTIX::getConnection();
        $lessons = Manifest::currentLessons();
        if ( ! $lessons ) {
            return 0;
        }
        $item = null;
        foreach ( $lessons->flattenedDiscussions() as $discussion ) {
            $rid = '';
            if ( is_object($discussion) && isset($discussion->resource_link_id) ) {
                $rid = (string) $discussion->resource_link_id;
            }
            if ( $rid !== '' && $rid === $resourceLinkId ) {
                $item = $discussion;
                break;
            }
        }
        if ( ! $item ) {
            return 0;
        }

        $sha = U::lti_sha256($resourceLinkId);
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie("SELECT link_id, deleted
            FROM {$p}lti_link
            WHERE context_id = :CID AND link_sha256 = :SHA",
            array(':CID' => $contextId, ':SHA' => $sha)
        );
        if ( ! is_array($row) ) {
            $title = (isset($item->title) && is_string($item->title)) ? $item->title : '';
            $PDOX->queryDie("INSERT INTO {$p}lti_link
                (link_key, link_sha256, title, context_id, path, created_at, updated_at)
                VALUES (:KEY, :SHA, :TITLE, :CID, :PATH, NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()",
                array(
                    ':KEY' => $resourceLinkId,
                    ':SHA' => $sha,
                    ':TITLE' => $title,
                    ':CID' => $contextId,
                    ':PATH' => 'tool/tdiscus/',
                )
            );
            $row = $PDOX->rowDie("SELECT link_id, deleted
                FROM {$p}lti_link
                WHERE context_id = :CID AND link_sha256 = :SHA",
                array(':CID' => $contextId, ':SHA' => $sha)
            );
        }
        if ( ! is_array($row) || intval($row['link_id']) < 1 ) {
            return 0;
        }
        $linkId = intval($row['link_id']);
        if ( intval($row['deleted']) !== 0 ) {
            $PDOX->queryDie("UPDATE {$p}lti_link
                SET deleted = 0, updated_at = NOW()
                WHERE link_id = :ID",
                array(':ID' => $linkId)
            );
        }
        return $linkId;
    }

    public static function instructor() {
        $rc = ReqScope::current();
        return $rc && $rc->user && (bool) $rc->user->instructor;
    }

    public static function discussionTitle() {
        $override = self::linkSetting('title', '');
        if ( is_string($override) && strlen($override) > 0 ) {
            return $override;
        }
        $rc = ReqScope::current();
        if ( $rc && $rc->link && is_string($rc->link->title) && $rc->link->title !== '' ) {
            return $rc->link->title;
        }
        return __('Discussion');
    }

    public static function linkSetting($key, $default = false) {
        global $LINK;

        $linkId = self::lid();
        if ( isset($LINK) && is_object($LINK) && isset($LINK->launch) && (int) $LINK->id === $linkId ) {
            return $LINK->settingsGet($key, $default);
        }
        $all = self::storedLinkSettings();
        if ( array_key_exists($key, $all) ) {
            $value = $all[$key];
            if ( is_string($value) && $value !== '' ) {
                return LTIX::decrypt_secret($value);
            }
        }
        return $default;
    }

    /**
     * @param array<string, mixed> $pairs
     */
    public static function saveLinkSettings(array $pairs) {
        global $CFG, $PDOX, $LINK;

        LTIX::getConnection();
        $linkId = self::lid();
        if ( isset($LINK) && is_object($LINK) && isset($LINK->launch) && (int) $LINK->id === $linkId ) {
            $LINK->settingsUpdate($pairs);
            return;
        }
        $all = self::storedLinkSettings();
        foreach ( $pairs as $k => $v ) {
            $all[$k] = $v;
        }
        $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_link
            SET settings = :JSON, updated_at = NOW()
            WHERE link_id = :ID",
            array(':JSON' => json_encode($all), ':ID' => $linkId)
        );
    }

    public static function maxDepth() {
        $maxdepth = self::linkSetting('maxdepth', '2');
        if ( ! is_string($maxdepth) && ! is_numeric($maxdepth) ) {
            return 2;
        }
        if ( strlen((string) $maxdepth) < 1 ) {
            return 2;
        }
        return intval($maxdepth);
    }

    public static function includeParticipatingInMainBadge() {
        return intval(self::linkSetting('badge_include_participating', '0')) > 0;
    }

    public static function includeParticipationAsPersonal() {
        return intval(self::linkSetting('badge_participation_personal', '0')) > 0;
    }

    public static function getPurifier() {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        return new \HTMLPurifier($config);
    }

    public static function creatorPremiumLevel() {
        global $TSUGI_LAUNCH;
        if ( ! isset($TSUGI_LAUNCH) || ! isset($TSUGI_LAUNCH->profile) ) {
            return 0;
        }
        return $TSUGI_LAUNCH->profile->getPremiumLevel();
    }

    public static function threadLoad($thread_id) {
        global $PDOX, $CFG;

        $row = $PDOX->rowDie("SELECT T.*,
            U.displayname AS displayname,
            COALESCE(UT.subscribe, 0) AS subscribe,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread AS UT
                ON UT.thread_id = T.thread_id AND UT.user_id = :UID
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_user AS O ON O.user_id = :UID
            WHERE T.link_id = :LID AND T.thread_id = :TID",
            array(':LID' => self::lid(), ':UID' => self::uid(), ':TID' => $thread_id)
        );
        return $row;
    }

    public static function threadLoadMarkRead($thread_id) {
        global $PDOX, $CFG;

        $row = self::threadLoad($thread_id);
        if ( ! $row ) {
            return $row;
        }

        self::ensureContextReadBaselineForUser(self::lid(), self::uid());

        $stmt = $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, read_at) VALUES
            (:TID, :UID, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()",
            array(
                ':TID' => $thread_id,
                ':UID' => self::uid(),
            )
        );

        $count = $stmt->rowCount();
        if ( $count == 1 ) {
            $staffread = "";
            if ( self::instructor() ) {
                $staffread = ", staffread=1";
            }
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET views=views+1 $staffread
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                )
            );
        }

        self::threadMarkAsReadForUserDao($row);
        return $row;
    }

    public static function threadMarkAsReadForUserDao($thread) {
        global $CFG, $PDOX;

        $thread_id = $thread['thread_id'];
        $thread_comments = $thread['comments'];
        $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, comments, read_at) VALUES
            (:TID, :UID, :COMMENTS, NOW())",
            array(
                ':TID' => $thread_id,
                ':UID' => self::uid(),
                ':COMMENTS' => $thread_comments,
            )
        );

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_user_thread
            SET comments=(SELECT comments from {$CFG->dbprefix}tdiscus_thread WHERE thread_id = :TID),
                read_at=NOW()
            WHERE thread_id = :TID AND user_id = :UID",
            array(
                ':TID' => $thread_id,
                ':UID' => self::uid(),
            )
        );
    }

    public static function threadLoadForUpdate($thread_id) {
        $row = self::threadLoad($thread_id);
        if ( ! is_array($row) ) {
            return null;
        }
        if ( $row['owned'] > 0 || self::instructor() ) {
            return $row;
        }
        return null;
    }

    public static function threadUpdate($thread_id, $data = false) {
        global $PDOX, $CFG;

        if ( $data == null ) {
            $data = $_POST;
        }
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');

        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        if ( ! is_array(self::threadLoadForUpdate($thread_id)) ) {
            return __('Could not load thread for update');
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread SET
            body = :BODY , title= :TITLE, updated_at = NOW(), edited=1
            WHERE link_id = :LID AND thread_id = :TID",
            array(
                ':LID' => self::lid(),
                ':TID' => $thread_id,
                ':TITLE' => $title,
                ':BODY' => $body,
            )
        );
        return null;
    }

    public static function threadDelete($thread_id) {
        global $PDOX, $CFG;

        $thread = self::threadLoadForUpdate($thread_id);
        if ( ! is_array($thread) ) {
            return __('Could not load thread for delete');
        }

        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_thread
            WHERE link_id = :LID AND thread_id = :TID",
            array(
                ':LID' => self::lid(),
                ':TID' => $thread_id,
            )
        );

        if ( $stmt->rowCount() == 0 ) {
            return __('Unable to delete thread');
        }
        return $stmt;
    }

    public static function threadSetBoolean($thread_id, $column, $value) {
        global $PDOX, $CFG;

        $valid_columns = array(
            'staffcreate', 'staffread', 'staffanswer', 'locked', 'hidden', 'pin',
        );

        if ( ! in_array($column, $valid_columns) ) {
            return __("Column $column not allowed");
        }
        if ( ! self::instructor() ) {
            return __('You must be an instructor to change this setting');
        }
        if ( $value != 1 && $value != 0 ) {
            return __("Column $column requires boolean (0 or 1)");
        }
        if ( ! is_numeric($thread_id) ) {
            return __('Incorrect or missing thread_id');
        }

        $thread = self::threadLoadForUpdate($thread_id);
        if ( ! is_array($thread) ) {
            return __('Could not load thread for update');
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread SET
            $column=:VALUE
            WHERE thread_id = :TID",
            array(
                ':VALUE' => $value,
                ':TID' => $thread_id,
            )
        );
        return null;
    }

    public static function threadsSortableBy() {
        return array('latest', 'unanswered', 'views', 'comments', 'earliest');
    }

    public static function threads($info = false) {
        global $CFG;

        if ( ! is_array($info) ) {
            $info = $_GET;
        }

        $order_by = "modified_at DESC, netvote DESC";
        $sort = U::get($info, "sort");
        if ( $sort == "latest" ) {
            $order_by = "modified_at DESC, netvote DESC";
        } else if ( $sort == "earliest" ) {
            $order_by = "modified_at ASC, netvote DESC";
        } else if ( $sort == "unanswered" ) {
            $order_by = "comments ASC, netvote DESC, modified_at DESC";
        } else if ( $sort == "views" ) {
            $order_by = "views DESC, comments DESC, netvote DESC, modified_at DESC";
        } else if ( $sort == "comments" ) {
            $order_by = "comments DESC, views DESC, netvote DESC, modified_at DESC";
        } else if ( $sort == "votes" ) {
            $order_by = "netvote DESC, comments DESC, views DESC, modified_at DESC";
        }

        $subst = array(':UID' => self::uid(), ':LID' => self::lid());
        $search = U::get($info, "search", "");
        $whereclause = "";
        if ( strlen(trim($search)) > 0 ) {
            $whereclause .= " AND (LOWER(title) LIKE LOWER(:SEARCH) OR LOWER(body) LIKE LOWER(:SEARCH)) ";
            $subst[':SEARCH'] = '%'.strtolower($search).'%';
        }
        if ( ! self::instructor() ) {
            $whereclause .= " AND (COALESCE(T.hidden, 0) = 0 ) ";
        }

        $fields = "
            T.thread_id AS thread_id, body, title, pin, T.views AS views, staffcreate,
            staffread, staffanswer, T.comments AS comments, UT.comments AS user_comments,
            displayname, T.premium AS premium, edited, hidden, locked,
            CONCAT(CONVERT_TZ(T.created_at, @@session.time_zone, '+00:00'), 'Z') AS created_at,
            CONCAT(CONVERT_TZ(T.updated_at, @@session.time_zone, '+00:00'), 'Z') AS updated_at,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            UT.subscribe AS subscribe, COALESCE(UT.favorite,0) AS favorite,
            (
                SELECT COUNT(DISTINCT C2.comment_id)
                FROM {$CFG->dbprefix}tdiscus_comment C2
                LEFT JOIN {$CFG->dbprefix}tdiscus_comment P2 ON P2.comment_id = C2.parent_id
                LEFT JOIN {$CFG->dbprefix}tdiscus_mention M2
                    ON M2.post_id = C2.comment_id AND M2.mentioned_user_id = :UID
                WHERE C2.thread_id = T.thread_id
                  AND C2.user_id <> :UID
                  AND C2.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')
                  AND (
                      P2.user_id = :UID
                      OR (T.user_id = :UID AND C2.parent_id > 0)
                      OR M2.mentioned_user_id IS NOT NULL
                  )
            ) AS personal_unread,
            (
                SELECT COUNT(DISTINCT C3.comment_id)
                FROM {$CFG->dbprefix}tdiscus_comment C3
                LEFT JOIN {$CFG->dbprefix}tdiscus_mention M3
                    ON M3.post_id = C3.comment_id AND M3.mentioned_user_id = :UID
                WHERE C3.thread_id = T.thread_id
                  AND C3.user_id <> :UID
                  AND C3.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')
                  AND M3.mentioned_user_id IS NOT NULL
            ) AS mention_unread,
            CASE
                WHEN (T.comments - COALESCE(UT.comments, 0)) > 0
                     AND COALESCE(UT.subscribe, 0) = 1
                THEN 1 ELSE 0
            END AS participating_unread
        ";

        $from = "
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread AS UT ON T.thread_id = UT.thread_id AND UT.user_id = :UID
            WHERE link_id = :LID $whereclause
            ORDER BY favorite DESC, T.pin DESC, T.rank_value DESC, $order_by
        ";

        return self::pagedQuery($fields, $from, $subst, $info);
    }

    public static function pagedQuery($fields, $from, $vars, $info = false) {
        global $PDOX;

        $retval = new \stdClass();
        $retval->more = false;
        $retval->next = -1;

        $start = intval(U::get($info, "start", 0));
        $pagesize = intval(U::get($info, "pagesize", self::default_page_size));

        if ( $pagesize == 0 ) {
            $sql = "SELECT ".$fields.$from;
            $rows = $PDOX->allRowsDie($sql, $vars);
            $retval->total = count($rows);
            $retval->rows = $rows;
            return $retval;
        }

        $paged_from = $from . " LIMIT $start, ".($pagesize+1);
        $sql = "SELECT ".$fields.$paged_from;
        $rows = $PDOX->allRowsDie($sql, $vars);

        $sql = "SELECT count(*) AS total ".$from;
        $pos = strpos($sql, "ORDER BY");
        if ( $pos > 0 ) {
            $sql = substr($sql, 0, $pos);
        }
        $row2 = $PDOX->rowDie($sql, $PDOX->limitVars($sql, $vars));
        $retval->total = intval($row2['total']);

        if ( count($rows) > 1 && count($rows) > $pagesize ) {
            unset($rows[$pagesize-1]);
            $retval->more = true;
            $retval->back = $start - $pagesize;
            $retval->next = $start + $pagesize;
        }
        $retval->rows = $rows;
        return $retval;
    }

    public static function threadInsert($data = null) {
        global $PDOX, $CFG;

        if ( $data == null ) {
            $data = $_POST;
        }
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');
        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        $staffcreate = self::instructor() ? 1 : 0;
        $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
            (link_id, user_id, staffcreate, title, body, premium, updated_at) VALUES
            (:LID, :UID, :STAFF, :TITLE, :BODY, :PREMIUM, NOW())",
            array(
                ':LID' => self::lid(),
                ':UID' => self::uid(),
                ':STAFF' => $staffcreate,
                ':TITLE' => $title,
                ':BODY' => $body,
                ':PREMIUM' => self::creatorPremiumLevel(),
            )
        );

        $thread_id = intval($PDOX->lastInsertId());
        if ( $thread_id > 0 ) {
            $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_user_thread
                (thread_id, user_id, subscribe)
                VALUES (:TID, :UID, 1)
                ON DUPLICATE KEY UPDATE subscribe = 1",
                array(
                    ':TID' => $thread_id,
                    ':UID' => self::uid(),
                )
            );
            self::upsertThreadParticipation($thread_id, self::uid());
        }
        return $thread_id;
    }

    public static function threadUserSetBoolean($thread_id, $column, $value) {
        global $CFG, $PDOX;

        $valid_columns = array('subscribe', 'favorite');
        if ( ! in_array($column, $valid_columns) ) {
            return __("Column $column not allowed");
        }
        if ( $value != 1 && $value != 0 ) {
            return __("Column $column requires boolean (0 or 1)");
        }
        if ( ! is_numeric($thread_id) ) {
            return __('Incorrect or missing thread_id');
        }

        $thread = self::threadLoad($thread_id);
        if ( ! is_array($thread) ) {
            return __('Could not load thread');
        }

        $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, $column) VALUES
            (:TID, :UID, :VALUE)
            ON DUPLICATE KEY UPDATE $column=:VALUE",
            array(
                ':TID' => $thread_id,
                ':UID' => self::uid(),
                ':VALUE' => $value,
            )
        );
        return null;
    }

    public static function commentSetBoolean($comment_id, $column, $value) {
        global $PDOX, $CFG;

        $valid_columns = array('locked', 'hidden');
        if ( ! in_array($column, $valid_columns) ) {
            return __("Column $column not allowed");
        }
        if ( ! self::instructor() ) {
            return __('You must be an instructor to change this setting');
        }
        if ( $value != 1 && $value != 0 ) {
            return __("Column $column requires boolean (0 or 1)");
        }
        if ( ! is_numeric($comment_id) ) {
            return __('Incorrect or missing comment_id');
        }

        $comment = self::commentLoadForUpdate($comment_id);
        if ( ! is_array($comment) ) {
            return __('Could not load comment for update');
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment SET
            $column=:VALUE
            WHERE comment_id = :CID",
            array(
                ':VALUE' => $value,
                ':CID' => $comment_id,
            )
        );
        return null;
    }

    public static function commentsSortableBy() {
        return array('most recent', 'earliest');
    }

    public static function comments($thread_id, $info = false, $parent_id = 0) {
        global $CFG;

        if ( ! is_array($info) ) {
            $info = $_GET;
        }

        $order_by = "modified_at DESC, depth";
        $sort = U::get($info, "sort");
        if ( $sort == "most recent" ) {
            $order_by = "modified_at DESC, depth";
        } else if ( $sort == "top" ) {
            $order_by = "netvote DESC, modified_at DESC, depth";
        } else if ( $sort == "earliest" ) {
            $order_by = "modified_at ASC";
        }

        $subst = array(
            ':UID' => self::uid(),
            ':LI' => self::lid(),
            ':TID' => $thread_id,
        );

        $search = U::get($info, "search", "");
        $whereclause = "";
        if ( ! self::instructor() ) {
            $whereclause .= " AND (COALESCE(C.hidden, 0) = 0 ) ";
        }
        if ( strlen(trim($search)) > 0 ) {
            $whereclause .= " AND (LOWER(comment) LIKE LOWER(:SEARCH)) ";
            $subst[':SEARCH'] = '%'.strtolower($search).'%';
        }

        $fields = "
            comment_id, comment, displayname, C.premium AS premium, C.edited AS edited, C.hidden AS hidden,
            C.locked AS locked, C.depth AS depth, C.children,
            CONCAT(CONVERT_TZ(C.created_at, @@session.time_zone, '+00:00'), 'Z') AS created_at,
            CONCAT(CONVERT_TZ(C.updated_at, @@session.time_zone, '+00:00'), 'Z') AS updated_at,
            CONCAT(CONVERT_TZ(COALESCE(C.updated_at, C.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            (COALESCE(C.upvote, 0)-COALESCE(C.downvote, 0)) AS netvote,
            CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
        ";

        $from = "
            FROM {$CFG->dbprefix}tdiscus_comment AS C
            JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
            WHERE T.link_id = :LI AND C.thread_id = :TID $whereclause
            ORDER BY $order_by
        ";
        return self::pagedQuery($fields, $from, $subst, $info);
    }

    public static function commentAddSubComment($thread_id, $parent_id, $comment) {
        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }

        $maxdepth = self::maxDepth();
        if ( $maxdepth < 1 ) {
            return __('Hierarchical comments not allowed');
        }
        if ( ! is_numeric($thread_id) || ! is_numeric($parent_id) ) {
            return __('thread_id and comment_id must be numeric');
        }

        $parent_id = intval($parent_id);
        $thread_id = intval($thread_id);

        $parent = self::commentLoad($parent_id);
        if ( is_string($parent) ) {
            return $parent;
        }
        if ( ! is_array($parent) ) {
            return __('Could not load comment').' '.$parent_id;
        }
        $thread = self::threadLoad($thread_id);
        if ( is_string($thread) ) {
            return $thread;
        }
        if ( ! is_array($thread) ) {
            return __('Could not load thread').' '.$thread_id;
        }
        if ( intval($parent['thread_id']) !== $thread_id ) {
            return __('Comment is not in this thread');
        }
        if ( intval($thread['locked']) && ! self::instructor() ) {
            return __('This thread is locked');
        }

        $parentDepth = $parent['depth'];
        if ( $parentDepth+2 > $maxdepth ) {
            return __('Comment depth exceeded');
        }
        return self::commentInsertDao($thread, $comment, $parent_id);
    }

    public static function commentInsertDao($thread, $comment, $parent_id = 0) {
        global $PDOX, $CFG;

        $thread_id = $thread['thread_id'];
        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }

        $maxdepth = self::maxDepth();
        if ( $maxdepth < 2 && $parent_id > 0 ) {
            return __('Hierarchical comments not allowed');
        }

        $parent_comment = false;
        if ( $parent_id > 0 ) {
            $parent_comment = self::commentLoad($parent_id);
            if ( ! is_array($parent_comment) ) {
                return __("Could not load comment");
            }
        }

        $depth = $parent_comment ? $parent_comment['depth']+1 : 0;
        $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_comment
            (thread_id, user_id, comment, parent_id, children, depth, premium) VALUES
            (:TH, :UID, :COM, :PARENT, 0, :DEPTH, :PREMIUM)",
            array(
                ':TH' => $thread_id,
                ':UID' => self::uid(),
                ':COM' => $comment,
                ':PARENT' => $parent_id,
                ':DEPTH' => $depth,
                ':PREMIUM' => self::creatorPremiumLevel(),
            )
        );

        $retval = intval($PDOX->lastInsertId());
        if ( $retval > 0 ) {
            $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_user_thread
                (thread_id, user_id, subscribe)
                VALUES (:TID, :UID, 1)
                ON DUPLICATE KEY UPDATE subscribe = 1",
                array(
                    ':TID' => $thread_id,
                    ':UID' => self::uid(),
                )
            );
            self::upsertThreadParticipation($thread_id, self::uid());
            self::syncMentionsForComment($retval, $comment, self::uid());
        }

        if ( $retval > 0 ) {
            $staffanswer = "";
            if ( self::instructor() ) {
                $staffanswer = "staffanswer=1, ";
            }
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET $staffanswer comments=(SELECT count(comment_id) FROM {$CFG->dbprefix}tdiscus_comment
                     WHERE thread_id = :TID), updated_at=NOW()
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                )
            );
        }

        self::threadMarkAsReadForUserDao($thread);

        if ( $retval > 0 ) {
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_user_thread
                SET notify=1
                WHERE thread_id = :TID AND subscribe = 1",
                array(
                    ':TID' => $thread_id,
                )
            );
        }

        if ( $retval > 0 ) {
            $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_closure
                (parent_id, child_id, depth)
                SELECT parent_id, :CID, depth FROM {$CFG->dbprefix}tdiscus_closure
                WHERE child_id = :PID
                UNION SELECT :CID, :CID, :DEPTH",
                array(':PID' => $parent_id, ':CID' => $retval, ':DEPTH' => $depth)
            );
        }

        if ( $retval > 0 && $parent_id > 0 ) {
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
                SET children=COALESCE(children, 0) + 1, updated_at=NOW()
                WHERE comment_id IN
                (
                    SELECT parent_id from {$CFG->dbprefix}tdiscus_closure
                    WHERE child_id = :PID
                )",
                array(
                    ':PID' => $parent_id,
                )
            );
        }

        return $retval;
    }

    public static function commentLoad($comment_id) {
        global $PDOX, $CFG;

        $row = $PDOX->rowDie("SELECT *,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_comment AS C
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
            JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
            WHERE link_id = :LID AND comment_id = :CID",
            array(':LID' => self::lid(), ':UID' => self::uid(), ':CID' => $comment_id)
        );
        return $row;
    }

    public static function commentLoadForUpdate($comment_id) {
        $row = self::commentLoad($comment_id);
        if ( ! is_array($row) ) {
            return null;
        }
        if ( $row['owned'] > 0 || self::instructor() ) {
            return $row;
        }
        return null;
    }

    public static function commentDelete($comment_id, $thread_id) {
        $comment = self::commentLoadForUpdate($comment_id);
        if ( ! is_array($comment) ) {
            return __('Could not load comment for delete');
        }
        $thread_id = $comment['thread_id'];
        return self::commentDeleteDao($comment, $thread_id);
    }

    public static function commentDeleteDao($comment, $thread_id) {
        global $PDOX, $CFG;

        $comment_id = $comment['comment_id'];
        $parent_id = $comment['parent_id'];

        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_comment
            WHERE comment_id IN (SELECT child_id FROM {$CFG->dbprefix}tdiscus_closure
                WHERE parent_id = :CID) AND comment_id != :CID",
            array(':CID' => $comment_id)
        );

        $sub_comments = $stmt->rowCount();

        $retval = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_comment
            WHERE comment_id = :CID",
            array(':CID' => $comment_id)
        );

        if ( $retval->rowCount() == 0 ) {
            return __('Unable to delete comment');
        }

        if ( $parent_id > 0 ) {
            $delta = $sub_comments + 1;
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
                SET children=CASE WHEN ((children - :DELTA) >= 0) THEN (children - :DELTA) ELSE 0 END
                WHERE comment_id = :PID",
                array(':DELTA' => $delta, ':PID' => $parent_id)
            );
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
            SET comments=(SELECT count(comment_id) FROM {$CFG->dbprefix}tdiscus_comment
                 WHERE thread_id = :TID)
            WHERE thread_id = :TID",
            array(
                ':TID' => $thread_id,
            )
        );

        return $retval;
    }

    public static function commentUpdateDao($old_comment, $comment) {
        global $PDOX, $CFG;

        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }

        $comment_id = $old_comment['comment_id'];
        $parent_id = $old_comment['parent_id'];

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment SET
            comment = :COM, updated_at = NOW()
            WHERE comment_id = :TID",
            array(
                ':TID' => $comment_id,
                ':COM' => $comment,
            )
        );

        self::syncMentionsForComment($comment_id, $comment, self::uid());

        $maxdepth = self::maxDepth();
        if ( $maxdepth > 1 && $parent_id > 0 ) {
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
                SET updated_at=NOW()
                WHERE comment_id IN
                (
                    SELECT parent_id from {$CFG->dbprefix}tdiscus_closure
                    WHERE child_id = :PID
                )",
                array(
                    ':PID' => $parent_id,
                )
            );
        }
        return null;
    }

    public static function unreadBadgeCounts() {
        global $PDOX, $CFG;

        $uid = self::uid();
        $lid = self::lid();
        if ( ! self::hasContextReadBaselineForUser($lid, $uid) ) {
            return array('personal' => 0, 'participating' => 0, 'global' => 0);
        }

        $extra_personal_clause = "";
        if ( self::includeParticipationAsPersonal() ) {
            $extra_personal_clause = " OR COALESCE(UT.subscribe, 0) = 1";
        }

        $personal_row = $PDOX->rowDie("SELECT COUNT(DISTINCT C.comment_id) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = C.thread_id AND UT.user_id = :UID
            LEFT JOIN {$CFG->dbprefix}tdiscus_comment P ON P.comment_id = C.parent_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_mention M
                ON M.post_id = C.comment_id AND M.mentioned_user_id = :UID
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE(UT.read_at, '1970-01-01 00:00:00')
              AND (
                    P.user_id = :UID
                    OR (T.user_id = :UID AND C.parent_id > 0)
                    OR M.mentioned_user_id IS NOT NULL
                    $extra_personal_clause
              )",
            array(':UID' => $uid, ':LID' => $lid)
        );

        $participating_row = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_thread T
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_thread UT
                ON UT.thread_id = T.thread_id AND UT.user_id = :UID
            WHERE T.link_id = :LID
              AND COALESCE(UT.subscribe, 0) = 1
              AND (T.comments - COALESCE(UT.comments, 0)) > 0",
            array(':UID' => $uid, ':LID' => $lid)
        );

        $global_row = $PDOX->rowDie("SELECT COUNT(*) AS count
            FROM {$CFG->dbprefix}tdiscus_comment C
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = C.thread_id
            WHERE T.link_id = :LID
              AND C.user_id <> :UID
              AND C.created_at > COALESCE((
                    SELECT MAX(UT2.read_at)
                    FROM {$CFG->dbprefix}tdiscus_user_thread UT2
                    JOIN {$CFG->dbprefix}tdiscus_thread T2 ON T2.thread_id = UT2.thread_id
                    WHERE UT2.user_id = :UID AND T2.link_id = :LID
              ), '1970-01-01 00:00:00')",
            array(':UID' => $uid, ':LID' => $lid)
        );

        return array(
            'personal' => intval($personal_row['count']),
            'participating' => intval($participating_row['count']),
            'global' => intval($global_row['count']),
        );
    }

    public static function mainBadgeCount($counts) {
        $main = intval($counts['personal']);
        if ( self::includeParticipatingInMainBadge() ) {
            $main = $main + intval($counts['participating']);
        }
        return $main;
    }

    public static function hasReadBaselineForCurrentContext() {
        return self::hasContextReadBaselineForUser(self::lid(), self::uid());
    }

    /**
     * @return array<string, mixed>
     */
    private static function storedLinkSettings() {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $row = $PDOX->rowDie("SELECT settings FROM {$CFG->dbprefix}lti_link WHERE link_id = :ID",
            array(':ID' => self::lid())
        );
        if ( ! is_array($row) || ! isset($row['settings']) || ! is_string($row['settings']) || $row['settings'] === '' ) {
            return array();
        }
        $decoded = json_decode($row['settings'], true);
        return is_array($decoded) ? $decoded : array();
    }

    private static function uid() {
        $rc = ReqScope::current();
        if ( ! $rc || ! $rc->user || (int) $rc->user->id < 1 ) {
            throw new \RuntimeException('Discussion request has no user');
        }
        return (int) $rc->user->id;
    }

    private static function lid() {
        $rc = ReqScope::current();
        if ( ! $rc || ! $rc->link || (int) $rc->link->id < 1 ) {
            throw new \RuntimeException('Discussion request has no link');
        }
        return (int) $rc->link->id;
    }

    private static function upsertThreadParticipation($thread_id, $user_id) {
        global $PDOX, $CFG;
        $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_user_thread_participation
            (thread_id, user_id, last_posted_at)
            VALUES (:TID, :UID, NOW())
            ON DUPLICATE KEY UPDATE last_posted_at = NOW()",
            array(
                ':TID' => $thread_id,
                ':UID' => $user_id,
            )
        );
    }

    private static function hasContextReadBaselineForUser($link_id, $user_id) {
        global $PDOX, $CFG;
        $row = $PDOX->rowDie("SELECT 1 AS present
            FROM {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_link L0 ON L0.context_id <=> L.context_id
            WHERE L0.link_id = :LID
              AND UT.user_id = :UID
              AND (UT.read_at IS NOT NULL OR COALESCE(UT.comments, 0) > 0)
            LIMIT 1",
            array(':LID' => $link_id, ':UID' => $user_id)
        );
        return is_array($row);
    }

    private static function ensureContextReadBaselineForUser($link_id, $user_id) {
        global $PDOX, $CFG;
        if ( self::hasContextReadBaselineForUser($link_id, $user_id) ) {
            return;
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_user_thread UT
            JOIN {$CFG->dbprefix}tdiscus_thread T ON T.thread_id = UT.thread_id
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_link L0 ON L0.context_id <=> L.context_id
            SET UT.read_at = NOW(),
                UT.comments = T.comments
            WHERE L0.link_id = :LID
              AND UT.user_id = :UID",
            array(':LID' => $link_id, ':UID' => $user_id)
        );

        $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, comments, read_at)
            SELECT T.thread_id, :UID, T.comments, NOW()
            FROM {$CFG->dbprefix}tdiscus_thread T
            JOIN {$CFG->dbprefix}lti_link L ON L.link_id = T.link_id
            JOIN {$CFG->dbprefix}lti_link L0 ON L0.context_id <=> L.context_id
            WHERE L0.link_id = :LID
              AND NOT EXISTS (
                SELECT 1
                FROM {$CFG->dbprefix}tdiscus_user_thread UT
                WHERE UT.thread_id = T.thread_id
                  AND UT.user_id = :UID2
              )",
            array(':LID' => $link_id, ':UID' => $user_id, ':UID2' => $user_id)
        );
    }

    private static function syncMentionsForComment($comment_id, $comment_text, $author_user_id) {
        global $PDOX, $CFG;

        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_mention WHERE post_id = :PID",
            array(':PID' => $comment_id)
        );

        $mentioned_user_ids = self::extractMentionedUserIds($comment_text);
        foreach ( $mentioned_user_ids as $mentioned_user_id ) {
            if ( $mentioned_user_id == $author_user_id ) {
                continue;
            }
            $row = $PDOX->rowDie("SELECT user_id
                FROM {$CFG->dbprefix}lti_user
                WHERE user_id = :UID",
                array(':UID' => $mentioned_user_id)
            );
            if ( ! is_array($row) ) {
                continue;
            }
            $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_mention
                (post_id, mentioned_user_id, created_at)
                VALUES (:PID, :UID, NOW())",
                array(
                    ':PID' => $comment_id,
                    ':UID' => $mentioned_user_id,
                )
            );
        }
    }

    private static function extractMentionedUserIds($comment_text) {
        $mentions = array();
        if ( ! is_string($comment_text) || strlen($comment_text) < 2 ) {
            return $mentions;
        }
        preg_match_all('/@([0-9]{1,11})\b/', $comment_text, $matches);
        if ( ! isset($matches[1]) || ! is_array($matches[1]) ) {
            return $mentions;
        }
        foreach ( $matches[1] as $raw_uid ) {
            $uid = intval($raw_uid);
            if ( $uid > 0 ) {
                $mentions[$uid] = $uid;
            }
        }
        return array_values($mentions);
    }

}
