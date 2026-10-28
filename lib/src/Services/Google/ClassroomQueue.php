<?php

namespace Tsugi\Services\Google;

use \Tsugi\Core\LTIX;

/**
 * Classroom grades waiting on the teacher, and the log of attempts.
 *
 * The queue is one row per result. A successful send deletes that row.
 * Reset clears the error and leaves the row. It does not call Google.
 * The log is append-only. Reset does not remove log rows.
 */
class ClassroomQueue {

    const STATE_PENDING = 'pending';
    const STATE_ERROR = 'error';

    const KIND_SCORE = 'score';
    const KIND_COURSEWORK = 'coursework_create';
    const KIND_RECONNECT = 'reconnect';
    const KIND_DUE = 'due_date';

    /**
     * Record a score that did not reach Classroom. The local grade stays.
     *
     * @param array<string,mixed> $row
     * @param string $message
     * @param int|null $http
     * @param string $url
     * @param string|null $request
     */
    public static function fail(array $row, $message, $http, $url, $request) {
        $message = self::clip($message, 500);
        if ( $message === '' ) {
            $message = 'The score was not sent.';
        }
        self::markResult($row, null, $message);
        self::upsert($row, self::STATE_ERROR, $message);
        self::writeLog($row, self::KIND_SCORE, false, $http, $url, $request, $message);
    }

    /**
     * The patch was accepted. Stamp the result Sent and the server time,
     * then drop the queue row.
     *
     * @param array<string,mixed> $row
     * @param int|null $http
     * @param string $url
     * @param string|null $request
     */
    public static function succeed(array $row, $http, $url, $request) {
        $grade = isset($row['grade']) ? (float) $row['grade'] : null;
        self::markResult($row, $grade, 'Sent');
        self::deleteQueueRow($row);
        self::writeLog($row, self::KIND_SCORE, true, $http, $url, $request, 'Sent');
    }

    /**
     * Coursework create, reconnect, or any non-score attempt.
     *
     * @param int $contextId
     * @param int $linkId
     * @param int $userId
     * @param string $kind
     * @param bool $success
     * @param string $detail
     * @param int|null $http
     * @param string $url
     * @param string|null $request
     */
    public static function note($contextId, $linkId, $userId, $kind, $success, $detail, $http = null, $url = '', $request = null) {
        self::writeLog(array(
            'context_id' => (int) $contextId,
            'link_id' => (int) $linkId,
            'user_id' => (int) $userId,
            'result_id' => 0,
        ), $kind, $success, $http, $url, $request, $detail);
    }

