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
