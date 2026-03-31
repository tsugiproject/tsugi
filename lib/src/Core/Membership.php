<?php

namespace Tsugi\Core;

/**
 * The launching user's membership in the current context (lti_membership).
 *
 * Built from the same LTI session row as User and Context after launch or login.
 *
 * LMS tools also cache a populated instance per context in $_SESSION['membership'][$context_id]
 * via ensureInSession() (effective numeric role, viewDueDates, optional id).
 */
class Membership {

    /**
     * @var Launch|false
     */
    public $launch = false;

    /**
     * Primary key in lti_membership when present in the session row.
     */
    public $id = null;

    /**
     * Effective role in the current context (numeric; see LTIX::ROLE_* constants).
     * Omitted from the session row until it is known.
     *
     * When loaded by ensureInSession(), this is max(role, role_override) from the row,
     * or LTIX::ROLE_INSTRUCTOR when the user owns the context or its key, or 0.
     */
    public $role = null;

    /**
     * When set by ensureInSession(), mirrors lti_membership.viewDueDates (user may see due dates).
     */
    public $viewDueDates = null;

    /**
     * True if effective role is instructor or administrator (>= LTIX::ROLE_INSTRUCTOR).
     */
    public function isInstructor() {
        return isset($this->role) && $this->role >= LTIX::ROLE_INSTRUCTOR;
    }

    /**
     * Load or reuse $_SESSION['membership'][$context_id] for this user in one lti_membership SELECT
     * (plus ownership query when role must be resolved and membership is not already instructor-level).
     *
     * @param int $context_id
     * @param int $user_id
     * @return self
     */
    public static function ensureInSession($context_id, $user_id) {
        global $CFG, $PDOX;

        if ( ! isset($_SESSION['membership']) ) {
            $_SESSION['membership'] = array();
        }
        if ( isset($_SESSION['membership'][$context_id]) ) {
            $existing = $_SESSION['membership'][$context_id];
            if ( $existing instanceof self ) {
                return $existing;
            }
        }

        $row = $PDOX->rowDie(
            "SELECT membership_id, role, role_override, viewDueDates FROM {$CFG->dbprefix}lti_membership
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );

        $m = new self();

        $vdd = true;
        if ( $row && isset($row['viewDueDates']) && $row['viewDueDates'] !== '' && $row['viewDueDates'] !== null ) {
            $vdd = ( (int) $row['viewDueDates'] ) !== 0;
        }
        $m->viewDueDates = $vdd;

        if ( $row && isset($row['membership_id']) ) {
            $m->id = $row['membership_id'] + 0;
        }

        $max_role = 0;
        $instructor_from_membership = false;
        if ( $row ) {
            $role = isset($row['role']) ? ($row['role'] + 0) : 0;
            $role_override = isset($row['role_override']) ? ($row['role_override'] + 0) : 0;
            $max_role = max($role, $role_override);
            if ( $max_role >= LTIX::ROLE_INSTRUCTOR ) {
                $instructor_from_membership = true;
            }
        }

        if ( ! $instructor_from_membership ) {
            $context_check = $PDOX->rowDie(
                "SELECT context_id FROM {$CFG->dbprefix}lti_context
                 WHERE context_id = :CID AND (
                     key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                     OR user_id = :UID
                 )",
                array(':CID' => $context_id, ':UID' => $user_id)
            );
            if ( $context_check ) {
                $max_role = LTIX::ROLE_INSTRUCTOR;
            }
        }

        $m->role = $max_role;

        $_SESSION['membership'][$context_id] = $m;
        return $m;
    }
}
