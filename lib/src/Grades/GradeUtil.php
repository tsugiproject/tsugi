<?php

namespace Tsugi\Grades;

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Cache;
use \Tsugi\Core\Membership;

class GradeUtil {

    /** @internal Session cache location for {@see Cache::setContext} */
    private const DUE_DATES_CACHE_LOC = 'gradeutil_due_dates';

    private const DUE_DATES_CACHE_TTL = 300;

    /** @internal Session cache for {@see loadGradesCurrentUser} */
    private const GRADES_CURRENT_USER_CACHE_LOC = 'gradeutil_grades_current_user';

    private const GRADES_CURRENT_USER_CACHE_TTL = 300;

    public static function gradeLoadAll() {
        global $CFG, $USER, $LINK, $PDOX;
        $LAUNCH = LTIX::requireData(LTIX::LINK);
        if ( ! $USER->instructor ) die("Requires instructor role");
        $p = $CFG->dbprefix;

        // Get basic grade data
        $stmt = $PDOX->queryDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id,
                grade, note, R.json AS json, R.note as note, R.updated_at AS updated_at, R.created_at AS created_at, displayname, email
            FROM {$p}lti_result AS R
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            WHERE R.link_id = :LID
            ORDER BY updated_at DESC",
            array(":LID" => $LINK->id)
        );
        return $stmt;
    }

    // $detail is either false or a class with methods
    public static function gradeShowAll($stmt, $detail = false) {
        echo('<table border="1">');
        echo("\n<tr><th>Name<th>Email</th><th>Grade</th><th>Date</th></tr>\n");

        while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            echo('<tr><td>');
            if ( $detail ) {
                $detail->link($row);
            } else {
                echo(htmlent_utf8($row['displayname']));
            }
            echo("</td>\n<td>".htmlent_utf8($row['email'])."</td>
                <td>".htmlent_utf8($row['grade'])."</td>
                <td>".htmlent_utf8($row['updated_at'])."</td>
            </tr>\n");
        }
        echo("</table>\n");
    }

    // Not cached
    public static function gradeLoad($user_id=false) {
        global $LAUNCH, $CFG, $USER, $LINK, $PDOX;
        $LAUNCH = LTIX::requireData(array(LTIX::LINK, LTIX::USER));
        if ( ! $USER->instructor && $user_id !== false ) die("Requires instructor role");
        if ( $user_id == false ) $user_id = $USER->id;
        $p = $CFG->dbprefix;

        // Get basic grade data
        $stmt = $PDOX->queryDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id,
                grade, note, R.json AS json, R.note as note, R.updated_at AS updated_at, R.created_at AS created_at, displayname, email
            FROM {$p}lti_result AS R
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            WHERE R.link_id = :LID AND R.user_id = :UID
            GROUP BY U.email",
            array(":LID" => $LINK->id, ":UID" => $user_id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    public static function gradeShowInfo($row, $url='grades.php') {
        if ($url) echo('<p><a href="'.U::safe_href($url).'">Back to All Grades</a>'."</p><p>\n");
        if ( ! is_array($row) ) {
            echo("<p>No user data found.</p>\n");
            return;
        }
        echo("Name: ".htmlent_utf8($row['displayname'])."<br/>\n");
        echo("Email: ".htmlent_utf8($row['email'])."<br/>\n");
        echo("Started at: ".htmlent_utf8($row['created_at'])."<br/>\n");
        echo("Last Submission: ".htmlent_utf8($row['updated_at'])."<br/>\n");
        echo("Score: ".htmlent_utf8($row['grade'])."<br/>\n");
        echo("</p>\n");
    }

    // newdata can be a string or array (preferred)
    public static function gradeUpdateJson($newdata=false) {
        global $CFG, $PDOX, $LINK, $RESULT;
        if ( $newdata == false ) return;
        if ( is_string($newdata) ) $newdata = json_decode($newdata, true);
        $LAUNCH = LTIX::requireData(array(LTIX::LINK));
        if ( ! isset($RESULT) ) return;
        $row = self::gradeLoad();
        $data = array();
        if ( $row !== false && isset($row['json'])) {
            $data = json_decode($row['json'], true);
        }

        $changed = false;
        foreach ($newdata as $k => $v ) {
            if ( (!isset($data[$k])) || $data[$k] != $v ) {
                $data[$k] = $v;
                $changed = true;
            }
        }

        if ( $changed === false ) return;

        $jstr = json_encode($data);

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result SET json = :json, updated_at = NOW()
                WHERE result_id = :RID",
            array(
                ':json' => $jstr,
                ':RID' => $RESULT->id)
        );
    }

    /**
     * Load all the grades for the current user / course including resource_link_id
     */
    public static function loadGradesForCourse($user_id, $context_id)
    {
        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $sql =
        "SELECT R.result_id AS result_id, L.title as title, L.link_key AS resource_link_id,
            R.grade AS grade, R.note AS note, R.updated_at AS updated_at, R.created_at AS created_at
        FROM {$p}lti_result AS R
        JOIN {$p}lti_link as L ON R.link_id = L.link_id
        LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
        WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL";
        $rows = $PDOX->allRowsDie($sql,array(':UID' => $user_id, ':CID' => $context_id));
        return $rows;
    }

    /**
     * Grades for the logged-in user in the current course (uses \loggedInUserId() and \currentContextId()).
     * Cached per context via {@see Cache::setContext} for {@see self::GRADES_CURRENT_USER_CACHE_TTL} seconds;
     * on miss loads via {@see loadGradesForCourse}.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function loadGradesCurrentUser() {
        $uid = \loggedInUserId();
        $cid = \currentContextId();
        if ( $uid < 1 || $cid < 1 ) {
            return array();
        }

        $cached = Cache::getContext(self::GRADES_CURRENT_USER_CACHE_LOC, $cid);
        if ( is_array($cached) ) {
            return $cached;
        }

        LTIX::getConnection();

        $rows = self::loadGradesForCourse($uid, $cid);
        if ( ! is_array($rows) ) {
            $rows = array();
        }

        Cache::setContext(self::GRADES_CURRENT_USER_CACHE_LOC, $cid, $rows, self::GRADES_CURRENT_USER_CACHE_TTL);

        return $rows;
    }

    /**
     * Clear all session data for {@see loadGradesCurrentUser} so the next request refetches from the database.
     */
    public static function invalidateGradesCurrentUser() {
        Cache::clearContext(self::GRADES_CURRENT_USER_CACHE_LOC);
    }

    /**
     * Remove session-cached due dates for one context, or clear the slot if $context_id is null.
     */
    public static function invalidateDueDatesCache($context_id = null) {
        if ( $context_id === null ) {
            Cache::clearContext(self::DUE_DATES_CACHE_LOC);
            return;
        }
        Cache::invalidateContext(self::DUE_DATES_CACHE_LOC, (int) $context_id);
    }

    /**
     * Due/start times from lti_link for a context, keyed by link_key (resource link id).
     * Cached via {@see Cache::setContext} for {@see self::DUE_DATES_CACHE_TTL} seconds.
     *
     * @return array<string,array{start_datetime:mixed,end_datetime:mixed}>
     */
    public static function loadDueDatesForContext($context_id) {
        global $CFG, $PDOX;
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return array();
        }

        $cached = Cache::getContext(self::DUE_DATES_CACHE_LOC, $cid);
        if ( is_array($cached) ) {
            return $cached;
        }

        LTIX::getConnection();

        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT link_key, start_datetime, end_datetime FROM {$p}lti_link
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $cid)
        );
        $map = array();
        foreach ( $rows as $row ) {
            $map[$row['link_key']] = array(
                'start_datetime' => $row['start_datetime'],
                'end_datetime' => $row['end_datetime'],
            );
        }

        Cache::setContext(self::DUE_DATES_CACHE_LOC, $cid, $map, self::DUE_DATES_CACHE_TTL);

        return $map;
    }

    /**
     * @param array<string,array<string,mixed>> $map
     */
    private static function dueMapHasScheduledEnd($map) {
        if ( ! is_array($map) ) {
            return false;
        }
        foreach ( $map as $row ) {
            $end = isset($row['end_datetime']) ? $row['end_datetime'] : null;
            if ( $end !== null && $end !== '' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether the course has any assignment with a non-empty lti_link.end_datetime (ignores viewDueDates).
     */
    public static function contextHasAnyDueDate($context_id) {
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return false;
        }
        return self::dueMapHasScheduledEnd(self::loadDueDatesForContext($cid));
    }

    /**
     * Due map for UI when the current user may see due dates (course has at least one due and
     * lti_membership.viewDueDates allows it). Otherwise returns an empty array.
     *
     * Use for learner-facing surfaces (lessons, assignments list, calendar). Instructors editing
     * due dates should use {@see loadDueDatesForContext} instead.
     *
     * @return array<string,array{start_datetime:mixed,end_datetime:mixed}>
     */
    public static function loadDueDatesForDisplay($context_id) {
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return array();
        }
        $map = self::loadDueDatesForContext($cid);
        if ( ! self::dueMapHasScheduledEnd($map) ) {
            return array();
        }
        $user_id = \loggedInUserId();
        if ( $user_id < 1 ) {
            return array();
        }
        LTIX::getConnection();
        $m = Membership::ensureInSession($cid, $user_id);
        if ( empty($m->viewDueDates) ) {
            return array();
        }
        return $map;
    }

    /**
     * Whether due-date UI should appear for the current user in this context.
     *
     * True when {@see loadDueDatesForDisplay} would return a non-empty map (at least one scheduled due
     * and membership allows viewing).
     *
     * @return bool
     */
    public static function showDueDates($context_id) {
        return self::loadDueDatesForDisplay($context_id) !== array();
    }
}
