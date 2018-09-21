<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;
use \Firebase\JWT\JWT;

/**
 * This is a general purpose LTI class with no Tsugi-specific dependencies.
 */
class LTI13 extends LTI {

    const ROLES_CLAIM = "https://purl.imsglobal.org/spec/lti/claim/roles";
    const NAMESANDROLES_CLAIM = "https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice";
    const ENDPOINT_CLAIM = "https://purl.imsglobal.org/spec/lti-ags/claim/endpoint";
    const DEEPLINK_CLAIM = "https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings";

    public static function extract_consumer_key($jwt) {
        return 'lti13_' . $jwt->body->iss . '_' . $jwt->body->aud;
    }

    public static function raw_jwt($request_data=false) {
        if ( $request_data === false ) $request_data = $_REQUEST;
        $raw_jwt = U::get($request_data, 'id_token');
        if ( ! $raw_jwt ) return false;
        return $raw_jwt;
    }

    public static function parse_jwt($raw_jwt, $required_fields=true) {
        if ( $raw_jwt === false ) return false;
        if ( ! is_string($raw_jwt)) return "parse_jwt first parameter must be a string";
        $jwt_parts = explode('.', $raw_jwt);
        if ( count($jwt_parts) < 2 ) return "jwt must have at least two parts";
        $jwt_header = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]));
        if ( ! $jwt_header ) return "Could not decode jwt header";
        if ( ! isset($jwt_header->alg) ) return "Missing alg from jwt header";
        $jwt_body = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]));
        if ( ! $jwt_body ) return "Could not decode jwt body";
        if ( $required_fields && ! isset($jwt_body->iss) ) return "Missing iss from jwt body";
        if ( $required_fields && ! isset($jwt_body->aud) ) return "Missing aud from jwt body";
        $jwt = new \stdClass();
        $jwt->header = $jwt_header;
        $jwt->body = $jwt_body;
        if ( count($jwt_parts) > 2 ) {
            $jwt_extra = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);
            if ( $jwt_body ) $jwt->extra = $jwt_extra;
        }
        return $jwt;
    }

    // Returns true if this is a Basic LTI message
    // with minimum values to meet the protocol
    public static function isRequest($request_data=false) {
        $raw_jwt = self::raw_jwt($request_data);
        if ( ! $raw_jwt ) return false;
        $jwt = self::parse_jwt($raw_jwt);
        if ( is_string($jwt) ) die($jwt);
        return is_object($jwt);
    }

    /**
     * Verify the Public Key for this request
     *
     * @return mixed This returns true if the request verified.  If the request did not verify,
     * this returns the exception that was generated.
     */
    public static function verifyPublicKey($raw_jwt, $public_key, $algs) {
        try {
            // $decoded = JWT::decode($raw_jwt, $public_key, array('RS256'));
            $decoded = JWT::decode($raw_jwt, $public_key, $algs);
            // $decoded_array = json_decode(json_encode($decoded), true);
            return true;
        } catch(\Exception $e) {
            return $e;
        }
    }

    // Returns true if the lti_message_type is valid
    public static function isValidMessageType($lti_message_type=false) {
        return ($lti_message_type == "basic-lti-launch-request" ||
            $lti_message_type == "ToolProxyReregistrationRequest" ||
            $lti_message_type == "ContentItemSelectionRequest");
    }

    // Returns true if the lti_version is valid
    public static function isValidVersion($lti_version=false) {
        return ($lti_version == "LTI-1p0" || $lti_version == "LTI-2p0");
    }

    public static function getGradeToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {
        global $CFG;

        return self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
            "https://purl.imsglobal.org/spec/lti-ags/scope/score",
            "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);
    }

    public static function getRosterToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {
        global $CFG;

        return self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);
    }

    public static function getRosterWithSourceDidsToken($issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {
        global $CFG;

        return self::get_access_token([
            "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
            "https://purl.imsglobal.org/spec/lti-ags/scope/basicoutcome",
            "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
        ], $issuer, $subject, $lti13_token_url, $lti13_privkey, $debug_log);
    }

    // Call lineitem
    public static function sendLineItem($user_id, $grade, $comment, $lineitem_url,
        $access_token, &$debug_log=false) {
        global $CFG;

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

        // curl_setopt($ch, CURLOPT_URL, "http://lti-ri.imsglobal.org/platforms/7/line_items/9/scores");
        // echo("\n---\n$lineitem_url\n-----\n");
        curl_setopt($ch, CURLOPT_URL, $lineitem_url."/scores");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($grade_call));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '. $access_token,
            'Content-Type: application/vnd.ims.lis.v1.score+json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $line_item = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        // echo $line_item;
        if ( is_array($debug_log) ) $debug_log[] = "Sent line item, received $httpcode\n".$line_item;

        if ( $httpcode != 200 ) {
            $json = json_decode($line_item, true);
            $status = U::get($json, "error", "Unable to send lineitem");
            if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
            return $status;
        }

        return true;
    }

    // Call lineitem
    public static function loadRoster($membership_url, $access_token, &$debug_log=false) {
        global $CFG;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $membership_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '. $access_token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $membership = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);
        if ( is_array($debug_log) ) $debug_log[] = "Sent roster request, received $httpcode (".strlen($membership)." characters)";

        $json = json_decode($membership, false);   // Top level object
        if ( $json === null ) {
            $retval = "Unable to parse roster:". json_last_error_msg();
            if ( is_array($debug_log) ) {
                $debug_log[] = $retval;
                $debug_log[] = substr($membership, 0, 1000);
            }
            return $retval;
        }

        if ( $httpcode == 200 && isset($json->members) ) {
            if ( is_array($debug_log) ) $debug_log[] = "Loaded ".count($json->members)." roster entries";
            return $json->members;
        }

        $status = U::get($json, "error", "Unable to load roster members");
        if ( is_array($debug_log) ) $debug_log[] = "Error status: $status";
        return $status;
    }

    public static function get_access_token($scope, $issuer, $subject, $lti13_token_url, $lti13_privkey, &$debug_log=false) {
        global $CFG;

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

        $token_str = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( is_array($debug_log) ) $debug_log[] = "Returned token data $httpcode\n".$token_str;
        $token_data = json_decode($token_str, true);

        curl_close ($ch);

        return $token_data;
    }

    public static function base_jwt($issuer, $subject, &$debug_log=false) {
        global $CFG;

        $jwt_claim = [
            "iss" => $issuer,
            "sub" => $subject,
            "iat" => time(),
            "exp" => time()+60,
            "jti" => uniqid($issuer)
        ];
        return $jwt_claim;
    }

    public static function encode_jwt($jwt_claim, $lti13_privkey) {
        $jws = JWT::encode($jwt_claim, self::cleanup_PKCS8($lti13_privkey), 'RS256');
        return $jws;
    }

    public static function build_jwt_html($launch_url, $jws, $dodebug=true) {
        $html = "<form action=\"" . $launch_url . "\" method=\"POST\">\n"
                . "    <input type=\"hidden\" name=\"id_token\" value=\"" . htmlspecialchars($jws) . "\" />\n"
                . "    <input type=\"submit\" value=\"Go!\" />\n"
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
