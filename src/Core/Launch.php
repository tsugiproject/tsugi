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
     * Dump out the internal data structures adssociated with the
     * current launch.  Best if used within a pre tag.
     */
    public function var_dump() {
        if ( ! isset($this->user) ) {
            echo("User not set\n");
        } else {
            var_dump($this->user);
        }
        if ( ! isset($this->context) ) {
            echo("Context not set\n");
        } else {
            var_dump($this->context);
        }
        if ( ! isset($this->link) ) {
            echo("Link not set\n");
        } else {
            var_dump($this->link);
        }
        if ( ! isset($this->result) ) {
            echo("Result not set\n");
        } else {
            var_dump($this->result);
        }
        echo("\n<hr/>\n");
        echo("Session data (low level):\n");
        if ( ! isset($_SESSION) ) {
            echo("Not set\n");
        } else {
            echo(Output::safe_var_dump($_SESSION));
        }
    }

}
