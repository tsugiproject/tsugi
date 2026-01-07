<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

// Require admin_util.php for isAdmin() function
require_once(__DIR__ . '/../admin/admin_util.php');

/**
 * Check if the current logged-in user is an instructor/admin for the current context
 * 
 * This function checks:
 * 1. If user is site admin (via isAdmin())
 * 2. If user has instructor role or role_override in lti_membership table
 * 3. If user owns the context or its key
 * 
 * Reads context_id and user_id from $_SESSION['context_id'] and $_SESSION['id'].
 * Role values are cached in $_SESSION['role'][$context_id] as the maximum of role and role_override.
 * Context/key ownership grants instructor status and is cached as ROLE_INSTRUCTOR.
 * This allows us to distinguish between "not cached" and "cached as non-instructor" (role = 0).
 * 
 * @return bool True if user is instructor/admin for the context, false otherwise
 */
function isInstructor() {
    global $CFG, $PDOX;
    
    // Get context_id and user_id from session
    $context_id = U::get($_SESSION, 'context_id');
    $user_id = U::get($_SESSION, 'id');
    
    // If context_id or user_id not in session, user is not an instructor
    if ( ! $context_id || ! $user_id ) {
        return false;
    }
    
    // Check if user is site admin (always true for admins, no need to cache)
    if ( isAdmin() ) {
        return true;
    }
    
    // Initialize role cache if not set
    if ( ! isset($_SESSION['role']) ) {
        $_SESSION['role'] = array();
    }
    
    // Check cache first - if cached, use the cached role value to determine instructor status
    if ( isset($_SESSION['role'][$context_id]) ) {
        $cached_role = $_SESSION['role'][$context_id];
        // Check if cached role indicates instructor (>= ROLE_INSTRUCTOR)
        if ( $cached_role >= LTIX::ROLE_INSTRUCTOR ) {
            return true;
        }
        // If cached role < INSTRUCTOR, user is not an instructor (ownership already checked)
        return false;
    }
    
    $max_role = 0;
    $is_instructor = false;
    
    // Check if user is instructor/admin for this context via membership
    $membership = $PDOX->rowDie(
        "SELECT role, role_override FROM {$CFG->dbprefix}lti_membership 
         WHERE context_id = :CID AND user_id = :UID",
        array(':CID' => $context_id, ':UID' => $user_id)
    );
    if ( $membership ) {
        $role = isset($membership['role']) ? ($membership['role'] + 0) : 0;
        $role_override = isset($membership['role_override']) ? ($membership['role_override'] + 0) : 0;
        // Cache the maximum role value
        $max_role = max($role, $role_override);
        // ROLE_INSTRUCTOR = 1000, ROLE_ADMINISTRATOR = 5000
        if ( $max_role >= LTIX::ROLE_INSTRUCTOR ) {
            $is_instructor = true;
        }
    }
    
    // Also check if user owns the context or its key
    // Context/key ownership grants instructor role and should be cached as ROLE_INSTRUCTOR
    if ( ! $is_instructor ) {
        $context_check = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (
                 key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                 OR user_id = :UID
             )",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( $context_check ) {
            $is_instructor = true;
            // Cache ownership as ROLE_INSTRUCTOR since it grants instructor status
            $max_role = LTIX::ROLE_INSTRUCTOR;
        }
    }
    
    // Cache the role value
    // This caches either the membership role or ROLE_INSTRUCTOR if granted via ownership
    $_SESSION['role'][$context_id] = $max_role;
    
    return $is_instructor;
}

/**
 * Build a stable path identifier for LMS tools (no query/session params).
 *
 * Examples:
 * - /lms/pages
 * - /lms/assignments
 *
 * @return string
 */
function lmsAnalyticsPath() {
    // We want tool-level analytics, not per-resource within a tool:
    // ignore PATH_INFO and query parameters; use just the tool folder.
    $script = U::get($_SERVER, 'SCRIPT_NAME');
    if ( ! $script ) $script = U::get($_SERVER, 'PHP_SELF');
    if ( ! $script ) return '/';

    $dir = dirname($script);
    if ( ! $dir || $dir == '.' ) $dir = '/';

    // Normalize trailing slash (but keep root)
    if ( $dir && $dir != '/' ) $dir = rtrim($dir, '/');
    return $dir;
}

/**
 * Build a stable key for LMS analytics, distinct from LTI resource_link_id values.
 *
 * @param string|null $path
 * @return string
 */
function lmsAnalyticsKey($path=null) {
    if ( $path === null ) $path = lmsAnalyticsPath();
    return 'lms:' . $path;
}

/**
 * Ensure there is an `lti_link` row for a local LMS page/tool and return link_id.
 *
 * We intentionally store LMS "tools" as synthetic `lti_link` records scoped to the
 * already-selected `lti_context`, so we can reuse the existing analytics tables
 * (`lti_link_activity`, `lti_link_user_activity`) without schema changes.
 *
 * @param int $context_id
 * @param string $link_key
 * @param string|null $title
 * @param string|null $path
 * @return int|false
 */
