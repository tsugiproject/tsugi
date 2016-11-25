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
     * If this is true when we return, the caller must redirect to a GET of the same URL.
     */
    public $do_redirect = false;

    /**
     * Get the base string from the launch.
     *
     * @return This is null if it is not the original launch.
     * it is not restored when the launch is restored from 
     * the session.
     */
    public $base_string;

    /**
     * Get the error message if something went wrong with the setup (TBD)
     */
    public $error_message;

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
