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
     * Get the base string from the launch.
     *
     * This is null if it is not the original launch.
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
     * Pull a keyed variable from the LTI data in the current session with default
     */
    public function ltiParameter($varname, $default=false) {
        $row = $this->session_get('lti', false);
        if ( ! $row ) return $default;
        if ( ! isset($row[$varname]) ) return $default;
        return $row[$varname];
    }

    /**
     * Return the original $_POST array
     */
    public function ltiRawPostArray() {
        $lti_post = $this->session_get('lti_post', false);
        return $lti_post;
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     */
    public function ltiRawParameter($varname, $default=false) {
        $lti_post = $this->ltiRawPostArray('lti_post', false);
        if ( $lti_post === false ) return $default;
        if ( ! isset($lti_post[$varname]) ) return $default;
        return $lti_post[$varname];
    }

    /**
     * Pull out a custom variable from the LTIX session. Do not
     * include the "custom_" prefix - this is automatic.
     */
    public function ltiCustomGet($varname, $default=false) {
        return $this->ltiRawParameter('custom_'.$varname, $default);
    }

    /**
     * Indicate if this launch came from Sakai
     */
    public function isSakai() {
        $ext_lms = $this->ltiRawParameter('ext_lms', false);
        $ext_lms = strtolower($ext_lms);
        return strpos($ext_lms, 'sakai') === 0 ;
    }

    /**
     * Indicate if this launch came from Canvas
     */
    public function isCanvas() {
        $product = $this->ltiRawParameter('tool_consumer_info_product_family_code', false);
        return $product == 'canvas';
    }

    /**
     * Indicate if this launch came from Moodle
     */
    public function isMoodle() {
        $ext_lms = $this->ltiRawParameter('ext_lms', false);
        $ext_lms = strtolower($ext_lms);
        return strpos($ext_lms, 'moodle') === 0 ;
    }

    /**
     * Indicate if this launch came from Coursera
     */
    public function isCoursera() {
        $product = $this->ltiRawParameter('tool_consumer_info_product_family_code', false);
        $tci_description = $this->ltiRawParameter('tool_consumer_instance_description', false);
        return ( $product == 'ims' && $tci_description == 'Coursera');
    }

    /**
     * set up parameters for an outbound launch from this launch
     */
    public function newLaunch($send_name=true, $send_email=true) {
        $parms = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'tool_consumer_info_product_family_code' => 'tsugi',
            'tool_consumer_info_version' => '1.1',
        );

        // Some Tsugi Goodness
        $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        if ( $this->user ) {
            $parms['user_id'] = $this->user->id;
            $parms['roles'] = $this->user->instructor ? 'Instructor' : 'Learner';
            if ( $send_name ) $parms['lis_person_name_full'] = $this->user->displayname;
            if ( $send_email ) $parms['lis_person_contact_email_primary'] = $this->user->email;
            if ( $send_email || $send_email ) $parms['image'] = $this->user->image;
        }
        if ( $this->context ) {
           $parms['context_id'] = $this->context->id;
           $parms['context_title'] = $this->context->title;
           $parms['context_label'] = $this->context->title;
        }
        if ( $this->link ) {
           $parms['resource_link_id'] = $this->link->id;
           $parms['resource_link_title'] = $this->link->title;
        }
        if ( $this->result ) {
           $parms['resource_link_id'] = $this->link->id;
           $parms['resource_link_title'] = $this->link->title;
        }
        foreach ( $parms as $k => $v ) {
            if ( $v === false || $v === null ) unset($parms[$k]);
        }
        return $parms;
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
