<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;

/**
 * This is a class holding convienence methods to access the session from the launch object
 */

class SessionAccess {
    /**
     * All extending classes must define these member variables
     */

    /**
     * A reference to our containing launch
     */
    public $launch;

   /**
     * Get a key from the session
     */
    public function session_get($key, $default=null) {
        return $this->launch->session_get($key, $default);
    }

    /**
     * Set a key in the session
     */
    public function session_put($key, $value) {
        $this->launch->session_put($key, $value);
    }

   /**
     * Forget a key in the session
     */
    public function session_forget($key) {
        $this->launch->session_forget($key);
    }

    /**
     * Flush the session
     */
    public function session_flush() {
        return $this->launch->session_flush();
    }

    /**
     * Return the original $_POST array
     */
    public function ltiRawPostArray() {
        $lti_post = $this->launch->ltiRawPostArray();
        return $lti_post;
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     */
    public function ltiParameter($varname, $default=false) {
        return $this->launch->ltiParameter($varname, $default);
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     */
    public function ltiRawParameter($varname, $default=false) {
        return $this->launch->ltiRawParameter($varname, $default);
    }

    /**
     * Pull out a custom variable from the LTIX session. Do not
     * include the "custom_" prefix - this is automatic.
     */
    public function ltiCustomGet($varname, $default=false) {
        return $this->launch->ltiCustomGet($varname, $default);
    }


}
