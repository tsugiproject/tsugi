<?php

namespace Tsugi\Core;

use \Tsugi\Util\LTI;
use \Tsugi\Util\LTI13;
use \Tsugi\Util\LTIConstants;
use \Tsugi\UI\Output;

/**
 * This captures all of the data associated with the LTI Launch.
 */

class Launch {

    /**
     * Get the User associated with the launch.
     */
    public $key;

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
    public function ltiParameterArray() {
        $row = $this->session_get('lti', false);
        return $row;
    }

    /**
     * Pull a keyed variable from the LTI data in the current session with default
     */
    public function ltiParameter($varname, $default=false) {
        $row = $this->ltiParameterArray();
        if ( ! $row ) return $default;
        if ( ! array_key_exists($varname, $row) ) return $default;
        return $row[$varname];
    }

    /**
     * Update a keyed variable from the LTI data in the current session with default
     */
    public function ltiParameterUpdate($varname, $value) {
        $lti = $this->ltiParameterArray();
        if ( ! $lti ) $lti = array(); // Should never happen
        if ( is_array($lti) ) $lti[$varname] = $value;
        $lti = $this->session_put('lti', $lti);
    }

    /**
     * Return the original $_POST array
     */
    public function ltiRawPostArray() {
        $lti_post = $this->session_get('lti_post', false);
        return $lti_post;
    }

