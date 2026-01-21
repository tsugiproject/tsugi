<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Keyset;
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

    /*
     * The LTI 1.1 key (if defined)
     */
    public $key;

    /*
     * The LTI 1.1 secret (if defined)
     */
    public $secret;

    /*
     * The context_id from the server
     */
    public $context_id;

    /**
     * Load the LTI 1.3 data from the session, checking for sanity
     *
     * @param string &$lti13_token_url The token URL (output parameter)
     * @param string &$privkey The current private key (output parameter)
     * @param string &$kid The current kid for the public key (output parameter)
     * @param string &$lti13_token_audience The current optional token audience (output parameter)
     * @param string &$issuer_client The current client_id (output parameter)
     * @param string &$deployment_id The current deployment_id (output parameter)
     *
     * @return string Empty string if successful, otherwise contains error details
     */
    private function loadLTI13Data(&$lti13_token_url, &$privkey, &$kid, &$lti13_token_audience, &$issuer_client, &$deployment_id)
    {
        global $CFG;

        $success = Keyset::getSigning($privkey, $kid);

        $lti13_token_url = $this->launch->ltiParameter('lti13_token_url');
        $issuer_client = $this->launch->ltiParameter('issuer_client');

        $lti13_token_audience = $this->launch->ltiParameter('lti13_token_audience'); // Optional
        $deployment_id = $this->launch->ltiParameter('deployment_id');

        $missing = '';
        if ( empty($issuer_client) ) $missing .= ' ' . 'issuer_client';
        if ( empty($privkey) ) $missing .= ' ' . 'private_key';
        if ( empty($kid) ) $missing .= ' ' . 'public_key kid';
        if ( empty($lti13_token_url) ) $missing .= ' ' . 'token_url';
        $missing = trim($missing);
        return($missing);
    }

    /**
     * Load the roster if we can get it from the LMS
     *
     * @param bool $with_sourcedids If true, ask for the sourcedids
     * @param array|false &$debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return object|string|false If this works it returns the NRPS object.  If it fails,
     * it returns a string with error details or false.
     */
    public function loadNamesAndRoles($with_sourcedids=false, &$debug_log=false) {
        global $CFG;

        $missing = $this->loadLTI13Data($lti13_token_url, $privkey, $kid, $lti13_token_audience, $issuer_client, $deployment_id);
        $lti13_membership_url = $this->launch->ltiParameter('lti13_membership_url');
        if ( empty($lti13_membership_url) ) $missing .= ' ' . 'membership_url';
        $missing = trim($missing);

        if ( is_string($missing) && U::strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return $missing;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while

        // TODO: Also note that in LTI13 the basicoutcome claim is suppressed to make the cert suite happy
        // so these two things to the same thing for now.
        if ( $with_sourcedids ) {
            $nrps_access_token = LTI13::getNRPSWithSourceDidsToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        } else {
            $nrps_access_token = LTI13::getNRPSToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        }

        if ( $nrps_access_token === false ) {
            $debug_log[] = "NRPS data could not be retrieved";
            return;
        }

        $nrps = LTI13::loadNRPS($lti13_membership_url, $nrps_access_token, $debug_log);
        return $nrps;
    }

    /**
     * Load all the groups if we can get them from the LMS
     *
     * @param array|false &$debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return object|string|false If this works it returns the Groups object.  If it fails,
     * it returns a string with error details or false.
     */
    public function loadAllGroups(&$debug_log=false) {
        return self::loadGroups(null, $debug_log);
    }

    /**
     * Load the groups from the LMS
     *
     * @param string|null $user_id If this is a string, then only the groups for the user_id are retrieved
     * @param array|false &$debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return object|string|false If this works it returns the Groups object.  If it fails,
     * it returns a string with error details or false.
     */
    public function loadGroups($user_id, &$debug_log=false) {
        global $CFG;

        $missing = $this->loadLTI13Data($lti13_token_url, $privkey, $kid, $lti13_token_audience, $issuer_client, $deployment_id);
        $lti13_context_groups_url = $this->launch->ltiParameter('lti13_context_groups_url');
        if ( empty($lti13_context_groups_url) ) $missing .= ' ' . 'context_groups_url';
        $missing = trim($missing);

        if ( is_string($missing) && U::strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return $missing;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while

        // TODO: Also note that in LTI13 the basicoutcome claim is suppressed to make the cert suite happy
        // so these two things to the same thing for now.
        $groups_access_token = LTI13::getGroupsToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);

        if ( $groups_access_token === false ) {
            $debug_log[] = "Groups data could not be retrieved";
            return;
        }

        if ( is_string($user_id) && strlen($user_id) > 0 ) {
            $lti13_context_groups_url = add_url_parm($lti13_context_groups_url, 'user_id', $user_id);
        }

        $nrps = LTI13::loadGroups($lti13_context_groups_url, $groups_access_token, $debug_log);
        return $nrps;
    }

    /** Wrapper to get line items token so we can add caching
     *
     * @param string $missing This is a non-empty string with error detail if there
     * was an error
     * @param string $lti13_lineitems The url for the LineItems Service if there
     * was no error
     * @param array $debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return mixed If there is an error, this returns false and $missing has the detail
     * if this is a success, the token is returned (a string)
     */

    public function getLineItemsToken(&$missing, &$lti13_lineitems, &$debug_log=false)
    {
        global $CFG;

        $missing = $this->loadLTI13Data($lti13_token_url, $privkey, $kid, $lti13_token_audience, $issuer_client, $deployment_id);

        $lti13_lineitems = $this->launch->ltiParameter('lti13_lineitems');
        if ( empty($lti13_lineitems) ) $missing .= ' ' . 'lineitems';

        $missing = trim($missing);
        if ( is_string($missing) && U::strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return false;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while
        $lineitems_access_token = LTI13::getLineItemsToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        return $lineitems_access_token;
    }

    /**
     * Load our lineitems from the LMS
     *
     * @param array|false $search Search values to apply to the load (tag, lti_link_id, resource_id)
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return array|string If this works it returns the LineItems array.  If it fails,
     * it returns a string with error details.
     */
    public function loadLineItems($search=false, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get LineItems access_token";

        $url = $lti13_lineitems;
        if ( is_array($search) && count($search) > 0 ) {
            if ( U::get($search, 'tag') ) $url = U::add_url_parm($url, 'tag', U::get($search, 'tag'));
            if ( U::get($search, 'lti_link_id') ) $url = U::add_url_parm($url, 'lti_link_id', U::get($search, 'lti_link_id'));
            if ( U::get($search, 'resource_id') ) $url = U::add_url_parm($url, 'resource_id', U::get($search, 'resource_id'));
        }

        $lineitems = LTI13::loadLineItems($url, $lineitems_access_token, $debug_log);
        return $lineitems;
    }

    /**
     * Load the detail for a lineitem from the LMS
     *
     * @param string $id The line item ID (REST endpoint URL)
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return object|string If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function loadLineItem($id, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get LineItems access_token";

        $lineitem = LTI13::loadLineItem($id, $lineitems_access_token, $debug_log);
        return $lineitem;
    }

    /**
     * Create a lineitem in the LMS
     *
     * @param object $newitem The fields for the new line item
     *     Example:
     *     $newitem = new \stdClass();
     *     $newitem->scoreMaximum = 100;
     *     $newitem->label = 'Week 3 Feedback';
     *     $newitem->resourceId = '2987487943';
     *     $newitem->tag = 'optional';
     *
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return object|string If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function createLineItem($newitem, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get lineitems_access_token";

        $retval = LTI13::createLineItem($lti13_lineitems, $lineitems_access_token, $newitem, $debug_log);

        return $retval;
    }

    /**
     * Delete a lineitem from the LMS
     *
     * @param string $id The line item ID (REST endpoint URL)
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return bool|string If this works it returns true.  If it fails, it returns a string with error details.
     */
    public function deleteLineItem($id, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get LineItems access_token";

        $lineitem = LTI13::deleteLineItem($id, $lineitems_access_token, $debug_log);
        return $lineitem;
    }

    /**
     * Update a lineitem in the LMS
     *
     * @param string $id The line item ID (REST endpoint URL)
     * @param object $newitem The fields to update
     *     Example:
     *     $newitem = new \stdClass();
     *     $newitem->scoreMaximum = 100;
     *     $newitem->label = 'Week 3 Feedback';
     *     $newitem->resourceId = '2987487943';
     *     $newitem->tag = 'optional';
     *
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return object|string If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function updateLineItem($id, $newitem, &$debug_log=false) {
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $grade_token ) return "Unable to get grade_token";

        $retval = LTI13::updateLineItem($id, $grade_token, $newitem, $debug_log);

        return $retval;
    }

    /** Wrapper to get grade token
     *
     * @param string $missing This is a non-empty string with error detail if there
     * was an error
     * @param string $subject The subject if there is no error
     * @param array $debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return mixed If there is an error, this returns false and $missing has the detail
     * if this is a success, the token is returned (a string)
     */

    public function getGradeToken(&$missing, &$subject, &$debug_log=false)
    {
        global $CFG;

        $missing = $this->loadLTI13Data($lti13_token_url, $privkey, $kid, $lti13_token_audience, $issuer_client, $deployment_id);

        $missing = trim($missing);
        if ( is_string($missing) && U::strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return false;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while
        $grade_token = LTI13::getGradeToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        return $grade_token;
    }

    /**
     * Send a lineitem result to the LMS
     *
     * @param string $id The REST endpoint (id) for this line item
     * @param string $user_key The user for this grade
     * @param float $grade Value to send
     * @param float $scoreMaximum What the score is relative to
     * @param string|null $comment An optional comment
     * @param array|false &$debug_log Returns a log of actions taken
     * @param array|false $extra13 A key/value store of extra LTI1.3 parameters
     *
     * @return bool|string If this is a success a true is returned, if not a string with an error
     * is returned.
     */
    public function sendLineItemResult($id, $user_key, $grade, $scoreMaximum, $comment, &$debug_log=false, $extra13=false) {
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $grade_token ) return "Unable to get grade_token";

        $status = LTI13::sendLineItemResult($user_key, $grade, $scoreMaximum, $comment, $id,
                        $grade_token, $extra13, $debug_log);

        return $status;
    }

    /**
     * Load the results for a line item
     *
     * @param string $id The REST endpoint (id) for this line item
     * @param array|false &$debug_log Returns a log of actions taken
     *
     * @return array|string If this works it returns the Results array.  If it fails,
     * it returns a string with error details.
     */
    public function loadResults($id, &$debug_log=false) {

        // TODO: Further evidence that $subject might want to be client_id
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( is_string($missing) && U::strlen($missing) > 0 ) return $missing;
        if ( ! $grade_token ) return "Unable to get grade_token";

        $results = LTI13::loadResults($id, $grade_token, $debug_log);
        return $results;
    }

}
