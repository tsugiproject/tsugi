<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;
use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

/**
 * This is a general purpose LTI 1.3 class with no Tsugi-specific dependencies.
 *
 * https://www.imsglobal.org/spec/lti/v1p3/
 */
class LTI13 {

    const VERSION_CLAIM =       'https://purl.imsglobal.org/spec/lti/claim/version';
    const MESSAGE_TYPE_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/message_type';
    const MESSAGE_TYPE_RESOURCE = 'LtiResourceLinkRequest';
    const MESSAGE_TYPE_DEEPLINK = 'LtiDeepLinkingRequest';
    const MESSAGE_TYPE_CONTENT_REVIEW = 'LtiSubmissionReviewRequest';
    const MESSAGE_TYPE_PRIVACY = 'DataPrivacyLaunchRequest';
    const RESOURCE_LINK_CLAIM = 'https://purl.imsglobal.org/spec/lti/claim/resource_link';
    const CONTEXT_ID_CLAIM =    'https://purl.imsglobal.org/spec/lti/claim/context';
    const DEPLOYMENT_ID_CLAIM = 'https://purl.imsglobal.org/spec/lti/claim/deployment_id';
    const ROLES_CLAIM =         'https://purl.imsglobal.org/spec/lti/claim/roles';
    const PRESENTATION_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/launch_presentation';
    const LTI11_TRANSITION_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/lti1p1';
    const FOR_USER_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/for_user';

    const NAMESANDROLES_CLAIM = 'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
    const ENDPOINT_CLAIM =      'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';
    const DEEPLINK_CLAIM =      'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings';

    const CUSTOM_CLAIM =        'https://purl.imsglobal.org/spec/lti/claim/custom';

    const MEDIA_TYPE_MEMBERSHIPS = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';
    const MEDIA_TYPE_LINEITEM = 'application/vnd.ims.lis.v2.lineitem+json';
    const MEDIA_TYPE_LINEITEMS = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
    const SCORE_TYPE = 'application/vnd.ims.lis.v1.score+json';
    const RESULTS_TYPE = 'application/vnd.ims.lis.v2.resultcontainer+json';

    const TOOL_PLATFORM_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/tool_platform';
    const PRODUCT_FAMILY_CODE = "product_family_code";

    // https://www.imsglobal.org/spec/lti-ags/v2p0#score-publish-service
    const LINEITEM_TIMESTAMP = "timestamp";
    const LINEITEM_SCOREGIVEN = "scoreGiven";
    const LINEITEM_SCOREMAXIMUM = "scoreMaximum";
    const LINEITEM_COMMENT = "comment";
    const LINEITEM_USERID = "userId";

    // https://www.imsglobal.org/spec/lti-ags/v2p0#activityprogress
    const ACTIVITY_PROGRESS = 'activityProgress';
    const ACTIVITY_PROGRESS_INITIALIZED = 'Initialized';
    const ACTIVITY_PROGRESS_STARTED = 'Started';
    const ACTIVITY_PROGRESS_INPROGRESS = 'InProgress';
    const ACTIVITY_PROGRESS_SUBMITTED = 'Submitted';
    const ACTIVITY_PROGRESS_COMPLETED = 'Completed';

    // https://www.imsglobal.org/spec/lti-ags/v2p0#gradingprogress
    const GRADING_PROGRESS = 'gradingProgress';
    const GRADING_PROGRESS_FULLYGRADED = 'FullyGraded';
    const GRADING_PROGRESS_PENDING = 'Pending';
    const GRADING_PROGRESS_PENDINGMANUAL = 'PendingManual';
    const GRADING_PROGRESS_FAILED = 'Failed';
    const GRADING_PROGRESS_NOTREADY = 'NotReady';

    /**
     * Pull out the issuer_key from a JWT
     *
     * @param string $jwt The parsed JWT
     */
    public static function extract_issuer_key($jwt) {
        $retval = self::extract_issuer_key_string($jwt->body->iss);
        return $retval;
    }

    /**
     * Pull out the composite issuer_key from issuer and audience
     *
     * @param string $jwt The parsed JWT
     */
    public static function extract_issuer_key_string($issuer) {
        $retval = U::lti_sha256($issuer);
        return $retval;
    }
    /**
     * Find the JWT in the request data
     *
     * @param array $request_data An optional prarameter if you want to pull the
     * data from somewhere other than $_REQUEST.
     *
     * @return string The JWT from the request or false if there is no JWT.
     */
    public static function raw_jwt($request_data=false) {
        if ( $request_data === false ) $request_data = $_REQUEST;
        $raw_jwt = U::get($request_data, 'id_token');
        if ( ! $raw_jwt ) return false;
        return $raw_jwt;
    }