    /**
     * Return the original JWT
     */
    public function ltiRawJWT() {
        $lti_jwt = $this->session_get('tsugi_jwt', null);
        return $lti_jwt;
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     */
    public function ltiRawParameter($varname, $default=false) {
        $lti_post = $this->ltiRawPostArray();
        if ( $lti_post === false ) return $default;
        if ( ! isset($lti_post[$varname]) ) return $default;
        return $lti_post[$varname];
    }

    /**
     * Pull a claim from the body of the Launch JWT
     */
    public function ltiJWTClaim($claim, $default=null) {
        $lti_jwt = $this->ltiRawJWT();
        if ( ! is_object($lti_jwt) ) return $default;
        if ( ! is_object($lti_jwt->body) ) return $default;
        if ( ! isset($lti_jwt->body->{$claim}) ) return $default;
        return $lti_jwt->body->{$claim};
    }

    /**
     * Return the LTI 1.3 Message Type with Reasonable Fall Backs
     */
    public function ltiMessageType() {
        $lti_jwt = $this->ltiRawJWT();
        $claim = LTI13::MESSAGE_TYPE_CLAIM;
        if ( ! is_object($lti_jwt) ) return LTI13::MESSAGE_TYPE_RESOURCE;
        if ( ! is_object($lti_jwt->body) ) return LTI13::MESSAGE_TYPE_RESOURCE;
        if ( ! isset($lti_jwt->body->{$claim}) ) return LTI13::MESSAGE_TYPE_RESOURCE;
        $message_type = $lti_jwt->body->{$claim};
        if ( is_string($message_type) ) return $message_type;
        return $message_type;
    }
    /**
     * Pull out a custom variable from the LTIX session.
     *
     * For LTI 1.1, it adds the "custom_" prefix automatically and looks in POST values
     * For LTI Advantage, the data is pulled from the Java Web Token (JWT)
     */
    public function ltiCustomGet($varname, $default=false) {
        $claim = $this->ltiJWTClaim(LTI13::CUSTOM_CLAIM);
        if ( is_object($claim) ) {
            if ( isset($claim->{$varname}) ) {
                return $claim->{$varname};
            }
            return $default;
        }
        return $this->ltiRawParameter('custom_'.$varname, $default);
    }

    /**
     * Check for a setting starting nearby
     *
     * This routine looks for a key based on the following low-to-high precedence:
     *
     * (4) From a Key Setting
     * (3) From a Context Setting
     * (2) From a Link Setting
     * (1) From a custom launch variable prefixed by "tsugi_setting"
     */
    public function settingsCascade($key, $retval=null)
    {
        if ( is_object($this->key) ) {
            $retval = $this->key->settingsGet($key, $retval);
        }

        if ( is_object($this->context) ) {
            $retval = $this->context->settingsGet($key, $retval);
        }

        if ( is_object($this->link) ) {
            $retval = $this->link->settingsGet($key, $retval);
        }

        // LTI 1.1 custom values map dashes to underscores :(
        $retval = $this->ltiCustomGet('tsugi_setting_'.LTI::mapCustomName($key), $retval);

        // Prefer exact match (i.e. with LTI 1.3)
        $retval = $this->ltiCustomGet('tsugi_setting_'.$key, $retval);

        return $retval;
    }

    /**
     * Indicate if this launch came from Sakai
     *
     * if ( $LTI->isSakai() ) echo("SAKAI");
     */
    public function isSakai() {
        $claim = $this->ltiJWTClaim(LTI13::TOOL_PLATFORM_CLAIM, null);
        if ( $claim !== null ) {
            $ext_lms = null;
            if ( isset($claim->{LTI13::PRODUCT_FAMILY_CODE}) ) {
                $ext_lms = $claim->{LTI13::PRODUCT_FAMILY_CODE};
            }
        } else {
            $ext_lms = $this->ltiRawParameter('ext_lms', false);
            $ext_lms = strtolower($ext_lms);
        }
        return stripos($ext_lms, 'sakai') === 0 ;
    }

    /**
     * Indicate if this launch came from Canvas
     *
     * if ( $LTI->isCanvas() ) echo("CANVAS");
     */
    public function isCanvas() {
        $claim = $this->ltiJWTClaim(LTI13::TOOL_PLATFORM_CLAIM, null);
        if ( $claim !== null ) {
            $product = null;
            if ( isset($claim->{LTI13::PRODUCT_FAMILY_CODE}) ) {
                $product = $claim->{LTI13::PRODUCT_FAMILY_CODE};
            }
        } else {
            $product = $this->ltiRawParameter(LTIConstants::TOOL_CONSUMER_INFO_PRODUCT_FAMILY_CODE, false);
        }
        return stripos($product, 'canvas') === 0 ;
    }

    /**
     * Indicate if this launch came from Moodle
     *
     * if ( $LTI->isMoodle() ) echo("MOODLE");
     */
    public function isMoodle() {
        $claim = $this->ltiJWTClaim(LTI13::TOOL_PLATFORM_CLAIM, null);
        if ( is_object($claim) ) {
            $ext_lms = null;
            if ( isset($claim->{LTI13::PRODUCT_FAMILY_CODE}) ) {
                $ext_lms = $claim->{LTI13::PRODUCT_FAMILY_CODE};
            }
        } else {
            $ext_lms = $this->ltiRawParameter('ext_lms', false);
            $ext_lms = strtolower($ext_lms);
        }
        return stripos($ext_lms, 'moodle') === 0 ;
    }

    /**
     * Indicate if this launch came from Coursera
     *
     * if ( $LTI->isCoursera() ) echo("Coursera");
     */
    public function isCoursera() {
        $claim = $this->ltiJWTClaim(LTI13::TOOL_PLATFORM_CLAIM, null);
        if ( is_object($claim) ) {
            $product = null;
            if ( isset($claim->{LTI13::PRODUCT_FAMILY_CODE}) ) {
                $product = $claim->{LTI13::PRODUCT_FAMILY_CODE};
            }
            return stripos($product, 'coursera')!== false ;
        } else {
            $product = $this->ltiRawParameter(LTIConstants::TOOL_CONSUMER_INFO_PRODUCT_FAMILY_CODE, false);
            $tci_description = $this->ltiRawParameter(LTIConstants::TOOL_CONSUMER_INSTANCE_DESCRIPTION, false);
            return ( $product == 'ims' && $tci_description == 'Coursera');
        }
    }

    /**
     * Return a boolean is this is an LTI Advantage launch
     */
    public function isLTIAdvantage() {
        return $this->ltiRawJWT() !== null;
    }

    /**
     * set up parameters for an outbound launch from this launch
     */
    public function newLaunch($send_name=true, $send_email=true) {
        $parms = array(
            LTIConstants::LTI_MESSAGE_TYPE => LTIConstants::LTI_MESSAGE_TYPE_BASICLTILAUNCHREQUEST,
            LTIConstants::TOOL_CONSUMER_INFO_PRODUCT_FAMILY_CODE => 'tsugi',
            LTIConstants::TOOL_CONSUMER_INFO_VERSION => '1.1',
        );

        // Some Tsugi Goodness
        $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        if ( $this->user ) {
            $parms[LTIConstants::USER_ID] = $this->user->id;
            $parms[LTIConstants::ROLES] = $this->user->instructor ?
                LTIConstants::ROLE_INSTRUCTOR : LTIConstants::ROLE_LEARNER;
            if ( $send_name ) $parms[LTIConstants::LIS_PERSON_NAME_FULL] = $this->user->displayname;
            if ( $send_email ) $parms[LTIConstants::LIS_PERSON_CONTACT_EMAIL_PRIMARY] = $this->user->email;
            if ( $send_email || $send_email ) $parms[LTIConstants::USER_IMAGE] = $this->user->image;
        }
        if ( $this->context ) {
           $parms[LTIConstants::CONTEXT_ID] = $this->context->id;
           $parms[LTIConstants::CONTEXT_TITLE] = $this->context->title;
           $parms[LTIConstants::CONTEXT_LABEL] = $this->context->title;
        }
        if ( $this->link ) {
           $parms[LTIConstants::RESOURCE_LINK_ID] = $this->link->id;
           $parms[LTIConstants::RESOURCE_LINK_TITLE] = $this->link->title;
        }
        if ( $this->result ) {
           $parms[LTIConstants::RESOURCE_LINK_ID] = $this->link->id;
           $parms[LTIConstants::RESOURCE_LINK_TITLE] = $this->link->title;
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