    /**
     * Last refresh error for this teacher. Null clears it and stamps the check.
     *
     * @param int $userId
     * @param string|null $error
     */
    public static function connection($userId, $error) {
        if ( (int) $userId < 1 ) {
            return;
        }
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            if ( $error === null ) {
                $PDOX->queryReturnError(
                    "UPDATE {$CFG->dbprefix}lti_user
                     SET gc_status = NULL, gc_checked_at = NOW(), updated_at = NOW()
                     WHERE user_id = :UID",
                    array(':UID' => (int) $userId)
                );
                return;
            }
            $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_user
                 SET gc_status = :ST, updated_at = NOW()
                 WHERE user_id = :UID",
                array(':UID' => (int) $userId, ':ST' => self::clip($error, 500))
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom connection status user='.(int) $userId.' '.$e->getMessage());
        }
    }

    /**
     * @param int $userId
     * @return array{connected:bool,status:string,checked_at:string}
     */
    public static function connectionStatus($userId) {
        $empty = array('connected' => false, 'status' => '', 'checked_at' => '');
        if ( (int) $userId < 1 ) {
            return $empty;
        }
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $row = $PDOX->rowDie(
                "SELECT gc_token, gc_status, gc_checked_at
                 FROM {$CFG->dbprefix}lti_user
                 WHERE user_id = :UID LIMIT 1",
                array(':UID' => (int) $userId)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom connection read user='.(int) $userId.' '.$e->getMessage());
            return $empty;
        }
        if ( ! is_array($row) ) {
            return $empty;
        }
        $token = trim((string) ($row['gc_token'] ?? ''));
        $status = trim((string) ($row['gc_status'] ?? ''));
        return array(
            'connected' => $token !== '' && $status === '',
            'status' => $status,
            'checked_at' => trim((string) ($row['gc_checked_at'] ?? '')),
        );
    }

    /**
     * @param int $contextId
     * @return array<int,array<string,mixed>>
     */
    public static function queueForContext($contextId) {
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $p = $CFG->dbprefix;
            $rows = $PDOX->allRowsDie(
                "SELECT Q.gclass_queue_id, Q.grade, Q.state, Q.detail, Q.updated_at, Q.created_at,
                        L.title AS link_title, U.displayname
                 FROM {$p}lti_gclass_queue AS Q
                 LEFT JOIN {$p}lti_link AS L ON L.link_id = Q.link_id
                 LEFT JOIN {$p}lti_user AS U ON U.user_id = Q.user_id
                 WHERE Q.context_id = :CID
                 ORDER BY Q.gclass_queue_id DESC",
                array(':CID' => (int) $contextId)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom queue read context='.(int) $contextId.' '.$e->getMessage());
            return array();
        }
        return is_array($rows) ? $rows : array();
    }

    /**
     * Errors first, then successful attempts. Each list is the newest 20.
     *
     * @param int $contextId
     * @return array{failures:array<int,array<string,mixed>>,successes:array<int,array<string,mixed>>}
     */
    public static function logForContext($contextId) {
        return array(
            'failures' => self::logRows($contextId, 0),
            'successes' => self::logRows($contextId, 1),
        );
    }

    /**
     * Clear errors on this course's queue. Does not call Google and does not delete the log.
     *
     * @param int $contextId
     * @return int
     */
    public static function reset($contextId) {
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_gclass_queue
                 SET state = :PENDING, detail = NULL, updated_at = NOW()
                 WHERE context_id = :CID AND state = :ERROR",
                array(
                    ':PENDING' => self::STATE_PENDING,
                    ':ERROR' => self::STATE_ERROR,
                    ':CID' => (int) $contextId,
                )
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom queue reset context='.(int) $contextId.' '.$e->getMessage());
            return 0;
        }
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            return 0;
        }
        return (int) $stmt->rowCount();
    }

    /**
     * Rows still waiting, including ones whose last send failed.
     *
     * @param int $contextId
     * @return array<int,array<string,mixed>>
     */
    public static function rowsToSend($contextId) {
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $rows = $PDOX->allRowsDie(
                "SELECT Q.result_id, Q.context_id, Q.link_id, Q.user_id, Q.owner_id,
                        Q.grade, Q.gc_course, Q.gc_coursework, Q.gc_submit_id
                 FROM {$CFG->dbprefix}lti_gclass_queue AS Q
                 WHERE Q.context_id = :CID
                 ORDER BY Q.gclass_queue_id",
                array(':CID' => (int) $contextId)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom queue send list context='.(int) $contextId.' '.$e->getMessage());
            return array();
        }
        return is_array($rows) ? $rows : array();
    }

    /**
     * Classroom courses this teacher installed.
     *
     * @param int $userId
     * @return array<int,array<string,mixed>>
     */
    public static function coursesForOwner($userId) {
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $p = $CFG->dbprefix;
            $rows = $PDOX->allRowsDie(
                "SELECT C.context_id, C.title, C.context_key, C.login_at,
                        (SELECT COUNT(*) FROM {$p}lti_gclass_queue AS Q
                         WHERE Q.context_id = C.context_id) AS waiting
                 FROM {$p}lti_context AS C
                 WHERE C.user_id = :UID
                   AND C.gc_secret IS NOT NULL AND C.gc_secret <> ''
                   AND (C.deleted IS NULL OR C.deleted = 0)
                 ORDER BY C.login_at DESC, C.context_id DESC",
                array(':UID' => (int) $userId)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom course list user='.(int) $userId.' '.$e->getMessage());
            return array();
        }
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param int $contextId
     * @param int $success
     * @return array<int,array<string,mixed>>
     */
    private static function logRows($contextId, $success) {
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $p = $CFG->dbprefix;
            $rows = $PDOX->allRowsDie(
                "SELECT G.gclass_log_id, G.created_at, G.kind, G.http_response, G.url,
                        G.request, G.detail, L.title AS link_title, U.displayname
                 FROM {$p}lti_gclass_log AS G
                 LEFT JOIN {$p}lti_link AS L ON L.link_id = G.link_id
                 LEFT JOIN {$p}lti_user AS U ON U.user_id = G.user_id
                 WHERE G.context_id = :CID AND G.success = :OK
                 ORDER BY G.gclass_log_id DESC
                 LIMIT 20",
                array(':CID' => (int) $contextId, ':OK' => (int) $success)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom log read context='.(int) $contextId.' '.$e->getMessage());
            return array();
        }
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param array<string,mixed> $row
     * @param float|null $grade
     * @param string|null $error
     */
    private static function markResult(array $row, $grade, $error) {
        $resultId = (int) ($row['result_id'] ?? 0);
        if ( $resultId < 1 ) {
            return;
        }
        global $CFG;
        $sets = array('retrieved_at = NOW()', 'updated_at = NOW()', 'server_status = :ST');
        $params = array(
            ':RID' => $resultId,
            ':ST' => $error,
        );
        if ( $grade !== null ) {
            $sets[] = 'server_grade = :SG';
            $params[':SG'] = $grade;
        }
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_result
                 SET ".implode(', ', $sets)."
                 WHERE result_id = :RID",
                $params
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom result status result='.$resultId.' '.$e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $row
     * @param string $state
     * @param string $detail
     */
    private static function upsert(array $row, $state, $detail) {
        $resultId = (int) ($row['result_id'] ?? 0);
        $contextId = (int) ($row['context_id'] ?? 0);
        $ownerId = (int) ($row['owner_id'] ?? 0);
        if ( $resultId < 1 || $contextId < 1 || $ownerId < 1 ) {
            return;
        }
        global $CFG;
        $linkId = (int) ($row['link_id'] ?? 0);
        $userId = (int) ($row['user_id'] ?? 0);
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->queryReturnError(
                "INSERT INTO {$CFG->dbprefix}lti_gclass_queue
                    (context_id, link_id, user_id, result_id, owner_id, grade,
                     gc_course, gc_coursework, gc_submit_id, state, detail, created_at, updated_at)
                 VALUES
                    (:CID, :LID, :UID, :RID, :OID, :GRADE,
                     :COURSE, :WORK, :SUB, :STATE, :DETAIL, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    grade = VALUES(grade),
                    gc_course = VALUES(gc_course),
                    gc_coursework = VALUES(gc_coursework),
                    gc_submit_id = VALUES(gc_submit_id),
                    state = VALUES(state),
                    detail = VALUES(detail),
                    updated_at = NOW()",
                array(
                    ':CID' => $contextId,
                    ':LID' => $linkId > 0 ? $linkId : null,
                    ':UID' => $userId > 0 ? $userId : null,
                    ':RID' => $resultId,
                    ':OID' => $ownerId,
                    ':GRADE' => isset($row['grade']) ? (float) $row['grade'] : null,
                    ':COURSE' => self::clip((string) ($row['gc_course'] ?? ''), 64),
                    ':WORK' => (string) ($row['gc_coursework'] ?? ''),
                    ':SUB' => (string) ($row['gc_submit_id'] ?? ''),
                    ':STATE' => $state,
                    ':DETAIL' => $detail,
                )
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom queue upsert result='.$resultId.' '.$e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function deleteQueueRow(array $row) {
        $resultId = (int) ($row['result_id'] ?? 0);
        if ( $resultId < 1 ) {
            return;
        }
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->queryReturnError(
                "DELETE FROM {$CFG->dbprefix}lti_gclass_queue WHERE result_id = :RID",
                array(':RID' => $resultId)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom queue delete result='.$resultId.' '.$e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $row
     * @param string $kind
     * @param bool $success
     * @param int|null $http
     * @param string $url
     * @param string|null $request
     * @param string $detail
     */
    private static function writeLog(array $row, $kind, $success, $http, $url, $request, $detail) {
        $contextId = (int) ($row['context_id'] ?? 0);
        if ( $contextId < 1 ) {
            return;
        }
        $linkId = (int) ($row['link_id'] ?? 0);
        $userId = (int) ($row['user_id'] ?? 0);
        $resultId = (int) ($row['result_id'] ?? 0);
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->queryReturnError(
                "INSERT INTO {$CFG->dbprefix}lti_gclass_log
                    (context_id, link_id, user_id, result_id, kind, success,
                     http_response, url, request, detail, created_at)
                 VALUES
                    (:CID, :LID, :UID, :RID, :KIND, :OK,
                     :HTTP, :URL, :REQ, :DETAIL, NOW())",
                array(
                    ':CID' => $contextId,
                    ':LID' => $linkId > 0 ? $linkId : null,
                    ':UID' => $userId > 0 ? $userId : null,
                    ':RID' => $resultId > 0 ? $resultId : null,
                    ':KIND' => self::clip($kind, 32),
                    ':OK' => $success ? 1 : 0,
                    ':HTTP' => $http,
                    ':URL' => self::scrub((string) $url),
                    ':REQ' => $request === null ? null : self::scrub($request),
                    ':DETAIL' => self::scrub(self::clip($detail, 4000)),
                )
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom log context='.$contextId.' '.$e->getMessage());
        }
    }

    /**
     * @param string $text
     * @param int $limit
     */
    private static function clip($text, $limit) {
        $text = trim((string) $text);
        if ( strlen($text) > $limit ) {
            return substr($text, 0, $limit);
        }
        return $text;
    }

    /**
     * Resource ids in this course that already have a Classroom link.
     * The mirror link lives in the same course. Its key is the coursework id,
     * and its path points at the lesson resource.
     *
     * @param int $contextId
     * @return array<string,string>
     */
    public static function installsInCourse($contextId) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        if ( (int) $contextId < 1 ) {
            return array();
        }
        $rows = $PDOX->allRowsDie(
            "SELECT L.path AS path, L.link_key AS link_key, L.gc_coursework AS gc_coursework
             FROM {$CFG->dbprefix}lti_link L
             WHERE L.context_id = :CID
               AND L.deleted = 0
               AND L.path IS NOT NULL AND L.path <> ''",
            array(':CID' => (int) $contextId)
        );
        $found = array();
        if ( ! is_array($rows) ) {
            return $found;
        }
        foreach ( $rows as $row ) {
            $path = (string) ($row['path'] ?? '');
            $key = (string) ($row['link_key'] ?? '');
            $work = trim((string) ($row['gc_coursework'] ?? ''));
            if ( $work !== '' && $key !== '' ) {
                $found[$key] = '';
            }
            $id = self::inheritId($path);
            if ( $id === '' ) {
                $id = self::launchResourceId($path);
            }
            if ( $id === '' || $id === $key ) {
                continue;
            }
            $found[$id] = '';
        }
        return $found;
    }

    /**
     * Resource id at the end of a course discussion or lessons launch path.
     */
    private static function launchResourceId($path) {
        $pathOnly = parse_url($path, PHP_URL_PATH);
        if ( ! is_string($pathOnly) || $pathOnly === '' ) {
            $pathOnly = $path;
        }
        if ( ! preg_match('#/(?:lessons_launch|discussions)/([^/]+)/?$#', $pathOnly, $match) ) {
            return '';
        }
        return rawurldecode($match[1]);
    }

    /**
     * inherit= on a Classroom launch path is the lesson resource id.
     */
    private static function inheritId($path) {
        $query = parse_url($path, PHP_URL_QUERY);
        if ( ! is_string($query) || $query === '' ) {
            return '';
        }
        $params = array();
        parse_str($query, $params);
        $id = $params['inherit'] ?? '';
        return is_string($id) ? $id : '';
    }

    public static function scrub($text) {
        if ( ! is_string($text) || $text === '' ) {
            return '';
        }
        $text = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $text);
        if ( ! is_string($text) ) {
            return '';
        }
        $text = preg_replace(
            '/"(?:access_token|refresh_token|client_secret)"\s*:\s*"[^"]*"/',
            '"token":"[redacted]"',
            $text
        );
        if ( ! is_string($text) ) {
            return '';
        }
        if ( strlen($text) > 60000 ) {
            $text = substr($text, 0, 60000)."\n[truncated]";
        }
        return $text;
    }
}
