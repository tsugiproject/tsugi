<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Threads {

    const default_page_size = 20;
    // const default_page_size = 3;

    public static function maxDepth() {
        $maxdepth = Settings::linkGet('maxdepth', '2');
        if ( strlen($maxdepth) < 1 ) return 2;
        return intval($maxdepth);
    }

    public static function getPurifier()
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $purifier = new \HTMLPurifier($config);
        return $purifier;
    }

    public static function threadLoad($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $row = $PDOX->rowDie("SELECT *,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_user AS O ON O.user_id = :UID
            WHERE link_id = :LID AND thread_id = :TID",
            array(':LID' => $TSUGI_LAUNCH->link->id,  ':UID' => $TSUGI_LAUNCH->user->id, ':TID' => $thread_id)
        );
        return $row;
    }

    public static function threadLoadMarkRead($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        // This also makes sure we can see the thread_id
        $row = self::threadLoad($thread_id);
        if ( ! $row ) return $row;

        $stmt = $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, read_at) VALUES
            (:TID, :UID, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()",
            array(
                ':TID' => $thread_id,
                ':UID' => $TSUGI_LAUNCH->user->id,
            )
        );

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the row
        // is inserted as a new row, 2 if an existing row is updated, and 0 if an existing
        // row is set to its current value
        // https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
        $count = $stmt->rowCount();
        if ( $count == 1 ) {
            $staffread = "";
            if ( $TSUGI_LAUNCH->user->instructor ) $staffread = ", staffread=1";
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET views=views+1 $staffread
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                )
            );
        }

        // Catch this user up
        self::threadMarkAsReadForUserDao($row);

       return $row;
    }

    // Record how many comments were on the thread when this user read the thread
    public static function threadMarkAsReadForUserDao($thread) {
        global $CFG, $PDOX, $TSUGI_LAUNCH;

        $thread_id = $thread['thread_id'];
        $thread_comments = $thread['comments'];
        if ( $thread_comments > 0 ) {
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_user_thread
                SET comments=(SELECT comments from {$CFG->dbprefix}tdiscus_thread WHERE thread_id = :TID)
                WHERE thread_id = :TID AND user_id = :UID",
                array(
                    ':COMMENTS' => $thread_comments,
                    ':TID' => $thread_id,
                    ':UID' => $TSUGI_LAUNCH->user->id,
                )
            );
        }
    }

    public static function threadLoadForUpdate($thread_id) {
        global $TSUGI_LAUNCH;
        $row = self::threadLoad($thread_id);
        if ( $row['owned'] > 0 || $TSUGI_LAUNCH->user->instructor ) return $row;
        return null;
    }

    public static function threadUpdate($thread_id, $data=false) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        if ( $data == null ) $data = $_POST;
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');

        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        // TODO: Purify pre-update
        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread SET
            body = :BODY , title= :TITLE, updated_at = NOW(), edited=1
            WHERE link_id = :LID AND thread_id = :TID AND user_id = :UID",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':TID' => $thread_id,
                ':TITLE' => $title,
                ':BODY' => $body
            )
        );
    }

    public static function threadDelete($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $thread = self::threadLoadForUpdate($thread_id);

        if ( ! is_array($thread) ) {
            return __('Could not load thread for delete');
        }

        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_thread
            WHERE link_id = :LID AND thread_id = :TID",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':TID' => $thread_id,
            )
        );

        if ( $stmt->rowCount() == 0 ) {
            return __('Unable to delete thread');
        }
        return $stmt;
    }

    public static function threadSetBoolean($thread_id, $column, $value) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $valid_columns = array(
            'staffcreate', 'staffread', 'staffanswer', 'locked', 'hidden', 'pin'
        );

        if ( ! in_array($column, $valid_columns) ) {
            return __("Column $column not allowed");
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
    }

    public static function threadsSortableBy() {
        return array('latest', 'unanswered', 'views', 'comments', /* 'votes', */ 'earliest');
    }

    public static function threads($info=false) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        if ( ! is_array($info) ) $info = $_GET;

        // Default is latest
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

        $subst = array(':UID' => $TSUGI_LAUNCH->user->id, ':LID' => $TSUGI_LAUNCH->link->id);

        $search = U::get($info, "search", "");

        $whereclause = "";
        if ( strlen(trim($search)) > 0 ) {
            $whereclause = " AND (LOWER(title) LIKE LOWER(:SEARCH) OR LOWER(body) LIKE LOWER(:SEARCH)) ";
            $subst[':SEARCH'] = '%'.strtolower($search).'%';
        }

        if ( ! $TSUGI_LAUNCH->user->instructor ) {
            $whereclause = " AND (COALESCE(hidden, 0) = 0 ) ";
        }

        $fields = "
            T.thread_id AS thread_id, body, title, pin, T.views AS views, staffcreate,
            staffread, staffanswer, T.comments AS comments, UT.comments AS user_comments,
            displayname, edited, hidden, locked,
            CONCAT(CONVERT_TZ(T.created_at, @@session.time_zone, '+00:00'), 'Z') AS created_at,
            CONCAT(CONVERT_TZ(T.updated_at, @@session.time_zone, '+00:00'), 'Z') AS updated_at,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            UT.subscribe AS subscribe, COALESCE(UT.favorite,0) AS favorite
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

    // https://dev.mysql.com/doc/refman/8.0/en/information-functions.html#function_found-rows
    public static function pagedQuery($fields, $from, $vars, $info=false)
    {
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
            return $retval;
        }

        // Retrieve one extra to see if there are more available
        $paged_from = $from . " LIMIT $start, ".($pagesize+1);
        $sql = "SELECT ".$fields.$paged_from;
        $rows = $PDOX->allRowsDie($sql, $vars);

        // https://stackoverflow.com/questions/186588/which-is-fastest-select-sql-calc-found-rows-from-table-or-select-count
        $sql = "SELECT count(*) AS total ".$from;
        $pos = strpos($sql, "ORDER BY");
        if ( $pos > 0 ) $sql = substr($sql, 0, $pos);
        $row2 = $PDOX->rowDie($sql, $PDOX->limitVars($sql, $vars));
        $retval->total = intval($row2['total']);

        // Remove that extra row and indicate there is more to go
        if ( count($rows) > 1 && count($rows) > $pagesize) {
            unset($rows[$pagesize-1]);
            $retval->more = true;
            $retval->back = $start - $pagesize;
            $retval->next = $start + $pagesize;
        }
        $retval->rows = $rows;

        return $retval;
    }

    public static function threadInsert($data=null) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        if ( $data == null ) $data = $_POST;
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');

        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        $staffcreate = $TSUGI_LAUNCH->user->instructor ? 1 : 0;
        // TODO: Purify pre-insert?
        $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
            (link_id, user_id, staffcreate, title, body, updated_at) VALUES
            (:LID, :UID, :STAFF, :TITLE, :BODY, NOW())",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':STAFF' => $staffcreate,
                ':TITLE' => $title,
                ':BODY' => $body
            )
        );

        return intval($PDOX->lastInsertId());
    }

    public static function threadUserSetBoolean($thread_id, $column, $value)
    {
        global $CFG, $PDOX, $TSUGI_LAUNCH;

        $valid_columns = array(
            'subscribe', 'favorite'
        );

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

        $stmt = $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, $column) VALUES
            (:TID, :UID, :VALUE)
            ON DUPLICATE KEY UPDATE $column=:VALUE",
            array(
                ':TID' => $thread_id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':VALUE' => $value,
            )
        );

    }

    public static function commentSetBoolean($comment_id, $column, $value) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $valid_columns = array(
            'locked', 'hidden'
        );

        if ( ! in_array($column, $valid_columns) ) {
            return __("Column $column not allowed");
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
    }

    public static function commentsSortableBy() {
        return array('most recent', /* 'top', put back if voting */ 'earliest');
    }

    public static function comments($thread_id, $info=false, $parent_id=0) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        if ( ! is_array($info) ) $info = $_GET;

        // Default is most recent
        $order_by = "modified_at DESC, depth";
        $sort = U::get($info, "sort");
        if ( $sort == "most recent" ) {
            $order_by = "modified_at DESC, depth";
        } else if ( $sort == "top" ) {
            $order_by = "netvote DESC, modified_at DESC, depth";
        } else if ( $sort == "earliest" ) {
            $order_by = "modified_at ASC";
        }

        $subst =  array(
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':LI' => $TSUGI_LAUNCH->link->id,
                ':TID' => $thread_id,
         //     ':PID' => $parent_id,
        );

        $search = U::get($info, "search", "");

        $whereclause = "";
        if ( ! $TSUGI_LAUNCH->user->instructor ) {
            $whereclause = " AND (COALESCE(C.hidden, 0) = 0 ) ";
        }

        if ( strlen(trim($search)) > 0 ) {
            $whereclause = " AND (LOWER(comment) LIKE LOWER(:SEARCH)) ";
            $subst[':SEARCH'] = '%'.strtolower($search).'%';
        }

        $fields = "
            comment_id, comment, displayname, C.edited AS edited, C.hidden AS hidden,
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
        //    WHERE COALESCE(C.parent_id, 0) = :PID AND T.link_id = :LI AND C.thread_id = :TID $whereclause
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
        if ( is_string($parent) ) return $parent;
        if ( !is_array($parent) ) return __('Could not load comment').' '.$parent_id;
        $thread = self::threadLoad($thread_id);
        if ( is_string($thread) ) return $thread;
        if ( !is_array($thread) ) return __('Could not load thread').' '.$thread_id;

        $parentDepth = $parent['depth'];
        if ( $parentDepth+2 > $maxdepth ) {
            return __('Comment depth exceeded');
        }

        $retval = self::commentInsertDao($thread, $comment, $parent_id);

        return $retval;

    }

    public static function commentInsertDao($thread, $comment, $parent_id=0) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $thread_id = $thread['thread_id'];

        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }

        $maxdepth = self::maxDepth();
        if ( $maxdepth < 2 && $parent_id > 0 ) {
            return __('Hierarchical comments not allowed');
        }

        // Replying to a comment - hierarchy
        $parent_comment = false;
        if ( $parent_id > 0 ) {
            $parent_comment = self::commentLoad($parent_id);
            if ( ! is_array($parent_comment) ) return __("Could not load comment");
        }

        $depth = $parent_comment ? $parent_comment['depth']+1 : 0;

        $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_comment
            (thread_id, user_id, comment, parent_id, children, depth) VALUES
            (:TH, :UID, :COM, :PARENT, 0, :DEPTH)",
            array(
                ':TH' => $thread_id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':COM' => $comment,
                ':PARENT' => $parent_id,
                ':DEPTH' => $depth,
            )
        );

        $retval = intval($PDOX->lastInsertId());

        // Update the thread
        if ( $retval > 0 ) {
            $staffanswer = "";
            if ( $TSUGI_LAUNCH->user->instructor ) $staffanswer = "staffanswer=1, ";

            // A little denormalization saves a JOIN / COUNT / GROUP BY and makes sorting super fast
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET $staffanswer comments=(SELECT count(comment_id) FROM {$CFG->dbprefix}tdiscus_comment
                     WHERE thread_id = :TID), updated_at=NOW()
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                 )
            );
        }

        // Mark thread as read for user - Make sure to do this after the comment count is updated above
        self::threadMarkAsReadForUserDao($thread);

        // Notify subscribed users
        // TODO: Give this a checkbox in settings
        if ( $retval > 0 ) {
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_user_thread
                SET notify=1
                WHERE thread_id = :TID AND subscribe = 1",
                array(
                    ':TID' => $thread_id,
                 )
            );
        }

        // Up the tree we go...
        // https://www.slideshare.net/billkarwin/models-for-hierarchical-data

        if ( $retval > 0 ) {
            $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_closure
                (parent_id, child_id, depth)
                SELECT parent_id, :CID, depth FROM {$CFG->dbprefix}tdiscus_closure
                WHERE child_id = :PID
                UNION SELECT :CID, :CID, :DEPTH",
                array(':PID' => $parent_id, ':CID' => $retval, ':DEPTH' => $depth)
            );
        }

        // From the parent on up - they get an additional child node
        // Yeah it is a sub-select - but it is no more than maxdepth...
        if ( $retval > 0 && $parent_id > 0 ) {
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
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
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $row = $PDOX->rowDie("SELECT *,
            CONCAT(CONVERT_TZ(COALESCE(T.updated_at, T.created_at), @@session.time_zone, '+00:00'), 'Z') AS modified_at,
            CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_comment AS C
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
            JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
            WHERE link_id = :LID AND comment_id = :CID",
            array(':LID' => $TSUGI_LAUNCH->link->id,  ':UID' => $TSUGI_LAUNCH->user->id, ':CID' => $comment_id)
        );
        return $row;
    }

    public static function commentLoadForUpdate($comment_id) {
        global $TSUGI_LAUNCH;

        $row = self::commentLoad($comment_id);
        if ( ! is_array($row) ) return null;
        if ( $row['owned'] > 0 || $TSUGI_LAUNCH->user->instructor ) return $row;

        return null;
    }

    public static function commentDelete($comment_id, $thread_id) {
        $comment = self::commentLoadForUpdate($comment_id);

        if ( ! is_array($comment) ) {
            return __('Could not load comment for delete');
        }

        $thread_id = $comment['thread_id'];
        $retval = self::commentDeleteDao($comment, $thread_id);

        return $retval;
    }

    public static function commentDeleteDao($comment, $thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $comment_id = $comment['comment_id'];
        $parent_id = $comment['parent_id'];

        // Delete the children of this comment if there are any
        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_comment
            WHERE comment_id IN (SELECT child_id FROM {$CFG->dbprefix}tdiscus_closure
                WHERE parent_id = :CID) AND comment_id != :CID",
            array(':CID' => $comment_id)
        );

        $sub_comments = $stmt->rowCount();
        error_log("Deleting comment $comment_id had $sub_comments sub comments");

        // Delete the actual comment - afterwards to keep the tdiscuss_closure
        // rows from vanishing due to referential integrity - they will be auto-deleted
        // here
        $retval = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_comment
            WHERE comment_id = :CID",
            array(':CID' => $comment_id)
        );

        if ( $retval->rowCount() == 0 ) {
            return __('Unable to delete comment');
        }

        // Update the children count of the parent
        if ( $parent_id > 0 ) {
            $delta = $sub_comments + 1;  //This comment
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
                SET children=CASE WHEN ((children - :DELTA) >= 0) THEN (children - :DELTA) ELSE 0 END
                WHERE comment_id = :PID",
                array(':DELTA' => $delta, ':PID' => $parent_id)
            );
            error_log("Removed $delta from children count of thread $parent_id");
        }

        // A little denormalization saves a COUNT / GROUP BY and makes sorting super fast
        $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
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
        global $PDOX, $TSUGI_LAUNCH, $CFG;
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

        // If we are updating a thread - update all its parent threads u the tree
        $maxdepth = self::maxDepth();
        if ( $maxdepth > 1 & $parent_id > 0 ) {
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment
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

    }

}
