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

    /**
     * Load the LTI 1.3 data from the session, checking for sanity
     *
     * @param $lti13_token_url The token URL (output)
     * @param $privkey The current private key (output)
     * @param $kid The current kid for the public key (output)
     * @param $lti13_token_audience The current optional token audience (output)
     * @param $issuer_client The current client_id (output)
     * @param $deployment_id The current deployment_id (output)
     *
     * @return string When the string is non-empty, it means an error has occurred and
     * the string contains the error detail.
     *
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
        if ( strlen($issuer_client) < 1 ) $missing .= ' ' . 'issuer_client';
        if ( strlen($privkey) < 1 ) $missing .= ' ' . 'private_key';
        if ( strlen($kid) < 1 ) $missing .= ' ' . 'public_key kid';
        if ( strlen($lti13_token_url) < 1 ) $missing .= ' ' . 'token_url';
        $missing = trim($missing);
        return($missing);
    }

    /**
     * Load the roster if we can get it from the LMS
     *
     * @param $with_sourcedids If true, ask for the sourcedids
     * @param array $debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return mixed If this works it returns the NRPS object.  If it fails,
     * it returns a string.
     *
     */
    public function loadNamesAndRoles($with_sourcedids=false, &$debug_log=false) {
        global $CFG;

        $missing = $this->loadLTI13Data($lti13_token_url, $privkey, $kid, $lti13_token_audience, $issuer_client, $deployment_id);
        $lti13_membership_url = $this->launch->ltiParameter('lti13_membership_url');
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
            $nrps_access_token = LTI13::getNRPSWithSourceDidsToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        } else {
            $nrps_access_token = LTI13::getNRPSToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
        }

        $nrps = LTI13::loadNRPS($lti13_membership_url, $nrps_access_token, $debug_log);
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
        if ( strlen($lti13_lineitems) < 1 ) $missing .= ' ' . 'lineitems';

        $missing = trim($missing);
        if ( strlen($missing) > 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing: '.$missing;
            return false;
        }

        // TODO: In the future we might cache this access token perhaps in session for a while
        $lineitems_access_token = LTI13::getLineItemsToken($issuer_client, $lti13_token_url, $privkey, $kid, $lti13_token_audience, $debug_log);
        return $lineitems_access_token;
    }

    /**
     * Load our lineitems from the LMS
     *
     * @param $search mixed - search values to apply to the load
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItems array.  If it fails,
     * it returns a string.
     */
    public function loadLineItems($search=false, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
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
     * Load the detiail for a lineitem from the LMS
     *
     * @param $id mixed - search values to apply to the load
     *     $lineitem_id = $lineitems[0]->id;
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function loadLineItem($id, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get LineItems access_token";

        $lineitem = LTI13::loadLineItem($id, $lineitems_access_token, $debug_log);
        return $lineitem;
    }

    /**
     * Create a lineitem in the LMS
     *
     * @param object $newitem The fields to update
     *
     *     $newitem = new \stdClass();
     *     $newitem->scoreMaximum = 100;
     *     $newitem->label = 'Week 3 Feedback';
     *     $newitem->resourceId = '2987487943';
     *     $newitem->tag = 'optional';
     *
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function createLineItem($newitem, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get lineitems_access_token";

        $retval = LTI13::createLineItem($lti13_lineitems, $lineitems_access_token, $newitem, $debug_log);

        return $retval;
    }

    /**
     * Delete a lineitem from the LMS
     *
     * @param $id mixed - search values to apply to the load
     *     $lineitem_id = $lineitems[0]->id;
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns true.  If it fails, it returns a string.
     */
    public function deleteLineItem($id, &$debug_log=false) {
        $lineitems_access_token = self::getLineItemsToken($missing, $lti13_lineitems, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
        if ( ! $lineitems_access_token ) return "Unable to get LineItems access_token";

        $lineitem = LTI13::deleteLineItem($id, $lineitems_access_token, $debug_log);
        return $lineitem;
    }

    /**
     * Update a lineitem in the LMS
     *
     * @param $id mixed - search values to apply to the load
     *     $lineitem_id = $lineitems[0]->id;
     * @param object $newitem The fields to update
     *
     *     $newitem = new \stdClass();
     *     $newitem->scoreMaximum = 100;
     *     $newitem->label = 'Week 3 Feedback';
     *     $newitem->resourceId = '2987487943';
     *     $newitem->tag = 'optional';
     *
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItem.  If it fails, it returns a string.
     */
    public function updateLineItem($id, $newitem, &$debug_log=false) {
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
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
        if ( strlen($missing) > 0 ) {
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
     * @param $id The REST endpoint (id) for this line item
     * @param $user_key The user for this grade
     * @param $grade Value to send
     * @param $scoreMaximum What the score is relative to
     * @param $comment An optional comment
     * @param $debug_log Returns a log of actions taken
     * @param $extra13 A key/value store of extra LTI1.3 parameters
     *
     * @return mixed If this is a success a true is returned, if not a string with an error
     * is returned.
     */
    public function sendLineItemResult($id, $user_key, $grade, $scoreMaximum, $comment, &$debug_log=false, $extra13=false) {
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
        if ( ! $grade_token ) return "Unable to get grade_token";

        $status = LTI13::sendLineItemResult($user_key, $grade, $scoreMaximum, $comment, $id,
                        $grade_token, $extra13, $debug_log);

        return $status;
    }

    /**
     * Load the results for a line item
     *
     * @param $id The REST endpoint (id) for this line item
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the Results array.  If it fails,
     * it returns a string.
     */
    public function loadResults($id, &$debug_log=false) {

        // TODO: Further evidence that $subject might want to be client_id
        $grade_token = self::getGradeToken($missing, $subject, $debug_log);
        if ( strlen($missing) > 0 ) return $missing;
        if ( ! $grade_token ) return "Unable to get grade_token";

        $results = LTI13::loadResults($id, $grade_token, $debug_log);
        return $results;
    }

}
