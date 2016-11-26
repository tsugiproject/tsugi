<?php

namespace Tsugi\Core;

use \Tsugi\UI\Output;

/**
 * This captures all of the data associated with the LTI Launch.
 */

class Launch {

    /**
     * Get the User associated with the launch.
     */
    public $user;

    /**
     * Get the Context associated with the launch.
     */
    public $context;

    /**
     * Get the Link associated with the launch.
     */
    public $link;

    /**
     * Get the Result associated with the launch.
     */
    public $result;

    /**
     * Return the PDOX connection used by Tsugi.
     */
    public $pdox;

    /**
     * Return the output helper class.
     */
    public $output;

    /**
     * Must be an array equivalent to $_REQUEST.  If this is present
     * We do not assume access to $_POST or $_GET and we assume
     * that we cannot use header() so the caller must
     */
    public $request_parms = null;

    /**
     * Must be a string that is the current called URL.  
     * Required if request_parms is provided.
     */
    public $current_url = null;

    /**
     * If present, it means all session management is above us.
     *
     * Must be an object that supports
     *     .get(key, default)
     *     .put(key,value)
     *     .forget(key)
     *     flush()
     *
     * Required if request_parms is provided.
     */
    public $session_object = null;

    /**
     * If this is non-null when we return, the caller must redirect to a GET of the same URL.
     */
    public $redirect_url = null;

    /**
     * If this is non-false, we send a 403 (see also $error_message)
     */
    public $send_403 = false;

    /**
     * Get the base string from the launch.
     *
     * @return This is null if it is not the original launch.
     * it is not restored when the launch is restored from 
     * the session.
     */
    public $base_string = null;

    /**
     * Get the error message if something went wrong with the setup (TBD)
     */
    public $error_message = null;

    /**
     * Get a key from the session
     */
    public function session_get($key, $default=null) {
        return LTIX::wrapped_session_get($this->session_object,$key,$default);
    }

    /**
     * Set a key in the session
     */
    public function session_put($key, $value) {
        return LTIX::wrapped_session_put($this->session_object,$key,$value);
    }

    /**
     * Forget a key in the session
     */
    public function session_forget($key) {
        return LTIX::wrapped_session_forget($this->session_object,$key);
    }

    /**
     * Flush the session
     */
    public function session_flush() {
        return LTIX::wrapped_session_flush($this->session_object);
    }


    /**
     * Dump out the internal data structures associated with the
     * current launch.  Best if used within a pre tag.
     */
    public function var_dump() {
        var_dump($this);
        echo("\n<hr/>\n");
        echo("Session data (low level):\n");
        if ( ! isset($_SESSION) ) {
            echo("Not set\n");
        } else {
            echo(Output::safe_var_dump($_SESSION));
        }
    }

}