function lmsEnsureAnalyticsLink($context_id, $link_key, $title=null, $path=null) {
    global $CFG, $PDOX;

    if ( ! $context_id || ! $link_key ) return false;

    $sha = function_exists('lti_sha256') ? lti_sha256($link_key) : hash('sha256', $link_key);

    $row = $PDOX->rowDie(
        "SELECT link_id, title, path
         FROM {$CFG->dbprefix}lti_link
         WHERE context_id = :CID AND link_sha256 = :SHA",
        array(':CID' => $context_id, ':SHA' => $sha)
    );

    if ( ! $row ) {
        // link_key is required (TEXT NOT NULL) - we store our prefixed key
        $stmt = $PDOX->queryReturnError(
            "INSERT IGNORE INTO {$CFG->dbprefix}lti_link
                (link_sha256, link_key, context_id, title, path, updated_at)
             VALUES
                (:SHA, :KEY, :CID, :TITLE, :PATH, NOW())",
            array(
                ':SHA' => $sha,
                ':KEY' => $link_key,
                ':CID' => $context_id,
                ':TITLE' => $title,
                ':PATH' => $path
            )
        );
        if ( ! $stmt->success ) {
            error_log("Unable to create LMS analytics link context=".$context_id." key=".$link_key);
            return false;
        }

        $row = $PDOX->rowDie(
            "SELECT link_id, title, path
             FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_sha256 = :SHA",
            array(':CID' => $context_id, ':SHA' => $sha)
        );
        if ( ! $row ) return false;
    }

    // Keep title/path reasonably fresh (best-effort)
    $new_title = U::isNotEmpty($title) ? $title : null;
    $new_path = U::isNotEmpty($path) ? $path : null;
    $need_update = false;
    if ( $new_title !== null && $new_title != U::get($row,'title') ) $need_update = true;
    if ( $new_path !== null && $new_path != U::get($row,'path') ) $need_update = true;
    if ( $need_update ) {
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link
             SET title = COALESCE(:TITLE, title),
                 path = COALESCE(:PATH, path),
                 updated_at = NOW()
             WHERE link_id = :LID",
            array(
                ':TITLE' => $new_title,
                ':PATH' => $new_path,
                ':LID' => $row['link_id']
            )
        );
    }

    return $row['link_id'] + 0;
}

/**
 * Record LMS page/tool "launch" into Tsugi internal analytics, mirroring LTIX behavior.
 *
 * - Only records for learners (i.e., not instructors/admins)
 * - Stores data in lti_link_activity / lti_link_user_activity with event=0
 *
 * @param string $analytics_path Required stable path for this tool (e.g., '/lms/pages')
 * @param string|null $title Optional display title for this synthetic link
 * @return int|false The link_id if successful, false otherwise
 */
function lmsRecordLaunchAnalytics($analytics_path, $title=null) {
    global $CFG, $PDOX;

    if ( ! isset($CFG->launchactivity) || ! $CFG->launchactivity ) return false;
    if ( ! U::get($_SESSION,'id') || ! U::get($_SESSION,'context_id') ) return false;
    if ( isInstructor() ) return false; // mirror LTIX: only learner launches are logged

    // Ensure DB connection
    if ( ! isset($PDOX) ) {
        LTIX::getConnection();
    }

    $context_id = $_SESSION['context_id'] + 0;
    $user_id = $_SESSION['id'] + 0;

    $link_key = lmsAnalyticsKey($analytics_path);

    $link_id = lmsEnsureAnalyticsLink($context_id, $link_key, $title, $analytics_path);

    if ( ! $link_id ) return false;

    // ---- Link activity (all users) ----
    $row = $PDOX->rowDie(
        "SELECT link_count, activity
         FROM {$CFG->dbprefix}lti_link_activity
         WHERE link_id = :LID AND event = 0",
        array(':LID' => $link_id)
    );

    if ( ! $row ) {
        $PDOX->queryReturnError(
            "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_activity
                (link_id, event, link_count, updated_at)
             VALUES
                (:LID, 0, 0, NOW())",
            array(':LID' => $link_id)
        );
        $row = array('link_count' => 0, 'activity' => null);
    }

    $ent = new \Tsugi\Event\Entry();
    if ( U::get($row,'activity') ) $ent->deSerialize($row['activity']);
    $ent->total = (U::get($row,'link_count',0) + 0);
    $ent->click();
    $activity = $ent->serialize(LTIX::MAX_ACTIVITY);
    $PDOX->queryReturnError(
        "UPDATE {$CFG->dbprefix}lti_link_activity
         SET activity = :ACT, updated_at = NOW(), link_count = link_count + 1
         WHERE link_id = :LID AND event = 0",
        array(':ACT' => $activity, ':LID' => $link_id)
    );

    // ---- User activity (per-user) ----
    $urow = $PDOX->rowDie(
        "SELECT link_user_count, activity
         FROM {$CFG->dbprefix}lti_link_user_activity
         WHERE link_id = :LID AND user_id = :UID AND event = 0",
        array(':LID' => $link_id, ':UID' => $user_id)
    );

    if ( ! $urow ) {
        $PDOX->queryReturnError(
            "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_user_activity
                (link_id, user_id, event, link_user_count, updated_at)
             VALUES
                (:LID, :UID, 0, 0, NOW())",
            array(':LID' => $link_id, ':UID' => $user_id)
        );
        $urow = array('link_user_count' => 0, 'activity' => null);
    }

    $uent = new \Tsugi\Event\Entry();
    if ( U::get($urow,'activity') ) $uent->deSerialize($urow['activity']);
    $uent->total = (U::get($urow,'link_user_count',0) + 0);
    $uent->click();
    $uactivity = $uent->serialize(LTIX::MAX_ACTIVITY);
    $PDOX->queryReturnError(
        "UPDATE {$CFG->dbprefix}lti_link_user_activity
         SET activity = :ACT, updated_at = NOW(), link_user_count = link_user_count + 1
         WHERE link_id = :LID AND user_id = :UID AND event = 0",
        array(':ACT' => $uactivity, ':LID' => $link_id, ':UID' => $user_id)
    );

    return $link_id;
}
