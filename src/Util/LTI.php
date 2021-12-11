<?php

namespace Tsugi\Util;

use \Tsugi\OAuth\TrivialOAuthDataStore;
use \Tsugi\OAuth\OAuthServer;
use \Tsugi\OAuth\OAuthSignatureMethod_HMAC_SHA1;
use \Tsugi\OAuth\OAuthSignatureMethod_HMAC_SHA256;
use \Tsugi\OAuth\OAuthRequest;
use \Tsugi\OAuth\OAuthConsumer;
use \Tsugi\OAuth\OAuthUtil;

use \Tsugi\Util\Net;
use \Tsugi\Util\Mimeparse;

/**
 * This is a general purpose LTI class with no Tsugi-specific dependencies.
 *
 * This class handles the protocol and OAuth validation and does not
 * deal with how to use LTI data during the runtime of the tool.
 *
 */
class LTI {

    /**
     * Determines if this is a valid Basic LTI message
     *
     * @retval mixed Returns true if this is a Basic LTI message
     * with minimum values to meet the protocol.  Returns false
     * if this is not even close to an LTI launch.  If this is a
     * broken launch, it returns an error as to why.
     */
    public static function isRequestCheck($request_data=false) {
        if ( $request_data === false ) $request_data = $_REQUEST;
        if ( !isset($request_data["lti_message_type"]) ) return false;
        if ( !isset($request_data["lti_version"]) ) return false;
        $good_lti_version = self::isValidVersion($request_data["lti_version"]);
        if ( ! $good_lti_version ) return "Invalid LTI version ".$request_data["lti_version"];
        $good_message_type = self::isValidMessageType($request_data["lti_message_type"]);
        if ( ! $good_message_type ) return "Invalid message type ".$request_data["lti_message_type"];
        return true;
    }

    /**
     * Determines if this is a valid Basic LTI message
     *
     * Returns true if this is a Basic LTI message
     * with minimum values to meet the protocol
     */
    public static function isRequest($request_data=false) {
        if ( $request_data === false ) $request_data = $_REQUEST;
        if ( !isset($request_data["lti_message_type"]) ) return false;
        if ( !isset($request_data["lti_version"]) ) return false;
        $good_message_type = self::isValidMessageType($request_data["lti_message_type"]);
        $good_lti_version = self::isValidVersion($request_data["lti_version"]);
        if ($good_message_type and $good_lti_version ) return(true);
        return false;
    }

    // Returns true if the lti_message_type is valid
    public static function isValidMessageType($lti_message_type) {
        return ($lti_message_type == "basic-lti-launch-request" ||
            $lti_message_type == "ContentItemSelection" ||
            $lti_message_type == "ContentItemSelectionRequest");
    }

    // Returns true if the lti_version is valid
    public static function isValidVersion($lti_version) {
        return ($lti_version == "LTI-1p0" || $lti_version == "LTI-2p0");
    }

    /**
     * Verify the message signature for this request
     *
     * @return mixed This returns true if the request verified.  If the request did not verify,
     * this returns an array with the first element as an error string, and the second element
     * as the base string of the request.
     */
    public static function verifyKeyAndSecret($key, $secret, $http_url=NULL, $parameters=null, $http_method=NULL) {
        global $LastOAuthBodyBaseString;
        if ( ! ($key && $secret) ) return array("Missing key or secret", "");
        $store = new TrivialOAuthDataStore();
        $store->add_consumer($key, $secret);

        $server = new OAuthServer($store);

        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);
        $method = new OAuthSignatureMethod_HMAC_SHA256();
        $server->add_signature_method($method);

        $request = OAuthRequest::from_request($http_method, $http_url, $parameters);

        $LastOAuthBodyBaseString = $request->get_signature_base_string();

