<?php

namespace Tsugi\Core;

/**
 * The launching user's membership in the current context (lti_membership).
 *
 * Built from the same LTI session row as User and Context after launch or login.
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
     */
    public $role = null;

    /**
     * True if this membership is instructor-level in the current context.
     *
     * Uses the same rule as User::$instructor: a role is present and non-zero.
     */
    public function isInstructor() {
        return isset($this->role) && $this->role != 0;
    }
}
