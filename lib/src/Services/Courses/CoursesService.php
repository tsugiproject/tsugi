<?php

namespace Tsugi\Services\Courses;

use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Core\User;
use Tsugi\Services\Service;

/**
 * Course rules that are not HTTP.
 *
 * Who may create a course, which key Google site login uses, whether
 * someone is a member, when they last opened a course, and whether that
 * course allows anonymous lesson viewing. Controllers call these and
 * still own routes, redirects, and session switching.
 */
class CoursesService extends Service {

    /** Consumer key used by Google site login. */
    const GOOGLE_KEY = 'google.com';

    /**
     * True when the current user may mint a new site-login course.
     *
     * Site admins pass. Everyone else needs the user create-courses flag.
     * Instructor role in a course does not count.
     */
    public static function canCreate() {
        return User::canCreateCourses();
    }

    /**
     * lti_key.key_id for the Google site-login consumer.
     */
    public static function googleKeyId() {
        global $CFG, $PDOX;
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( isset($_SESSION[$ltiKey]['key_id']) ) {
            $kid = (int) $_SESSION[$ltiKey]['key_id'];
            if ( $kid > 0 ) {
                return $kid;
            }
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $row = $PDOX->rowDie(
            "SELECT key_id FROM {$CFG->dbprefix}lti_key
             WHERE key_sha256 = :SHA LIMIT 1",
            array(':SHA' => lti_sha256(self::GOOGLE_KEY))
        );
        return $row ? (int) $row['key_id'] : 0;
    }

    /**
     * Login + membership (or site admin) for $context_id without switching session.
     *
     * @param mixed $context_id
     * @return true|string
     */
    public static function memberCheck($context_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return 'Invalid course.';
        }
        $user_id = ReqScope::loggedInUserId();
        if ( $user_id < 1 ) {
            return 'Must be logged in.';
        }
        $is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        if ( $is_admin ) {
            return true;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;
        $member = $PDOX->rowDie(
            "SELECT M.membership_id
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_context AS C ON C.context_id = M.context_id
             WHERE M.context_id = :CID AND M.user_id = :UID
               AND (C.deleted IS NULL OR C.deleted = 0)",
            array(':CID' => $cid, ':UID' => $user_id)
        );
        if ( ! $member ) {
            return 'You are not a member of that course.';
        }
        return true;
    }

    /**
     * Record that this user entered a course (flyout recency).
     *
     * Call only from explicit course entry (enter, nested switch, create,
     * catalog enroll). Do not call when restoring the Google home course
     * onto a site URL.
     *
     * @param mixed $context_id
     */
    public static function touchVisited($context_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        $user_id = ReqScope::loggedInUserId();
        if ( $cid < 1 || $user_id < 1 ) {
            return;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;
        $PDOX->queryDie(
            "UPDATE {$p}lti_membership
             SET visited_at = NOW()
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $cid, ':UID' => $user_id)
        );
    }

    /**
     * True when this course row allows anonymous lesson preview.
     *
     * @param mixed $context_id
     * @return bool
     */
    public static function contextAllowsAnonymousLessons($context_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return false;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $table = $CFG->dbprefix.'lti_context';
        if ( ! $PDOX->columnExists('anonymous_lessons', $table) ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT manifest_id, anonymous_lessons FROM {$table}
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $cid)
        );
        if ( ! is_array($row) ) {
            return false;
        }
        if ( (int) ($row['manifest_id'] ?? 0) < 1 ) {
            return false;
        }
        return (int) ($row['anonymous_lessons'] ?? 0) === 1;
    }

    /**
     * Turn anonymous lesson preview on or off for one course.
     *
     * @param mixed $context_id
     * @param bool $on
     * @return bool
     */
    public static function setContextAnonymousLessons($context_id, $on) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return false;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $table = $CFG->dbprefix.'lti_context';
        if ( ! $PDOX->columnExists('anonymous_lessons', $table) ) {
            return false;
        }
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$table} SET anonymous_lessons = :ON
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(
                ':ON' => $on ? 1 : 0,
                ':CID' => $cid,
            )
        );
        return is_object($stmt) && ! empty($stmt->success);
    }
}
