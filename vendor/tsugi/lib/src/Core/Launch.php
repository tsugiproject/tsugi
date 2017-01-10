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
    WARDED_FOR
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
     * Get the actual IP address of the incoming request.
     *
     * Adapted from: https://www.chriswiegman.com/2014/05/getting-correct-ip-address-php/
     * With some additional explode goodness via: http://stackoverflow.com/a/25193833/1994792
     */
    public function get_ip() {

        //Just get the headers if we can or else use the SERVER global
        if ( function_exists( 'apache_request_headers' ) ) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }

        //Get the forwarded IP if it exists
        $the_ip = false;
        if ( array_key_exists( 'X-Forwarded-For', $headers ) ) {
            $pieces = explode(',',$headers['X-Forwarded-For']);
            $the_ip = filter_var(end($pieces),FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        }

        if ( $the_ip === false && array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) ) {
            $pieces = explode(',',$headers['HTTP_X_FORWARDED_FOR']);
            $the_ip = filter_var(end($pieces),FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        }

        if ( $the_ip === false ) {
            $the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        }
        return $the_ip;
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
