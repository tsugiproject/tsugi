<?php

namespace Tsugi\Util;

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
        // return isset($jwt->header->kid) ? $jwt->header->kid : false;
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
        $jwt_header = json_decode(base64_decode($jwt_parts[0]));
        if ( ! $jwt_header ) return "Could not decode jwt header";
        if ( ! isset($jwt_header->alg) ) return "Missing alg from jwt header";
        if ( ! isset($jwt_header->kid) ) return "Missing kid from jwt header";
        $jwt_body = json_decode(base64_decode($jwt_parts[1]));
        if ( ! $jwt_body ) return "Could not decode jwt body";
        if ( ! isset($jwt_body->iss) ) return "Missing iss from jwt body";
        if ( ! isset($jwt_body->aud) ) return "Missing aud from jwt body";
        $jwt = new \stdClass();
        $jwt->header = $jwt_header;
        $jwt->body = $jwt_body;
        if ( count($jwt_parts) > 2 ) {
            $jwt_extra = json_decode(base64_decode($jwt_parts[1]), true);
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

}