    /**
     * Parse and validate a raw JWT
     *
     * @param string $raw_jwt The encoded JWT (a string)
     * @param boolean $required_fields Whether to throw an error if the required fields are missing.
     * You can set this to false if you just want to parse and dump a JWT for debugging.
     *
     * @return mixed The parsed fields in an object as long as there are no errors.
     * If there are errors, a string with the error message is returned.
     */
    public static function parse_jwt($raw_jwt, $required_fields=true) {
        if ( $raw_jwt === false ) return false;
        if ( ! is_string($raw_jwt)) return 'parse_jwt first parameter must be a string';
        $jwt_parts = explode('.', $raw_jwt);
        if ( count($jwt_parts) < 2 ) return "jwt must have at least two parts";
        $jwt_header = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]));
        if ( ! $jwt_header ) return "Could not decode jwt header";
        if ( ! isset($jwt_header->alg) ) return "Missing alg from jwt header";
        $jwt_body = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]));
        if ( ! $jwt_body ) return "Could not decode jwt body";
        if ( $required_fields && ! isset($jwt_body->iss) ) return "Missing iss from jwt body";
        if ( $required_fields && ! isset($jwt_body->aud) ) return "Missing aud from jwt body";
        if ( $required_fields && ! isset($jwt_body->exp) ) return "Missing exp from jwt body";
        $jwt = new \stdClass();
        $jwt->header = $jwt_header;
        $jwt->body = $jwt_body;
        if ( count($jwt_parts) > 2 ) {
            $jwt_extra = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);
            if ( $jwt_body ) $jwt->extra = $jwt_extra;
        }
        return $jwt;
    }

    /**
     * Print out the contents of the JWT
     *
     * @param object The parsed JWT object.
     *
     * @return string The output of the JWT suitable for printing (escaping needed)
     */
    public static function dump_jwt($jwt) {
        if ( ! $jwt ) "JWT is false";
        if ( is_string($jwt) ) {
            return "Error parsing JWT: $jwt";
        } else {
            $retval = "Parsed JWT:\n";
            $retval .= json_encode($jwt->header, JSON_PRETTY_PRINT);
            $retval .= "\n";
            $retval .= json_encode($jwt->body, JSON_PRETTY_PRINT);
            $retval .= "\n";
            return $retval;
        }
    }

    /**
     * Returns true if this is an LTI 1.3 message with minimum values to meet the protocol
     *
     * @param array $request_data An optional prarameter if you want to pull the
     * data from somewhere other than $_REQUEST.
     *
     * @return Returns true if this has a valid JWT, false if this is not a JWT at all,
     * or a string with an error message if this parses as a JWT but is missing required data.
     */
    public static function isRequestDetail($request_data=false) {
        $raw_jwt = self::raw_jwt($request_data);
        if ( ! $raw_jwt ) return false;
        $jwt = self::parse_jwt($raw_jwt);
        if ( is_string($jwt) ) {
            return $jwt;
        }
        return is_object($jwt);
    }

    /**
     * Returns true if this is an LTI 1.3 message with minimum values to meet the protocol
     *
     * @param array $request_data An optional prarameter if you want to pull the
     * data from somewhere other than $_REQUEST.
     *
     * @return Returns true if this has a valid JWT, false if this is not a JWT at all.
     */
    public static function isRequest($request_data=false) {
        $retval = self::isRequestDetail($request_data);
        if ( is_string($retval) ) {
            error_log("Bad launch ".$retval);
            return false;
        }
        return is_object($retval);
    }

    /**
     * Verify the Public Key for this request
     *
     * @param string $raw_jwt The raw JWT from the request
     * @param string $public_key The public key
     * @param array $algs The algorithms to use for validating the key.
     *
     * @return mixed This returns true if the request verified.  If the request did not verify,
     * this returns the exception that was generated.
     */
    public static function verifyPublicKey($raw_jwt, $public_key, $algs=false) {
        if ( ! $algs ) $algs = array('RS256');

        // From Google/AccessToken/Verify.php
        if (property_exists('\Firebase\JWT\JWT', 'leeway')) {
            // adds 60 seconds to JWT leeway - mostly to allow a server with a slightly ahead time to work
            // @see https://github.com/google/google-api-php-client/issues/827
            JWT::$leeway = 60;
        }

        try {
            $decoded = JWT::decode($raw_jwt, $public_key, $algs);
            return true;
        } catch(\Exception $e) {
            return $e;
        }
    }

    /** Check the incoming message type
     *
     * @param string $lti_message_type The incoming message type from the request.
     *
     * @return boolean True if this is an LTI 1.1 or LTI 1.3 message type.
     */
    public static function isValidMessageType($lti_message_type) {
        return ($lti_message_type == "basic-lti-launch-request" ||
            $lti_message_type == 'LtiResourceLinkRequest' ||
            $lti_message_type == "ContentItemSelectionRequest");
    }

    /** Check the incoming message version
     *
     * @param string $lti_version The incoming message type from the request.
     *
     * @return boolean True if this is an LTI 1.1 or LTI 2.0 message version.
     */
    public static function isValidVersion($lti_version) {
        return ($lti_version == "LTI-1p0" || $lti_version == "LTI-2p0");
    }

    /**
     * Apply Jon Postel's Law as appropriate
     *
     * Postel's Law - https://en.wikipedia.org/wiki/Robustness_principle
     *
     * "TCP implementations should follow a general principle of robustness:
     * be conservative in what you do, be liberal in what you accept from others."
     *
     * By default, Jon Postel mode is off and we are stricter than we need to be.
     * This works well because it reduces the arguments with the certification
     * folks.   But if you add:
     *
     *      $CFG->jon_postel = true;
     *
     * Tsugi will follow Jon Postel's law.
     *
     * @param object $body The body of the JWT
     * @param array $failures A string array of failures (pass by reference)
     */
    public static function jonPostel($body, &$failures) {
		global $CFG;
        if ( isset($CFG->jon_postel) ) return; // We are on Jon Postel mode

        // Sanity checks
        $version = false;
        if ( isset($body->{self::VERSION_CLAIM}) ) $version = $body->{self::VERSION_CLAIM};
        if ( strpos($version, '1.3') !== 0 ) $failures[] = "Bad LTI version: ".$version;

        $message_type = false;
        if ( isset($body->{self::MESSAGE_TYPE_CLAIM}) ) $message_type = $body->{self::MESSAGE_TYPE_CLAIM};
        if ( ! $message_type ) {
            $failures[] = "Missing message type";
        } else if ( $message_type == self::MESSAGE_TYPE_RESOURCE ) {
            // Required
            if ( ! isset($body->{self::RESOURCE_LINK_CLAIM}) ) $failures[] = "Missing required resource_link claim";
            if ( ! isset($body->{self::RESOURCE_LINK_CLAIM}->id) ) $failures[] = "Missing required resource_link id";
        } else if ( $message_type == self::MESSAGE_TYPE_DEEPLINK ) {
            // OK
        } else if ( $message_type == self::MESSAGE_TYPE_CONTENT_REVIEW ) {
            // OK
        } else if ( $message_type == self::MESSAGE_TYPE_PRIVACY ) {
            // OK
        } else {
            $failures[] = "Bad message type: ".$message_type;
        }

        if ( ! isset($body->{self::ROLES_CLAIM}) ) $failures[] = "Missing required role claim";
        if ( ! isset($body->{self::DEPLOYMENT_ID_CLAIM}) ) $failures[] = "Missing required deployment_id claim";
    }

    /** Retrieve a grade token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getGradeToken($subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, &$debug_log=false) {

        $token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
            "https://purl.imsglobal.org/spec/lti-ags/scope/score",
            "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly"
        // ], $subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $debug_log);
        ], $subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, $debug_log);

        return self::extract_access_token($token_data, $debug_log);
    }

    /** Retrieve a Names and Roles Provisioning Service (NRPS) token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getNRPSToken($subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, &$debug_log=false) {

         $roster_token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, $debug_log);

        return self::extract_access_token($roster_token_data, $debug_log);
    }

    /** Retrieve a Names and Roles Provisioning Service (NRPS) token with source_dids
     *
     * This should require both the lineitems and grade permission I think.   But some clarification
     * is needed to make sure this is done correctly.
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getNRPSWithSourceDidsToken($subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, &$debug_log=false) {

        $roster_token_data =  self::get_access_token([
            // TODO: Uncomment this after (I think) the certification suite tolerates extra stuff
            // "https://purl.imsglobal.org/spec/lti-ags/scope/basicoutcome",
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, $debug_log);

        return self::extract_access_token($roster_token_data, $debug_log);
    }

    /** Retrieve a LineItems token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getLineItemsToken($subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, &$debug_log=false) {
        $token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        ], $subject, $lti13_token_url, $lti13_privkey, $lti13_kid, $lti13_token_audience, $deployment_id, $debug_log);

        return self::extract_access_token($token_data, $debug_log);
    }

    /** Send a line item result
     *
     * @param $user_id The user for this grade
     * @param $grade Value to send
     * @param $scoreMaximum The amount that $grade is realative to
     * @param $comment An optional comment
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param array $extra A set of key value extensions to be added/replaced in the request
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function sendLineItemResult($user_id, $grade, $scoreMaximum, $comment, $lineitem_url,
        $access_token, $extra=false, &$debug_log=false) {

        if ( strlen($user_id) < 1 ) {
            if ( is_array($debug_log) ) $debug_log[] = 'Missing user_id';
            return false;
        }

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();

        // https://www.imsglobal.org/spec/lti-ags/v2p0#scoregiven-and-scoremaximum
        // From Karen L: "All 'scoreGiven' values MUST be positive numeric".
        if ( is_numeric($grade) ) $grade = floatval($grade);
        if ( is_numeric($scoreMaximum) ) $scoreMaximum = floatval($scoreMaximum);

        // An empty grade is considered a "delete" request
        // user_id comes from the "sub" in the JWT launch
        $grade_call = [
            // "timestamp" => "2017-04-16T18:54:36.736+00:00",
            self::LINEITEM_TIMESTAMP => U::iso8601(),
            self::LINEITEM_SCOREGIVEN => $grade,
            self::LINEITEM_SCOREMAXIMUM => $scoreMaximum,
            self::LINEITEM_COMMENT => $comment,
            self::ACTIVITY_PROGRESS => self::ACTIVITY_PROGRESS_COMPLETED,
            self::GRADING_PROGRESS => self::GRADING_PROGRESS_FULLYGRADED,
            self::LINEITEM_USERID => $user_id,
        ];

        // Allow the extra to override any of the normal values - trust the caller :)
        if ( is_array($extra) ) {
            $grade_call = array_merge($grade_call, $extra);
        }

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Content-Type: '.self::SCORE_TYPE,
            'Accept: '.self::SCORE_TYPE
        ];

        // echo("\n---\n$lineitem_url\n-----\n");
        $pos = strpos($lineitem_url, '?');
        $actual_url = $pos === false ? $lineitem_url . '/scores' : substr_replace($lineitem_url, '/scores', $pos, 0);
        curl_setopt($ch, CURLOPT_URL, $actual_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($grade_call));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ( is_array($debug_log) ) $debug_log[] = "Scores Url: ".$actual_url;
        if ( is_array($debug_log) ) $debug_log[] = $headers;
        if ( is_array($debug_log) ) $debug_log[] = $grade_call;

        $line_item = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        // echo $line_item;
        if ( is_array($debug_log) ) $debug_log[] = "Sent line item, received status=$httpcode\n".$line_item;

        if ( ! Net::httpSuccess($httpcode) ) {
            $json = json_decode($line_item, true);
            $status = U::get($json, "error", "Unable to send lineitem ".$httpcode);
            if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
            return $status;
        }

        return true;
    }

    /**
     * Load the memberships and roles if we can get it from the LMS
     *
     * @param string $membership_url The REST endpoint for memberships
     * @param $access_token The access token for this request
     * @param array $debug_log If this is an array, debug information is returned as the
     * process progresses.
     *
     * @return mixed If this works it returns the NRPS object.  If it fails,
     * it returns a string.
     */
    public static function loadNRPS($membership_url, $access_token, &$debug_log=false) {

        $ch = curl_init();

        $membership_url = trim($membership_url);

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Accept: '.self::MEDIA_TYPE_MEMBERSHIPS,
            'Content-Type: '.self::MEDIA_TYPE_MEMBERSHIPS // TODO: Remove when certification is fixed
        ];

        curl_setopt($ch, CURLOPT_URL, $membership_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ( is_array($debug_log) ) $debug_log[] = $membership_url;
        if ( is_array($debug_log) ) $debug_log[] = $headers;

        $membership = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent roster request, received status=$httpcode (".strlen($membership)." characters)";

        if ( strlen($membership) < 1 ) {
            return "No data retrieved status=" . $httpcode;
        }

        $json = json_decode($membership, false);   // Top level object
        if ( $json === null ) {
            $retval = "Unable to parse returned roster JSON:". json_last_error_msg();
            if ( is_array($debug_log) ) {
                if (is_array($debug_log) ) $debug_log[] = $retval;
                if (is_array($debug_log) ) $debug_log[] = substr($membership, 0, 3000);
            }
            return $retval;
        }

        if ( Net::httpSuccess($httpcode) && isset($json->members) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded ".count($json->members)." roster entries";
            return $json;
        }

        $status = isset($json->error) ? $json->error : "Unable to load results";
        if ( is_array($debug_log) ) {
            $debug_log[] = "Error status: $status";
            if (is_array($debug_log) ) $debug_log[] = substr($membership, 0, 3000);
        }
        return $status;
    }

    /**
     * Load our lineitems from the LMS
     *
     * @param $lineitems_url The REST endpoint (id) for the line items
     * @param $access_token The access token for this request
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItems array.  If it fails,
     * it returns a string.
     */
    public static function loadLineItems($lineitems_url, $access_token, &$debug_log=false) {

        $lineitems_url = trim($lineitems_url);

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Accept: '.self::MEDIA_TYPE_LINEITEMS,
            // 'Content-Type: '.self::MEDIA_TYPE_LINEITEMS // TODO: Remove when certification is fixed
        ];
        curl_setopt($ch, CURLOPT_URL, $lineitems_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($debug_log) ) $debug_log[] = 'Line Items URL: '.$lineitems_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $lineitems = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent lineitems request, received status=$httpcode (".strlen($lineitems)." characters)";

        $json = json_decode($lineitems, false);
        if ( $json === null ) {
            $retval = "Unable to parse returned lineitems JSON:". json_last_error_msg();
            if ( is_array($debug_log) ) {
                if (is_array($debug_log) ) $debug_log[] = $retval;
                if (is_array($debug_log) ) $debug_log[] = substr($lineitems, 0, 1000);
            }
            return $retval;
        }
        if ( Net::httpSuccess($httpcode) && is_array($json) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded ".count($json)." lineitems entries";
            return $json;
        }

        $status = isset($json->error) ? $json->error : "Unable to load results";
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
        return $status;
    }

    /**
     * Load the detiail for a lineitem from the LMS
     *
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the LineItem object.  If it fails, it returns a string.
     */
    public static function loadLineItem($lineitem_url, $access_token, &$debug_log=false) {

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();
        $headers = [
            'Authorization: Bearer '. $access_token,
            'Accept: '.self::MEDIA_TYPE_LINEITEM,
        ];

        curl_setopt($ch, CURLOPT_URL, $lineitem_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($debug_log) ) $debug_log[] = 'Line Items URL: '.$lineitem_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $lineitem = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent lineitem request, received status=$httpcode (".strlen($lineitem)." characters)";

        $json = json_decode($lineitem, false);
        if ( $json === null ) {
            $retval = "Unable to parse returned lineitem JSON:". json_last_error_msg();
            if ( is_array($debug_log) ) {
                if (is_array($debug_log) ) $debug_log[] = $retval;
                if (is_array($debug_log) ) $debug_log[] = substr($lineitem, 0, 1000);
            }
            return $retval;
        }

        if ( Net::httpSuccess($httpcode) && is_object($json) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded lineitem";
            return $json;
        }

        $status = isset($json->error) ? $json->error : "Unable to load results";
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
        return $status;
    }

    /**
     * Load the results for a line item
     *
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns the Results array.  If it fails,
     * it returns a string.
     */
    public static function loadResults($lineitem_url, $access_token, &$debug_log=false) {

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Content-Type: '.self::RESULTS_TYPE,   //  TODO: Convince Claude this is wrong
            'Accept: '.self::RESULTS_TYPE
        ];

        $actual_url = $lineitem_url."/results";
        curl_setopt($ch, CURLOPT_URL, $actual_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($debug_log) ) $debug_log[] = 'Line Items URL: '.$actual_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $results = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent results request, received status=$httpcode (".strlen($results)." characters)";
        if ( is_array($debug_log)) $debug_log[] = substr($results, 0, 3000);

        $json = json_decode($results, false);
        if ( $json === null ) {
            $retval = "Unable to parse returned results JSON:". json_last_error_msg();
            if ( is_array($debug_log) ) $debug_log[] = $retval;
            return $retval;
        }

        // NOTE:
        // Even though the LTIAdvantage spec says array, best practice is to enclose
        // in an object and the array as an attirbute - so we accept both in case this
        // gets cleaned up in a future release of LTI Advantage
        $results = $json;
        if ( isset($json->results) && is_array($json->results) ) {
            $results = $json->results;
        }

        if ( Net::httpSuccess($httpcode) && is_array($results) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded results";
            return $results;
        }

        $status = isset($json->error) ? $json->error : "Unable to load results";
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
        return $status;
    }

    /**
     * Delete a lineitem from the LMS
     *
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns true.  If it fails, it returns a string.
     */
    public static function deleteLineItem($lineitem_url, $access_token, &$debug_log=false) {

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer '. $access_token
        ];

        curl_setopt($ch, CURLOPT_URL, $lineitem_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        if (is_array($debug_log) ) $debug_log[] = 'Line Item URL: '.$lineitem_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent lineitem delete, received status=$httpcode (".strlen($response)." characters)";

        if ( Net::httpSuccess($httpcode) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Deleted lineitem";
            return true;
        }

        if ( strlen($response) < 1 ) {
            return "Failed with no response body and code=".$httpcode;
        }

        $json = json_decode($response, false);
        if ( $json === null ) {
            $retval = "Unable to parse returned lineitem JSON:". json_last_error_msg();
            if ( is_array($debug_log) ) {
                if (is_array($debug_log) ) $debug_log[] = $retval;
                if (is_array($debug_log) ) $debug_log[] = substr($lineitem, 0, 1000);
            }
            return $retval;
        }

        $status = U::get($json, "error", "Unable to delete lineitem");
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
        return $status;
    }

    /**
     * Create a lineitem in the LMS
     *
     * @param $lineitems_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param object $lineitem The fields for the new line item
     *
     *     $newitem = new \stdClass();
     *     $newitem->scoreMaximum = 100;
     *     $newitem->label = 'Week 3 Feedback';
     *     $newitem->resourceId = '2987487943';
     *     $newitem->tag = 'optional';
     *
     * @param $debug_log Returns a log of actions taken
     *
     * @return mixed If this works it returns true.  If it fails, it returns a string.
     */
    public static function createLineItem($lineitems_url, $access_token, $lineitem, &$debug_log = false) {

        $lineitems_url = trim($lineitems_url);

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Content-Type: ' . self::MEDIA_TYPE_LINEITEM
        ];

        curl_setopt($ch, CURLOPT_URL, $lineitems_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($lineitem));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($debug_log) ) $debug_log[] = 'Line Items URL: '.$lineitems_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $line_item = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        if ( is_array($debug_log) ) $debug_log[] = "Created line item, received status=$httpcode\n".$line_item;

        if ( ! Net::httpSuccess($httpcode) ) {
            $json = json_decode($line_item, true);
            $status = U::get($json, "error", "Unable to create lineitem");
            if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
            return $status;
        }

        return true;
    }

    /**
     * Update a lineitem in the LMS
     *
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
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
     * @return mixed If this works it returns true.  If it fails, it returns a string.
     */
    public static function updateLineItem($lineitem_url, $access_token, $lineitem, &$debug_log = false) {

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Content-Type: ' . self::MEDIA_TYPE_LINEITEM
        ];

        curl_setopt($ch, CURLOPT_URL, $lineitem_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($lineitem));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($debug_log) ) $debug_log[] = 'Line Item URL: '.$lineitem_url;
        if (is_array($debug_log) ) $debug_log[] = $headers;

        $line_item = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        if ( is_array($debug_log) ) $debug_log[] = "Updated line item, received status=$httpcode\n".$line_item;

        if ( ! Net::httpSuccess($httpcode) ) {
            $json = json_decode($line_item, true);
            $status = U::get($json, "error", "Unable to update lineitem");
            if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
            return $status;
        }

        return true;
    }

    /** Retrieve an access token
     *
     * @param array $scope A list of requested scopes
     * @param string $subject Who we are (client id in OAuth)
     * @param string $lti13_token_url
     * @param string $lti13_privkey
     * @param string $lti13_kid The optional kid to include in the JWT
     * @param string $deployment_id The optional deployment_id to include in the JWT
     * @param string $lti13_token_audience The optional value for token audience.  If not
     * provided, use the $lti13_token_url.
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * Note - the idea of lti13_token_audience came in the following PR
     * https://github.com/IMSGlobal/lti-spec-guides/pull/11/files
     *
     * @return array The retrieved and parsed JSON data.  There is no validation performed,
     * and we might have got a 403 and received no data at all.
     */
    public static function get_access_token($scope, $subject, $lti13_token_url, $lti13_privkey, $lti13_kid=false, $lti13_token_audience=false, $deployment_id=false, &$debug_log=false) {

        $lti13_token_url = trim($lti13_token_url);
        $subject = trim($subject);
        $audience = $lti13_token_url;
        if ( $lti13_token_audience ) {
            $audience = trim($lti13_token_audience);
        }

        if ( ! is_string($scope) ) {
            $scope = implode(' ',$scope);
        }

        $jwt_claim = self::base_jwt($subject, $subject, $debug_log);
        $jwt_claim["aud"] = $audience;
        if ( $deployment_id ) {
            $jwt_claim[self::DEPLOYMENT_ID_CLAIM] = $deployment_id;
        }

        // echo("<pre>\n");var_dump($jwt_claim);echo("</pre>\n");die();

        $jwt = self::encode_jwt($jwt_claim, $lti13_privkey, $lti13_kid);

        $auth_request = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $jwt,
            'scope' => $scope
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$lti13_token_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ( is_array($debug_log) ) $debug_log[] = "Token Url: ".$lti13_token_url;
        if ( is_array($debug_log) ) $debug_log[] = $auth_request;

        $token_str = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( is_array($debug_log) ) $debug_log[] = "Returned token code $httpcode\n".$token_str;
        $token_data = json_decode($token_str, true);

        curl_close ($ch);

        return $token_data;
    }

    /** Extract an access token from returned data
     *
     * @param array $token_data The JSON response to a token request, parsed in an array
     * of key / value pairs.
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed This returns the token as a string if it is successful, or false
     */
    public static function extract_access_token($token_data, &$debug_log=false) {
        if ( ! $token_data ) return false;

        if ( ! isset($token_data['access_token']) ) return false;

        $access_token = $token_data['access_token'];
        if ( is_array($debug_log) ) {
            // Parse the JWT if it is a JWT
            $required_fields = false;
            $jwt = LTI13::parse_jwt($access_token, $required_fields);
            if ( $jwt && ! is_string($jwt) ) $debug_log[] = self::dump_jwt($jwt);
        }
        return $access_token;
    }

    /** Build up a basic JWT
     *
     * @param string $issuer Who we are
     * @param string $subject Who we are
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return array The basic fields of the JWT are populated
     */
    public static function base_jwt($issuer, $subject, &$debug_log=false) {

        $jwt_claim = [
            "iss" => $issuer,
            "sub" => $subject,
            "iat" => time(),
            "exp" => time()+60,
            // Yup this is weird - nonce is not a jwt concept in general
            // but ContentItemResponse strangely requires it...  - Learned at D2L
            // "nonce" => md5(time()-60),
            "jti" => uniqid($issuer)
        ];
        return $jwt_claim;
    }

    /** Sign and encode a JWT
     *
     * @param array $jwt_claim An array of key/value pairs for the claims
     * @param string $lti13_privkey The private key to use to sign the JWT
     * @param string $lti13_kid The key id to include in the JWT (optional)
     *
     * @return string The signed JWT
     */
    public static function encode_jwt($jwt_claim, $lti13_privkey, $lti13_kid=false) {
        if ( $lti13_kid ) {
            $jws = JWT::encode($jwt_claim, self::cleanup_PKCS8($lti13_privkey), 'RS256', $lti13_kid);
        } else {
            $jws = JWT::encode($jwt_claim, self::cleanup_PKCS8($lti13_privkey), 'RS256');
        }
        return $jws;
    }

    /** Build an HTML form to submit a JWT
     *
     * @param string $launch_url The URL to send the JWT
     * @param string $jws The signed JWT
     * @param boolean dodebug Whether to auto submit the JWT or pause with some
     * debugging output.
     * @param array $extra some extra/optional parameters
     *
     *     formattr - Additional text to include within the <form tag
     *     button - The text of the botton (ie. to allow I18N)
     *
     *  @return string The HTML to send to the browser
     */
    public static function build_jwt_html($launch_url, $jws, $dodebug=true, $extra=false) {
        $form_id = uniqid();
        $html = "<form action=\"" . $launch_url . "\" method=\"POST\" id=\"".$form_id."\"";
        if ($extra && isset($extra['id']) ) {
            $html .= ' id="'.$extra['id'].'"';
        }

        if ($extra && isset($extra['formattr']) ) {
            $html .= ' '.$extra['formattr'];
        }
        $button = ($extra && isset($extra['button'])) ? $extra['button'] : 'Go!';
        $html .= ">\n"
                . "    <input type=\"hidden\" name=\"JWT\" value=\"" . htmlspecialchars($jws) . "\" />\n"
                . "    <input type=\"submit\" value=\"".$button."\" />\n"
                . "</form>\n";

        if ($dodebug) {
            $jwt = self::parse_jwt($jws, false);
            $html .=   "<p>\n--- Encoded JWT:<br/>"
                    . htmlspecialchars($jws)
                    . "</p>\n"
                    . "<p>\n--- JWT:<br/><pre>"
                    . htmlspecialchars(json_encode($jwt->body, JSON_PRETTY_PRINT))
                    . "</pre></p>\n";
        } else {
             $html .= " <script type=\"text/javascript\"> \n" .
                "  //<![CDATA[ \n" .
                "    document.getElementById(\"".$form_id."\").style.display = \"none\";\n" .
                "    document.getElementById(\"".$form_id."\").submit(); \n" .
                "    console.log('Autosubmitted ".$form_id."'); \n" .
                "  //]]> \n" .
                " </script> \n";
        }
        return $html;
    }

    /**
     * Generate a PKCS8 Ppublic / private key pair
     *
     * @param string $publicKey Returned public key
     * @param string $privateKey Returned private key
     *
     * @return string or true If there was an error, we return it, on success return true
     */
    // https://stackoverflow.com/questions/6648337/generate-ssh-keypair-form-php
    public static function generatePKCS8Pair(&$publicKey, &$privateKey) {
        $privKey = openssl_pkey_new(
            array('digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA));

        if ( $privKey === false ) {
            $error = openssl_error_string();
            error_log("generatePKCS8Pair error="+$error);
            $privateKey = null;
            $publicKey = null;
            return $error;
        }

        // Private Key
        $privKey = openssl_pkey_get_private($privKey);
        openssl_pkey_export($privKey, $privateKey);

        // Public Key
        $pubKey = openssl_pkey_get_details($privKey);
        $publicKey = $pubKey['key'];
        return true;
    }

    /** Cleanup common mess-ups in PKCS8 strings
     *
     * Often when public/private keys are pasted, stuff is added or
     * lines run together or stuff is missing from the string.
     * The PHP library is a little picky on these things so this
     * routine just checks for common boo-boos and fixes them.
     * As they say in Office Space, "We fixed the glitch."
     *
     * @param string $private_key The possible ill-formatted private key
     *
     * @return string The hopefully better formatted private key
     */
    public static function cleanup_PKCS8($private_key)
    {
        $parts = preg_split('/\s+/', $private_key);
        $better = "";
        $indashes = false;
        foreach($parts as $part) {
            if ( strpos($part,'-----') === 0 ) {
                if ( strlen($better) > 0 ) $better .= "\n";
                $better .= $part;
                $indashes = true;
                continue;
            }
            if ( U::endsWith($part,'-----') > 0 ) {
                $better .= ' ' . $part;
                $indashes = false;
                continue;
            }
            $better .= $indashes ? ' ' : "\n";
            $better .= $part;
        }
        return $better;
    }

    // https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
    /*
        sign=base64(hmac_sha256(utf8bytes('179248902&689302&https://lmsvendor.com&PM48OJSfGDTAzAo&1551290856&172we8671fd8z'), utf8bytes('my-lti11-secret')))

        {
            "nonce": "172we8671fd8z",
            "iat": 1551290796,
            "exp": 1551290856,
            "iss": "https://lmsvendor.com",
            "aud": "PM48OJSfGDTAzAo",
            "sub": "3",
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id": "689302",
            "https://purl.imsglobal.org/spec/lti/claim/lti1p1": {
                "user_id": "34212",
                "oauth_consumer_key": "179248902",
                "oauth_consumer_key_sign": "lWd54kFo5qU7xshAna6v8BwoBm6tmUjc6GTax6+12ps="
            }
        }

     */

    /**
     * Compute the base string for a Launch JWT
     *
     * See: https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
     *
     * @param object $lj The Launch JSON Web Token with the LTI 1.1 transition data
     *
     * @return string This is null if the base string cannot be computed
     */
    public static function getLTI11TransitionBase($lj) {
        $nonce =  $lj->nonce;
        $expires = $lj->exp;
        $issuer = $lj->iss;
        $client_id = $lj->aud;
        $subject = $lj->sub;
        $deployment_id = $lj->{self::DEPLOYMENT_ID_CLAIM};
        if ( $nonce == null || $issuer == null || $expires == null ||
                $client_id == null || $subject == null || $deployment_id == null) return null;

        if ( ! isset($lj->{self::LTI11_TRANSITION_CLAIM}) ) return null;
        $lti11_transition = $lj->{self::LTI11_TRANSITION_CLAIM};
        $user_id = $lti11_transition->user_id;
        $oauth_consumer_key = $lti11_transition->oauth_consumer_key;
        if ( $user_id == null || $oauth_consumer_key == null ) return null;

        $base = $oauth_consumer_key . "&" . $deployment_id . "&" . $issuer . "&" .
            $client_id . "&" . $expires . "&" . $nonce;

        return $base;
    }

    /**
     * Compute the OAuth signature for an LTI 1.3 Launch JWT
     *
     * See: https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
     *
     * @param object $lj The Launch JSON Web Token with the LTI 11 transition data
     * @param string $secret The OAuth secret
     *
     * @return string This is null if the signature cannot be computed
     *
     */
    public static function signLTI11Transition($lj, $secret) {

        if ( $secret == null ) return null;

        $base = self::getLTI11TransitionBase($lj);
        if ( $base == null ) return null;

        $signature = self::compute_HMAC_SHA256($base, $secret);
        return $signature;
    }

    /**
     * Check the OAuth signature for an LTI 1.3 Launch JWT
     *
     * See: https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
     *
     * @param object $lj The Launch JSON Web Token with the LTI 11 transition data
     * @param string $key The OAuth key
     * @param string $secret The OAuth secret
     *
     * @return mixed true if the signature matches, false if the JWT
     * the signature does not match, and a string with an error if the JWT
     * data is malformed.
     */
    public static function checkLTI11Transition($lj, $key, $secret) {

        if ( $key == null ) return "Missing oauth_consumer_key";
        if ( $secret == null ) return "Missing OAuth secret";

        if ( ! isset($lj->{self::LTI11_TRANSITION_CLAIM}) ) return "LTI1.1 Transition claim not found";
        if ( ! isset($lj->{self::LTI11_TRANSITION_CLAIM}->oauth_consumer_key) ) return "LTI1.1 Transition claim missing key";
        if ( ! isset($lj->{self::LTI11_TRANSITION_CLAIM}->oauth_consumer_key_sign) ) return "LTI1.1 Transition signature not found";

        if ( $key != $lj->{self::LTI11_TRANSITION_CLAIM}->oauth_consumer_key ) return "LTI1.1 Transition key mis-match tsugi key=$key";
        $oauth_consumer_key_sign = $lj->{self::LTI11_TRANSITION_CLAIM}->oauth_consumer_key_sign;

        $base = self::getLTI11TransitionBase($lj);
        if ( $base == null ) return "LTI 1.1 transition - could not create base string";

        $signature = self::compute_HMAC_SHA256($base, $secret);
        return $oauth_consumer_key_sign == $signature;
    }

    /**
     * Compute the HMAC256 of a string (part of LTI 1.1 Transition)
     *
     * See: https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
     *
     * Based on:
     * https://www.jokecamp.com/blog/examples-of-creating-base64-hashes-using-hmac-sha256-in-different-languages/#php
     *
     * @param object $message The message to sign
     * @param string $secret The secret used to sign the message
     *
     * @return string The signed message
     */
    public static function compute_HMAC_SHA256($message, $secret)
    {
        $s = hash_hmac('sha256', $message, $secret, true);
        $hash = base64_encode($s);
        return $hash;
    }

    /**
     * Extract a public key from a string containing a JSON keyset
     */
    public static function extractKeyFromKeySet($keyset_str, $kid)
    {
        $key_set_arr = json_decode($keyset_str, true);
        if  ($key_set_arr == null ) return null;
        try {
            $key_set = JWK::parseKeySet($key_set_arr);
        } catch (\Exception $e ) {
            return null;
        }

        $key = U::get($key_set, $kid, false);
        if ( ! $key ) return null;

        $details = openssl_pkey_get_details($key);
        if ( $details && is_array($details) && isset($details['key']) ) {
            $new_public_key = $details['key'];
            return $new_public_key;
        }
        return null;
    }

}
