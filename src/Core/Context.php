<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;

/**
 * This is a class to provide access to the resource context level data.
 *
 * This data comes from the LTI launch from the LMS. 
 * A context is the equivalent of a "class" or course.   A context
 * has a roster of users and each user has a role within the context.
 * A launch may or may not contain a context.  If there
 * is a link without a context, it is a "system-wide" link
 * like "view profile" or "show all courses"
 *
 */

class Context extends Entity {

    // TODO: - $Context->lang - The context language choice.

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_context";
    protected $PRIMARY_KEY = "context_id";

    // Contexts have settings...
    protected $ENTITY_NAME = "context";
    use SettingsTrait;  // Pull in the trait

    /**
     * The integer primary key for this context in the 'lti_context' table.
     */
    public $id;

    /**
     * The context title
     */
    public $title;

    /**
     * load the roster if we can get it from the LMS
     *
     * @param $with_sourcedids If true, include the sourcedids
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the NRPS object.  If it fails,
     * it returns a string.
     *
     */
    public function loadNamesAndRoles($with_sourcedids=false, &$debug_log=false) {
        global $CFG;

        $lti13_token_url = $this->launch->ltiParameter('lti13_token_url');
        $lti13_privkey = LTIX::decrypt_secret($this->launch->ltiParameter('lti13_privkey'));
        $lti13_membership_url = $this->launch->ltiParameter('lti13_membership_url');
        $lti13_client_id = $this->launch->ltiParameter('lti13_client_id');

        $missing = '';
        if ( strlen($lti13_client_id) < 1 ) $missing .= ' ' . 'lti13_client_id';
        if ( strlen($lti13_privkey) < 1 ) $missing .= ' ' . 'private_key';
        if ( strlen($lti13_token_url) < 1 ) $missing .= ' ' . 'token_url';
        if ( strlen($lti13_membership_url) < 1 ) $missing .= ' ' . 'membership_url';
        $missing = trim($missing);

        if ( strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return $missing;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while
        
        // TODO: Also note that in LTI13 the basicoutcome claim is suppressed to make the cert suite happy
        // so these two things to the same thing for now.
        if ( $with_sourcedids ) {
            $nrps_access_token = LTI13::getNRPSWithSourceDidsToken($CFG->wwwroot, $lti13_client_id, $lti13_token_url, $lti13_privkey, $debug_log);
        } else {
            $nrps_access_token = LTI13::getNRPSToken($CFG->wwwroot, $lti13_client_id, $lti13_token_url, $lti13_privkey, $debug_log);
        }

        $nrps = LTI13::loadNRPS($lti13_membership_url, $nrps_access_token, $debug_log);
        return $nrps;
    }

}
