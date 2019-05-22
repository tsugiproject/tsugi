<?php


namespace Tsugi\Util;

use \Tsugi\Util\U;
use \Firebase\JWT\JWT;

/**
 * This is a general purpose LTI 1.3 class with no Tsugi-specific dependencies.
 */
class LTI13 {

    const VERSION_CLAIM =       'https://purl.imsglobal.org/spec/lti/claim/version';
    const MESSAGE_TYPE_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/message_type';
    const MESSAGE_TYPE_RESOURCE = 'LtiResourceLinkRequest';
    const MESSAGE_TYPE_DEEPLINK = 'LtiDeepLinkingRequest';
    const RESOURCE_LINK_CLAIM = 'https://purl.imsglobal.org/spec/lti/claim/resource_link';
    const DEPLOYMENT_ID =       'https://purl.imsglobal.org/spec/lti/claim/deployment_id';
    const ROLES_CLAIM =         'https://purl.imsglobal.org/spec/lti/claim/roles';
    const PRESENTATION_CLAIM =  'https://purl.imsglobal.org/spec/lti/claim/launch_presentation';

    const NAMESANDROLES_CLAIM = 'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
    const ENDPOINT_CLAIM =      'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';
    const DEEPLINK_CLAIM =      'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings';

    const MEDIA_TYPE_MEMBERSHIPS = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';
    const MEDIA_TYPE_LINEITEM = 'application/vnd.ims.lis.v2.lineitem+json';
    const MEDIA_TYPE_LINEITEMS = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
    const SCORE_TYPE = 'application/vnd.ims.lis.v1.score+json';
    const RESULTS_TYPE = 'application/vnd.ims.lis.v2.resultcontainer+json';

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
            $lti_message_type == "ToolProxyReregistrationRequest" ||
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
        } else {
            $failures[] = "Bad message type: ".$message_type;
        }

        if ( ! isset($body->{self::ROLES_CLAIM}) ) $failures[] = "Missing required role claim";
        if ( ! isset($body->{self::DEPLOYMENT_ID}) ) $failures[] = "Missing required deployment_id claim";
    }

    /** Retrieve a grade token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getGradeToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {

        $token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
            "https://purl.imsglobal.org/spec/lti-ags/scope/score",
            "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);

        return self::extract_access_token($token_data, $debug_log);
    }

    /** Retrieve a Names and Roles Provisioning Service (NRPS) token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getNRPSToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {

         $roster_token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);

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
    public static function getNRPSWithSourceDidsToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {

        $roster_token_data =  self::get_access_token([
            // TODO: Uncomment this after (I think) the certification suite tolerates extra stuff
            // "https://purl.imsglobal.org/spec/lti-ags/scope/basicoutcome",
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);

        return self::extract_access_token($roster_token_data, $debug_log);
    }

    /** Retrieve a LineItems token
     *
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function getLineItemsToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {

        $token_data = self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);

        return self::extract_access_token($token_data, $debug_log);
    }

    /** Send a line item result
     *
     * @param $user_id The user for this grade
     * @param $grade Value to send
     * @param $comment An optional comment
     * @param $lineitem_url The REST endpoint (id) for this line item
     * @param $access_token The access token for this request
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * @return mixed Returns the token (string) or false on error.
     */
    public static function sendLineItemResult($user_id, $grade, $comment, $lineitem_url,
        $access_token, &$debug_log=false) {

        $lineitem_url = trim($lineitem_url);

        $ch = curl_init();

        $grade = $grade * 100.0;
        $grade = (int) $grade;

        // user_id comes from the "sub" in the JWT launch
        $grade_call = [
            // "timestamp" => "2017-04-16T18:54:36.736+00:00",
            "timestamp" => U::iso8601(),
            "scoreGiven" => $grade,
            "scoreMaximum" => 100,
            "comment" => $comment,
            "activityProgress" => "Completed",
            "gradingProgress" => "Completed",
            "userId" => $user_id,
        ];

        $headers = [
            'Authorization: Bearer '. $access_token,
            'Content-Type: '.self::SCORE_TYPE,
            'Accept: '.self::SCORE_TYPE
        ];

        // echo("\n---\n$lineitem_url\n-----\n");
        $actual_url = $lineitem_url."/scores";
        curl_setopt($ch, CURLOPT_URL, $actual_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($grade_call));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ( is_array($debug_log) ) $debug_log[] = "Scores Url: ".$actual_url;
        if ( is_array($debug_log) ) $debug_log[] = $headers;

        $line_item = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        // echo $line_item;
        if ( is_array($debug_log) ) $debug_log[] = "Sent line item, received status=$httpcode\n".$line_item;

        if ( ! Net::httpSuccess($httpcode) ) {
            $json = json_decode($line_item, true);
            $status = U::get($json, "error", "Unable to send lineitem");
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

        if ( ! Net::httpSuccess($httpcode) && isset($json->members) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded ".count($json->members)." roster entries";
            return $json;
        }

        $status = isset($json->error) ? $json->error : "Unable to load results";
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
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

        if ( Net::httpSuccess($httpcode) && is_array($json) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded results";
            return $json;
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
     * @param string $issuer Who we are
     * @param string $subject Who we are (within $issuer)
     * @param string $lti13_token_url
     * @param string $lti13_privkey
     * @param array $debug_log An optional array passed by reference.   Actions taken will be
     * logged into this array.
     *
     * Note that for LTI Advantage, we send the client_id as both the $issuer and
     * $subject since LMS's don't have our url (i.w. wwwroot) available to them
     * as part of the LTI 1.3 configuration.
     *
     * @return array The retrieved and parsed JSON data.  There is no validation performed,
     * and we might have got a 403 and received no data at all.
     */
    public static function get_access_token($scope, $issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {

        $lti13_token_url = trim($lti13_token_url);
        $issuer = trim($issuer);
        $subject = trim($subject);

        if ( ! is_string($scope) ) {
            $scope = implode(' ',$scope);
        }

        $jwt_claim = self::base_jwt($issuer, $subject, $debug_log);
        $jwt_claim["aud"] = $lti13_token_url;

        $jwt = self::encode_jwt($jwt_claim, $lti13_privkey);

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
        if ( is_array($debug_log) ) $debug_log[] = "Returned token data $httpcode\n".$token_str;
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
            "jti" => uniqid($issuer)
        ];
        return $jwt_claim;
    }

    /** Sign and encode a JWT
     *
     * @param array $jwt_claim An array of key/value pairs for the claims
     * @param string $lti13_privkey The private key to use to sign the JWT
     *
     * @return string The signed JWT
     */
    public static function encode_jwt($jwt_claim, $lti13_privkey) {
        $jws = JWT::encode($jwt_claim, self::cleanup_PKCS8($lti13_privkey), 'RS256');
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
        $html = "<form action=\"" . $launch_url . "\" method=\"POST\"";
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
        }
        return $html;
    }

    /**
     * Generate a PKCS8 Ppublic / private key pair
     *
     * @param string $publicKey Returned public key
     * @param string $privateKey Returned private key
     */
    // https://stackoverflow.com/questions/6648337/generate-ssh-keypair-form-php
    public static function generatePKCS8Pair(&$publicKey, &$privateKey) {
        $privKey = openssl_pkey_new(
            array('digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA));

        // Private Key
        $privKey = openssl_pkey_get_private($privKey);
        openssl_pkey_export($privKey, $privateKey);

        // Public Key
        $pubKey = openssl_pkey_get_details($privKey);
        $publicKey = $pubKey['key'];
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

}