        try {
            $server->verify_request($request);
            return true;
        } catch (\Exception $e) {
            return array($e->getMessage(), $LastOAuthBodyBaseString);
        }
    }

    public static function signParameters($oldparms, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret,
        $submit_text = false, $org_id = false, $org_desc = false)
    {
        global $LastOAuthBodyBaseString;
        $parms = $oldparms;
        if ( ! isset($parms["lti_version"]) ) $parms["lti_version"] = "LTI-1p0";
        if ( ! isset($parms["lti_message_type"]) ) $parms["lti_message_type"] = "basic-lti-launch-request";
        if ( ! isset($parms["oauth_callback"]) ) $parms["oauth_callback"] = "about:blank";
        if ( $org_id ) $parms["tool_consumer_instance_guid"] = $org_id;
        if ( $org_desc ) $parms["tool_consumer_instance_description"] = $org_desc;
        if ( $submit_text ) $parms["ext_submit"] = $submit_text;
        if ( ! isset($parms["ext_lti_element_id"]) ) {
            $parms["ext_lti_element_id"] = "tsugi_element_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        }

        $test_token = '';

        $oauth_signature_method = isset($parms['oauth_signature_method']) ? $parms['oauth_signature_method'] : false;

        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
        if ( $oauth_signature_method == "HMAC-SHA256" ) {
            $hmac_method = new OAuthSignatureMethod_HMAC_SHA256();
        }
        $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
        $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

        // Pass this back up "out of band" for debugging
        $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();

        $newparms = $acc_req->get_parameters();

      // Don't want to pull GET parameters into POST data so
      // manually pull back the oauth_ parameters
      foreach($newparms as $k => $v ) {
            if ( strpos($k, "oauth_") === 0 ) {
                $parms[$k] = $v;
            }
        }

        return $parms;
    }

    public static function postLaunchHTML($newparms, $endpoint, $debug=false, $iframeattr=false, $endform=false) {
        global $LastOAuthBodyBaseString;

        if ( isset($newparms["ext_lti_element_id"]) ) {
            $frame_id = $newparms["ext_lti_element_id"];
        } else {
            $frame_id = "tsugi_random_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        }
        if ( isset($newparms["ext_lti_form_id"]) ) {
            $form_id = $newparms["ext_lti_form_id"];
        } else {
            $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        }
        $debug_id = rand(1000,9999);
        if ( $iframeattr =="_blank" ) {
            $r = "<form action=\"".$endpoint."\" name=\"".$form_id."\" id=\"".$form_id."\" method=\"post\" target=\"_blank\" encType=\"application/x-www-form-urlencoded\">\n" ;
        } else if ( $iframeattr && $iframeattr != '_pause') {
            $r = "<form action=\"".$endpoint."\" name=\"".$form_id."\" id=\"".$form_id."\" method=\"post\" target=\"".$frame_id."\" encType=\"application/x-www-form-urlencoded\">\n" ;
        } else {
            $r = "<form action=\"".$endpoint."\" name=\"".$form_id."\" id=\"".$form_id."\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n" ;
        }
        ksort($newparms);
        $submit_text = $newparms['ext_submit'];
        foreach($newparms as $key => $value ) {
            $key = htmlspec_utf8($key);
            $value = htmlspec_utf8($value);
            if ( $key == "ext_submit" && $iframeattr != '_pause') {
                $r .= "<input type=\"submit\" name=\"";
            } else {
                $r .= "<input type=\"hidden\" name=\"";
            }
            $r .= $key;
            if ( $key == "ext_submit" && $iframeattr != '_pause') {
                $r .= "\" class=\"btn btn-primary";
            }
            $r .= "\" value=\"";
            $r .= $value;
            $r .= "\"/>\n";
        }
        if ( $debug ) {
            $r .= "<script language=\"javascript\"> \n";
            $r .= "  //<![CDATA[ \n" ;
            $r .= "function basicltiDebug_".$debug_id."_Toggle() {\n";
            $r .= "    var ele = document.getElementById(\"basicltiDebug_".$debug_id."_\");\n";
            $r .= "    if(ele.style.display == \"block\") {\n";
            $r .= "        ele.style.display = \"none\";\n";
            $r .= "    }\n";
            $r .= "    else {\n";
            $r .= "        ele.style.display = \"block\";\n";
            $r .= "    }\n";
            $r .= "} \n";
            $r .= "  //]]> \n" ;
            $r .= "</script>\n";
            $r .= "<a class=\"basicltiDebugToggle\" id=\"basicltiDebug_";
            $r .= $debug_id."_Toggle\" href=\"javascript:basicltiDebug_".$debug_id."_Toggle();\">";
            $r .= self::get_string("toggle_debug_data","basiclti")."</a>\n";
            $r .= "<div id=\"basicltiDebug_".$debug_id."_\" style=\"display:none\">\n";
            $r .=  "<b>".self::get_string("basiclti_endpoint","basiclti")."</b><br/>\n";
            $r .= $endpoint . "<br/>\n&nbsp;<br/>\n";
            $r .=  "<b>".self::get_string("basiclti_parameters","basiclti")."</b><br/>\n";
            foreach($newparms as $key => $value ) {
                $key = htmlspec_utf8($key);
                $value = htmlspec_utf8($value);
                $r .= "$key = $value<br/>\n";
            }
            $r .= "&nbsp;<br/>\n";
            $r .= "<p><b>".self::get_string("basiclti_base_string","basiclti")."</b><br/>\n".$LastOAuthBodyBaseString."</p>\n";
            $r .= "</div>\n";
        }
        if ( $endform ) $r .= $endform;
        $r .= "</form>\n";
        if ( $iframeattr && $iframeattr != '_blank' && $iframeattr != '_pause') {
            $r .= "<iframe class=\"lti_frameResize\" name=\"".$frame_id."\"  id=\"".$frame_id."\" src=\"\"\n";
            $r .= $iframeattr . ">\n<p>".self::get_string("frames_required","basiclti")."</p>\n</iframe>\n";
        }
        // Remove session_name (i.e. PHPSESSID) if it was added.
        $r .= " <script type=\"text/javascript\"> \n" .
            "  //<![CDATA[ \n" .
            "    var inputs = document.getElementById(\"".$form_id."\").childNodes;\n" .
            "    for (var i = 0; i < inputs.length; i++)\n" .
            "    {\n" .
            "        var thisinput = inputs[i];\n" .
            "        if ( thisinput.name != '".session_name()."' ) continue;\n" .
            "        thisinput.parentNode.removeChild(thisinput);\n" .
            "    }\n" .
            "  //]]> \n" .
            " </script> \n";

        if ( ( ! $debug ) && $iframeattr != '_pause' ) {
            $ext_submit = "ext_submit";
            $ext_submit_text = $submit_text;
            $r .= " <script type=\"text/javascript\"> \n" .
                "  //<![CDATA[ \n" .
                "    document.getElementById(\"".$form_id."\").style.display = \"none\";\n" .
                "    nei = document.createElement('input');\n" .
                "    nei.setAttribute('type', 'hidden');\n" .
                "    nei.setAttribute('name', '".$ext_submit."');\n" .
                "    nei.setAttribute('value', '".$ext_submit_text."');\n" .
                "    document.getElementById(\"".$form_id."\").appendChild(nei);\n" .
                "    document.getElementById(\"".$form_id."\").submit(); \n" .
                "    console.log('Autosubmitted ".$form_id."'); \n" .
                "  //]]> \n" .
                " </script> \n";
        }
        return $r;
    }

    /* This is a bit of homage to Moodle's pattern of internationalisation */
    public static function get_string($key,$bundle) {
        return $key;
    }

    public static function bodyRequest($url, $method, $data, $optional_headers = null)
    {
      if ($optional_headers !== null) {
         $header = $optional_headers . "\r\n";
      }
      $header = $header . "Content-Type: application/x-www-form-urlencoded\r\n";

      return Net::doBody($url,$method,$data,$header);
    }

    /**
     * Lower case and turn non letters / numbers into underscores per LTI 1.1
     *
     * http://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide
     *
     * When there are custom name / value parameters in the launch, a POST parameter is
     * included for each custom parameter.  The parameter names are mapped to lower
     * case and any character that is neither a number nor letter in a parameter
     * name is replaced with an "underscore".  So if a custom entry was as follows:
     *
     *    Review:Chapter=1.2.56
     *
     * would be mapped to:
     *
     *  custom_review_chapter=1.2.56
     */
    public static function mapCustomName($name) {
        $name = strtolower($name);
        $retval = "";
        for($i=0; $i < strlen($name); $i++) {
            $ch = substr($name,$i,1);
            if ( $ch >= "a" && $ch <= "z" ) $retval .= $ch;
            else if ( $ch >= "0" && $ch <= "9" ) $retval .= $ch;
            else $retval .= "_";
        }
        return $retval;
    }

    public static function addCustom(&$parms, $custom) {
        if ( ! is_array($custom)) {
            $lines = explode("\n", $custom);
            $custom = array();
            foreach($lines as $line) {
                $pos = strpos($line,'=');
                if ( $pos < 1 ) continue;
                $key = substr($line,0,$pos);
                $val = substr($line,$pos+1);
                if ( strlen($val) < 1 ) continue;
                $custom[$key] = trim($val);
            }
        }
        foreach ( $custom as $key => $val) {
            $nk = self::mapCustomName($key);
            $parms["custom_".$nk] = $val;
        }
    }

    public static function getLastOAuthBodyBaseString() {
        global $LastOAuthBodyBaseString;
        return $LastOAuthBodyBaseString;
    }

    public static function getLastOAuthBodyHashInfo() {
        global $LastOAuthBodyHashInfo;
        return $LastOAuthBodyHashInfo;
    }

    public static function getAuthorizationHeader()
    {
        $request_headers = OAuthUtil::get_headers();
        $auth = isset($request_headers['Authorization']) ? $request_headers['Authorization'] : null;
        if ( ! $auth ) $auth = isset($request_headers['authorization']) ? $request_headers['authorization'] : null;
        return $auth;
    }

    public static function getContentTypeHeader()
    {
        $request_headers = OAuthUtil::get_headers();
        $ctype = isset($request_headers['Content-Type']) ? $request_headers['Content-Type'] : null;
        if ( ! $ctype ) $ctype = isset($request_headers['Content-type']) ? $request_headers['Content-type'] : null;
        if ( ! $ctype ) $ctype = isset($request_headers['content-type']) ? $request_headers['content-type'] : null;
        return $ctype;
    }

    public static function getOAuthKeyFromHeaders()
    {
        $auth = self::getAuthorizationHeader();

        if ($auth && @substr($auth, 0, 6) == "OAuth ") {
            $auth_parameters = OAuthUtil::split_header($auth);

            // echo("HEADER PARMS=\n");
            // print_r($auth_parameters);
            return $auth_parameters['oauth_consumer_key'];
        }
        return false;
    }

    public static function handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret, $postdata=false)
    {
        // Must reject application/x-www-form-urlencoded
        $ctype = self::getContentTypeHeader();
        if ($ctype == 'application/x-www-form-urlencoded' ) {
            throw new \Exception("OAuth request body signing must not use application/x-www-form-urlencoded");
        }

        $oauth_signature_method = false;
        $auth = self::getAuthorizationHeader();
        if (@substr($auth, 0, 6) == "OAuth ") {
            $auth_parameters = OAuthUtil::split_header($auth);

            // echo("HEADER PARMS=\n");
            // print_r($auth_parameters);
            $oauth_body_hash = $auth_parameters['oauth_body_hash'];
            if ( isset($auth_parameters['oauth_signature_method']) ) $oauth_signature_method = $auth_parameters['oauth_signature_method'];
            // error_log("OBH=".$oauth_body_hash."\n");
        }

        if ( ! isset($oauth_body_hash)  ) {
            throw new \Exception("OAuth request body signing requires oauth_body_hash body");
        }

        if ( ! $postdata ) $postdata = file_get_contents('php://input');
        // error_log($postdata);

        if ( $oauth_signature_method == 'HMAC-SHA256' ) {
            $hash = base64_encode(hash('sha256', $postdata, TRUE));
        } else {
            $hash = base64_encode(sha1($postdata, TRUE));
        }

        global $LastOAuthBodyHashInfo;
        $LastOAuthBodyHashInfo = "hdr_hash=$oauth_body_hash body_len=".strlen($postdata)." body_hash=$hash oauth_signature_method=$oauth_signature_method";

        // Check the key and secret.
        $retval = self::verifyKeyAndSecret($oauth_consumer_key, $oauth_consumer_secret);
        if ( $retval !== true ) {
            throw new \Exception("OAuth signature failed: " . $retval[0]);
        }

        if ( $hash != $oauth_body_hash ) {
            throw new \Exception("OAuth oauth_body_hash mismatch");
        }

        return $postdata;
    }

    public static function sendOAuthGET($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $accept_type, $signature=false)
    {
        $test_token = '';
        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
        if ( $signature == "HMAC-SHA256" ) {
            $hmac_method = new OAuthSignatureMethod_HMAC_SHA256();
        }
        $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);
        $parms = array();

        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, "GET", $endpoint, $parms);
        $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

        // Pass this back up "out of band" for debugging
        global $LastOAuthBodyBaseString;
        $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();

        $header = $acc_req->to_header();
        $header = $header . "\r\nAccept: " . $accept_type . "\r\n";

        global $LastGETHeader;
        $LastGETHeader = $header;

        return Net::doGet($endpoint,$header);
    }

    public static function sendOAuthBody($method, $endpoint, $oauth_consumer_key, $oauth_consumer_secret,
	$content_type, $body, $more_headers=false, $signature=false)
    {

        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
        $hash = base64_encode(sha1($body, TRUE));
        if ( $signature == "HMAC-SHA256" ) {
            $hmac_method = new OAuthSignatureMethod_HMAC_SHA256();
            $hash = base64_encode(hash('sha256', $body, TRUE));
        }

        $parms = array('oauth_body_hash' => $hash);
        if ( $signature == "HMAC-SHA256" ) {
            $parms['oauth_signature_method'] = $signature;
        }

        $test_token = '';

        $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
        $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

        // Pass this back up "out of band" for debugging
        global $LastOAuthBodyBaseString;
        $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();

        $header = $acc_req->to_header();
        $header = $header . "\r\nContent-Type: " . $content_type . "\r\n";
        if ( $more_headers === false ) $more_headers = array();
        foreach ($more_headers as $more ) {
            $header = $header . $more . "\r\n";
        }

        return Net::doBody($endpoint, $method, $body,$header);
    }

    /**
     * Retrieve a grade using the LTI 1.1 protocol (POX)
     *
     * This retrieves a grade using the Plain-Old-XML protocol from
     * IMS LTI 1.1
     *
     * @param debug_log This can either be false or an empty array.  If
     * this is an array, it is filled with data as the steps progress.
     * Each step is an array with a string message as the first element
     * and optional debug detail (i.e. like a post body) as the second
     * element.
     *
     * @return mixed If things go well this returns a float of the existing grade.
     * If this goes badly, this returns a string with an error message.
     */
    public static function getPOXGrade($sourcedid, $service, $key_key, $secret, &$debug_log=false, $signature=false) {
        global $LastPOXGradeResponse;
        global $LastPOXGradeParse;
        global $LastPOXGradeError;
        global $LastCurlError;
        $LastPOXGradeResponse = false;
        $LastPOXGradeParse = false;
        $LastPOXGradeError = false;
        $LastCurlError = false;

        if ( strlen($sourcedid) < 1 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('Missing sourcedid');
            return "Missing sourcedid";
        }
        if ( strlen($service) < 1 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('Missing service');
            return "Missing service";
        }

        $content_type = "application/xml";
        $sourcedid = htmlspecialchars($sourcedid);

        $operation = 'readResultRequest';
        $postBody = str_replace(
            array('SOURCEDID', 'OPERATION','MESSAGE'),
            array($sourcedid, $operation, uniqid()),
            self::getPOXRequest());

        if ( is_array($debug_log) ) $debug_log[] = array('Loading grade from '.$service.' sourcedid='.$sourcedid);
        if ( is_array($debug_log) )  $debug_log[] = array('Grade API Request',$postBody);

        $more_headers = false;
        $response = self::sendOAuthBody("POST", $service, $key_key, $secret,
            $content_type, $postBody, $more_headers, $signature);
        if ( is_string($LastCurlError) && is_array($debug_log) )  $debug_log[] = array("Curl Error",$LastCurlError);
        $LastPOXGradeResponse = $response;
        if ( is_array($debug_log) )  $debug_log[] = array("Grade API Response",$response);

        $status = "Failure to retrieve grade";
        if ( strpos($response, '<?xml') !== 0 && strpos($response, '<imsx_POXEnvelopeResponse' !== 0)) {
            error_log("Fatal XML Grade Read: ".session_id()." sourcedid=".$sourcedid);
            error_log("Detail: service=".$service." key_key=".$key_key);
            error_log("Response: ".$response);
            return "Unable to read XML from web service.";
        }

        $grade = false;
        try {
            $retval = self::parseResponse($response);
            $LastPOXGradeParse = $retval;
            if ( is_array($retval) ) {
                if ( isset($retval['imsx_codeMajor']) && $retval['imsx_codeMajor'] == 'success') {
			if ( isset($retval['textString'])) {
				$grade = $retval['textString'];
				if ( is_numeric($grade) ) $grade = $grade + 0.0;
			}
                } else if ( isset($retval['imsx_description']) ) {
                    $LastPOXGradeError = $retval['imsx_description'];
                    error_log("Grade read failure: ".$LastPOXGradeError);
                    if ( is_array($debug_log) )  $debug_log[] = array("Grade read failure: ".$LastPOXGradeError);
                    return $LastPOXGradeError;
                }
            }
        } catch(Exception $e) {
            $LastPOXGradeError = $e->getMessage();
            error_log("Grade read failure: ".$LastPOXGradeError);
            if ( is_array($debug_log) )  $debug_log[] = array("Exception: ".$status);
            return $LastPOXGradeError;
        }
        return $grade;
    }

    /**
     * Send a grade using the LTI 1.1 protocol (POX)
     *
     * This sends a grade using the Plain-Old-XML protocol from
     * IMS LTI 1.1
     *
     * @param debug_log This can either be false or an empty array.  If
     * this is an array, it is filled with data as the steps progress.
     * Each step is an array with a string message as the first element
     * and optional debug detail (i.e. like a post body) as the second
     * element.
     *
     * @return mixed If things go well this returns true.
     * If this goes badly, this returns a string with an error message.
     */
    public static function sendPOXGrade($grade, $sourcedid, $service, $key_key, $secret, &$debug_log=false, $signature=false) {
        global $LastPOXGradeResponse;
        $LastPOXGradeResponse = false;

        if ( strlen($sourcedid) < 1 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('Missing service');
            return "Missing sourcedid";
        }
        if ( strlen($service) < 1 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('Missing service');
            return "Missing service";
        }

        $content_type = "application/xml";
        $sourcedid = htmlspecialchars($sourcedid);

        $operation = 'replaceResultRequest';
        $postBody = str_replace(
            array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
            array($sourcedid, $grade.'', 'replaceResultRequest', uniqid()),
            self::getPOXGradeRequest());

        if ( is_array($debug_log) ) $debug_log[] = array('Sending '.$grade.' to '.$service.' sourcedid='.$sourcedid);

        if ( is_array($debug_log) )  $debug_log[] = array('Grade API Request',$postBody);

        $more_headers = false;
        $response = self::sendOAuthBody("POST", $service, $key_key, $secret,
            $content_type, $postBody, $more_headers, $signature);
        global $LastOAuthBodyBaseString;
        global $LastCurlError;
        $lbs = $LastOAuthBodyBaseString;
        if ( is_array($debug_log) )  $debug_log[] = array("Grade API Response",$response);
        if ( is_string($LastCurlError) && is_array($debug_log) )  $debug_log[] = array("Curl Error",$LastCurlError);
        $LastPOXGradeResponse = $response;
        $status = "Failure to store grade";
        if ( strpos($response, '<?xml') !== 0 ) {
            error_log("Fatal XML Grade Update: ".session_id()." sourcedid=".$sourcedid);
            error_log("Detail: service=".$service." key_key=".$key_key);
            error_log("Response: ".$response);
            return $status;
        }
        try {
            $retval = self::parseResponse($response);
            if ( isset($retval['imsx_codeMajor']) && $retval['imsx_codeMajor'] == 'success') {
                $status = true;
            } else if ( isset($retval['imsx_description']) ) {
                $status = $retval['imsx_description'];
            }
        } catch(Exception $e) {
            $status = $e->getMessage();
            if ( is_array($debug_log) )  $debug_log[] = array("Exception: ".$status);
        }
        return $status;
    }


    public static function getPOXGradeRequest() {
        return '<?xml version = "1.0" encoding = "UTF-8"?>
    <imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
      <imsx_POXHeader>
        <imsx_POXRequestHeaderInfo>
          <imsx_version>V1.0</imsx_version>
          <imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
        </imsx_POXRequestHeaderInfo>
      </imsx_POXHeader>
      <imsx_POXBody>
        <OPERATION>
          <resultRecord>
            <sourcedGUID>
              <sourcedId>SOURCEDID</sourcedId>
            </sourcedGUID>
            <result>
              <resultScore>
                <language>en-us</language>
                <textString>GRADE</textString>
              </resultScore>
            </result>
          </resultRecord>
        </OPERATION>
      </imsx_POXBody>
    </imsx_POXEnvelopeRequest>';
    }

    /*  $postBody = str_replace(
          array('SOURCEDID', 'OPERATION','MESSAGE'),
          array($sourcedid, $operation, uniqid()),
          self::getPOXRequest());
    */
    public static function getPOXRequest() {
        return '<?xml version = "1.0" encoding = "UTF-8"?>
    <imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
      <imsx_POXHeader>
        <imsx_POXRequestHeaderInfo>
          <imsx_version>V1.0</imsx_version>
          <imsx_messageIdentifier>MESSAGE</imsx_messageIdentifier>
        </imsx_POXRequestHeaderInfo>
      </imsx_POXHeader>
      <imsx_POXBody>
        <OPERATION>
          <resultRecord>
            <sourcedGUID>
              <sourcedId>SOURCEDID</sourcedId>
            </sourcedGUID>
          </resultRecord>
        </OPERATION>
      </imsx_POXBody>
    </imsx_POXEnvelopeRequest>';
    }

    /*     sprintf(getPOXResponse(),uniqid(),'success', "Score read successfully",$message_ref,$body);
    */

    public static function getPOXResponse() {
        return '<?xml version="1.0" encoding="UTF-8"?>
    <imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
        <imsx_POXHeader>
            <imsx_POXResponseHeaderInfo>
                <imsx_version>V1.0</imsx_version>
                <imsx_messageIdentifier>%s</imsx_messageIdentifier>
                <imsx_statusInfo>
                    <imsx_codeMajor>%s</imsx_codeMajor>
                    <imsx_severity>status</imsx_severity>
                    <imsx_description>%s</imsx_description>
                    <imsx_messageRefIdentifier>%s</imsx_messageRefIdentifier>
                    <imsx_operationRefIdentifier>%s</imsx_operationRefIdentifier>
                </imsx_statusInfo>
            </imsx_POXResponseHeaderInfo>
        </imsx_POXHeader>
        <imsx_POXBody>%s
        </imsx_POXBody>
    </imsx_POXEnvelopeResponse>';
    }

    public static function getContextMemberships($membershipsid, $membershipsurl, $key_key, $secret, $groups=false, $signature=false, &$debug_log=false) {
        $content_type = "application/x-www-form-urlencoded";
        $membershipsid = htmlspecialchars($membershipsid);

        $messagetype = LTIConstants::LTI_MESSAGE_TYPE_CONTEXTMEMBERSHIPS;

        if($groups) {
            $messagetype = LTIConstants::LTI_MESSAGE_TYPE_CONTEXTMEMBERSHIPSWITHGROUPS;
        }

        $parameters = array(
            LTIConstants::EXT_CONTEXT_REQUEST_ID => $membershipsid,
            LTIConstants::LTI_MESSAGE_TYPE => $messagetype,
            LTIConstants::LTI_VERSION => LTIConstants::LTI_VERSION_1
        );

        $body = http_build_query($parameters, null,"&", PHP_QUERY_RFC3986);
        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
        $hash = base64_encode(sha1($body, TRUE));
        if ( $signature == "HMAC-SHA256" ) {
            $hmac_method = new OAuthSignatureMethod_HMAC_SHA256();
            $hash = base64_encode(hash('sha256', $body, TRUE));
        }
        $parameters['oauth_body_hash'] = $hash;

        $test_token = '';

        $test_consumer = new OAuthConsumer($key_key, $secret, NULL);

        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, "POST", $membershipsurl, $parameters);
        $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

        $header = $acc_req->to_header();
        $header .= PHP_EOL . "Content-Type: " . $content_type . PHP_EOL;

        return self::parseContextMembershipsResponse(Net::doBody($membershipsurl, "POST", $body,$header));
    }

    public static function parseContextMembershipsResponse($response) {
        $result = false;
        try{
            $xml = new \SimpleXMLElement(utf8_encode($response));
            $success = $xml->xpath("/message_response/statusinfo");

            if($success[0]->codemajor != "Success") {
                return $result;
            }
            $result = array();
            $members = $xml->xpath('/message_response/members/member');

            foreach($members as $index => $node) {
                $groups = array();
                foreach($node->groups as $k) {
                    foreach($k->group as $v) {
                        $groups[] = array(
                            "id" => $v->id->__toString(),
                            "title" => $v->title->__toString()
                        );
                    }
                }

                $result[] = array(
                    "user_id" => $node->user_id->__toString(),
                    "person_name_given" => $node->person_name_given->__toString(),
                    "person_name_family" => $node->person_name_family->__toString(),
                    "role" => $node->role->__toString(),
                    "roles" => $node->roles->__toString(),
                    "user_email" => $node->person_contact_email_primary->__toString(),
                    "user_name" => $node->person_name_full->__toString(),
                    "lis_result_sourcedid" => empty($node->lis_result_sourcedid->__toString()) ? $node->person_sourcedid->__toString() : $node->lis_result_sourcedid->__toString(),
                    "groups" => $groups
                );
            }
        } catch (\Exception $e) {
            throw new \Exception('Error: Unable to parse XML response' . $e->getMessage());
        }

        return $result;
    }


    public static function replaceResultRequest($grade, $sourcedid, $endpoint, $oauth_consumer_key, $oauth_consumer_secret, $signature=false) {
        $method="POST";
        $content_type = "application/xml";
        $operation = 'replaceResultRequest';
        $postBody = str_replace(
            array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
            array($sourcedid, $grade, $operation, uniqid()),
            self::getPOXGradeRequest());

        $more_headers=false;
        $response = sendOAuthBody("POST", $endpoint, $oauth_consumer_key, $oauth_consumer_secret,
            $content_type, $postBody, $more_headers, $signature);

        return parseResponse($response);
    }

    public static function parseResponse($response) {
        $retval = Array();
        try {
            $xml = new \SimpleXMLElement($response);
            $imsx_header = $xml->imsx_POXHeader->children();
            $parms = $imsx_header->children();
            $status_info = $parms->imsx_statusInfo;
            $retval['imsx_codeMajor'] = (string) $status_info->imsx_codeMajor;
            $retval['imsx_severity'] = (string) $status_info->imsx_severity;
            $retval['imsx_description'] = (string) $status_info->imsx_description;
            $retval['imsx_messageIdentifier'] = (string) $parms->imsx_messageIdentifier;
            $imsx_body = $xml->imsx_POXBody->children();
            $operation = $imsx_body->getName();
            $retval['response'] = $operation;
            $parms = $imsx_body->children();
        } catch (Exception $e) {
            throw new \Exception('Error: Unable to parse XML response' . $e->getMessage());
        }

        if ( $operation == 'readResultResponse' && isset($parms->result) && isset($parms->result->resultScore) ) {
           try {
               $retval['language'] =(string) $parms->result->resultScore->language;
               $retval['textString'] = (string) $parms->result->resultScore->textString;
           } catch (Exception $e) {
                throw new \Exception("Error: Body parse error: ".$e->getMessage());
           }
        }
        return $retval;
    }

    /**
     * Send a grade using the JSON protocol from IMS LTI 2.x
     *
     * @param debug_log This can either be false or an empty array.  If
     * this is an array, it is filled with data as the steps progress.
     * Each step is an array with a string message as the first element
     * and optional debug detail (i.e. like a post body) as the second
     * element.
     *
     * @return mixed If things go well this returns true.
     * If this goes badly, this returns a string with an error message.
     */
    public static function sendJSONGrade($grade, $comment, $result_url, $key_key, $secret, &$debug_log=false, $signature=false) {
        global $LastJSONGradeResponse;
        $LastJSONGradeResponse = false;

        $content_type = "application/vnd.ims.lis.v2.result+json";

        if ( is_array($debug_log) ) $debug_log[] = array('Sending '.$grade.' to result_url='.$result_url);

        $addStructureRequest = self::getResultJSON($grade, $comment);

        $postBody = self::jsonIndent(json_encode($addStructureRequest));
        if ( is_array($debug_log) )  $debug_log[] = array('Grade JSON Request',$postBody);

        $more_headers = false;
        $response = self::sendOAuthBody("PUT", $result_url, $key_key,
            $secret, $content_type, $postBody, $more_headers, $signature);

        if ( is_array($debug_log) )  $debug_log[] = array('Grade JSON Response',$response);

        global $LastOAuthBodyBaseString;
        $lbs = $LastOAuthBodyBaseString;
        if ( is_array($debug_log) )  $debug_log[] = array('Our base string',$lbs);

        // TODO: Be smarter about this :)
        return true;
    }

    /**
     * Return an array suitable for sending to the LTI 2.x result_url
     */
    public static function getResultJSON($grade, $comment) {
        $resultArray = array(
            "@context" => "http://purl.imsglobal.org/ctx/lis/v2/Result",
            "@type" => "Result",
            "comment" => $comment,
            "resultScore" => array(
                "@type" => "decimal",
                "@value" => $grade
            ),
        );
        return $resultArray;
    }

    /**
     * Send setings data using the JSON protocol from IMS LTI 2.x
     *
     * @param debug_log This can either be false or an empty array.  If
     * this is an array, it is filled with data as the steps progress.
     * Each step is an array with a string message as the first element
     * and optional debug detail (i.e. like a post body) as the second
     * element.
     *
     * @return mixed If things go well this returns true.
     * If this goes badly, this returns a string with an error message.
     */
    public static function sendJSONSettings($settings, $settings_url, $key_key, $secret, &$debug_log=false, $signature=false) {
        $content_type = "application/vnd.ims.lti.v2.toolsettings.simple+json";

        if ( is_array($debug_log) ) $debug_log[] = array('Sending '.count($settings).' settings to settings_url='.$settings_url);
        // Make sure everything is a string
        $sendsettings = array();
        foreach ( $settings as $k => $v ) {
            $sendsettings[$k] = "".$v;
        }

        $postBody = self::jsonIndent(json_encode($sendsettings));
        if ( is_array($debug_log) )  $debug_log[] = array('Settings JSON Request',$postBody);

        $more_headers = false;
        $response = self::sendOAuthBody("PUT", $settings_url, $key_key,
            $secret, $content_type, $postBody, $more_headers, $signature);

        if ( is_array($debug_log) )  $debug_log[] = array('Settings JSON Response',$response);

        global $LastOAuthBodyBaseString;
        $lbs = $LastOAuthBodyBaseString;
        if ( is_array($debug_log) )  $debug_log[] = array('Our base string',$lbs);

        // TODO: Be better at error checking...
        return true;
    }

    /**
     * Send a JSON body LTI 2.x Style
     *
     * @param debug_log This can either be false or an empty array.  If
     * this is an array, it is filled with data as the steps progress.
     * Each step is an array with a string message as the first element
     * and optional debug detail (i.e. like a post body) as the second
     * element.
     *
     * @return mixed If things go well this returns true.
     * If this goes badly, this returns a string with an error message.
     */
    public static function sendJSONBody($method, $postBody, $content_type,
            $rest_url, $key_key, $secret, &$debug_log=false, $signature=false)
    {
        if ( is_array($debug_log) ) $debug_log[] = array('Sending '.strlen($postBody).' bytes to rest_url='.$rest_url);

        $more_headers = false;
        $response = self::sendOAuthBody($method, $rest_url, $key_key,
            $secret, $content_type, $postBody);

        if ( is_array($debug_log) )  $debug_log[] = array('Caliper JSON Response',$response);

        global $LastOAuthBodyBaseString;
        $lbs = $LastOAuthBodyBaseString;
        if ( is_array($debug_log) )  $debug_log[] = array('Our base string',$lbs);

        return true;
    }

    /**
     * ltiLinkUrl - Returns true if we can return LTI Links for this launch
     *
     * IMS Public Draft: http://www.imsglobal.org/specs/lticiv1p0
     *
     * @return string The content_item_return_url or false
     */
    public static function ltiLinkUrl($postdata=false) {
        if ( $postdata === false ) $postData = $_POST;
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        if ( isset($postdata['accept_media_types']) ) {
            $ltilink_mimetype = 'application/vnd.ims.lti.v1.ltilink';
            $m = new Mimeparse;
            $ltilink_allowed = $m->best_match(array($ltilink_mimetype), $postdata['accept_media_types']);
            if ( $ltilink_mimetype != $ltilink_allowed ) return false;
            return $postdata['content_item_return_url'];
        }
        return false;
    }

    /**
     * getLtiLinkJson - Get a JSON object for an LTI Link Content Item Return
     *
     * @param url The launch URL of the tool that is about to be placed
     * @param title A plain text title of the content-item.
     * @param text A plain text description of the content-item.
     * @param icon An image URL of an icon
     * @param fa_icon The class name of a FontAwesome icon
     *
     */
    public static function getLtiLinkJSON($url, $title=false, $text=false,
        $icon=false, $fa_icon=false, $custom=false )
    {
        $return = '{
            "@context" : "http://purl.imsglobal.org/ctx/lti/v1/ContentItem",
                "@graph" : [
                { "@type" : "LtiLinkItem",
                    "@id" : ":item2",
                    "title" : "A cool tool hosted in the Tsugi environment.",
                    "mediaType" : "application/vnd.ims.lti.v1.ltilink",
                    "text" : "For more information on how to build and host powerful LTI-based Tools quickly, see www.tsugi.org",
                    "url" : "http://www.tsugi.org/",
                    "placementAdvice" : {
	                "presentationDocumentTarget" : "window",
	                "displayHeight" : 1024,
	                "displayWidth" : 800
                    },
                    "icon" : {
                        "@id" : "https://static.tsugi.org/img/default-icon.png",
                        "fa_icon" : "fa-magic",
                        "width" : 64,
                        "height" : 64
                    }
                }
            ]
        }';

        $json = json_decode($return);
        $json->{'@graph'}[0]->url = $url;
        if ( $title ) $json->{'@graph'}[0]->{'title'} = $title;
        if ( $text ) $json->{'@graph'}[0]->{'text'} = $text;
        if ( $icon ) $json->{'@graph'}[0]->{'icon'}->{'@id'} = $icon;
        if ( $fa_icon ) $json->{'@graph'}[0]->icon->fa_icon = $fa_icon;
        if ( $custom ) $json->{'@graph'}[0]->custom = $custom;
        return $json;
    }

    // Compares base strings, start of the mis-match
    // Returns true if the strings are identical
    // This is setup to be displayed in <pre> tags as newlines are added
    public static function compareBaseStrings($string1, $string2)
    {
    	if ( $string1 == $string2 ) return true;

    	$out2 = "";
    	$out1 = "";
        $chars = 0;
    	$oops = false;
        for($i=0; $i<strlen($string1)&&$i<strlen($string2); $i++) {
    		if ( $oops || $string1[$i] == $string2[$i] ) {
    			$out1 = $out1 . $string1[$i];
    			$out2 = $out2 . $string2[$i];
    		} else {
    			$out1 = $out1 . ' ->' . $string1[$i] .'<- ';
    			$out2 = $out2 . ' ->' . $string2[$i] .'<- ';
    			$oops = true;
    		}
    		$chars = $chars + 1;
    		if ( $chars > 79 ) {
    			$out1 .= "\n";
    			$out2 .= "\n";
    			$chars = 0;
    		}
    	}
    	if ( $i < strlen($string1) ) {
    		$out2 = $out2 . ' -> truncated ';
    		for($i=0; $i<strlen($string1); $i++) {
    			$out1 = $out1 . $string1[$i];
    			$chars = $chars + 1;
    			if ( $chars > 79 ) {
    				$out1 .= "\n";
    				$chars = 0;
    			}
    		}
    	}

    	if ( $i < strlen($string2) ) {
    		$out1 = $out1 . ' -> truncated ';
    		for($i=0; $i<strlen($string2); $i++) {
    			$out2 = $out2 . $string2[$i];
    			$chars = $chars + 2;
    			if ( $chars > 79 ) {
    				$out2 .= "\n";
    				$chars = 0;
    			}
    		}
    	}
    	return $out1 . "\n-------------\n" . $out2 . "\n";
    }

    public static function jsonIndent($json) {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        $json = str_replace('\/', '/',$json);
        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }
        return $result;
    }

}
