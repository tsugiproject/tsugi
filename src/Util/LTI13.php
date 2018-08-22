<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;
use \Firebase\JWT\JWT;

/**
 * This is a general purpose LTI class with no Tsugi-specific dependencies.
 *
 * This class handles the protocol and OAuth validation and does not
 * deal with how to use LTI data during the runtime of the tool.
 *
 */
class LTI13 extends LTI {

    public static function extract_consumer_key($jwt) {
        return 'lti13_' . $jwt->body->iss . '_' . $jwt->body->aud;
    }

    public static function raw_jwt($request_data=false) {
        if ( $request_data === false ) $request_data = $_REQUEST;
        $raw_jwt = U::get($request_data, 'id_token');
        if ( ! $raw_jwt ) return false;
        return $raw_jwt;
    }

    public static function parse_jwt($raw_jwt) {
        if ( $raw_jwt === false ) return false;
        if ( ! is_string($raw_jwt)) return "parse_jwt first parameter must be a string";
        $jwt_parts = explode('.', $raw_jwt);
        if ( count($jwt_parts) < 2 ) return "jwt must have at least two parts";
        $jwt_header = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]));
        if ( ! $jwt_header ) return "Could not decode jwt header";
        if ( ! isset($jwt_header->alg) ) return "Missing alg from jwt header";
        $jwt_body = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]));
        if ( ! $jwt_body ) return "Could not decode jwt body";
        if ( ! isset($jwt_body->iss) ) return "Missing iss from jwt body";
        if ( ! isset($jwt_body->aud) ) return "Missing aud from jwt body";
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

        $jwt_claim = [
            "iss" => $issuer,
            "sub" => $subject,
            "aud" => $lti13_token_url,
            "iat" => time(),
            "exp" => time()+60,
            "jti" => uniqid($issuer)
        ];

        $jwt = JWT::encode($jwt_claim, $lti13_privkey, 'RS256');

        $auth_request = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $jwt,
            'scope' => "http://imsglobal.org/ags/lineitem http://imsglobal.org/ags/result/read"
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$lti13_token_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $token_data = json_decode(curl_exec($ch), true);

        curl_close ($ch);


// echo $token_data['access_token'];
        return $token_data;
        // return $token_data['access_token'];
    }

    // Call grade book
    public static function sendGrade($user_id, $grade, $comment, $lineitem_url,
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

        curl_close ($ch);

        // echo $line_item;
        error_log("Sent line item, received\n".$line_item);

        return true;
    }

}
