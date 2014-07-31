<?php

namespace Tsugi;

require_once 'OAuth.php';
require_once 'net.class.php';

use \Tsugi\OAuth\TrivialOAuthDataStore;
use \Tsugi\OAuth\OAuthServer;
use \Tsugi\OAuth\OAuthSignatureMethod_HMAC_SHA1;
use \Tsugi\OAuth\OAuthRequest;
use \Tsugi\OAuth\OAuthConsumer;;
use \Tsugi\OAuth\OAuthUtil;

use \Tsugi\Net;

class LTI {

    // Returns true if this is a Basic LTI message
    // with minimum values to meet the protocol
    public static function isRequest() {
        if ( !isset($_REQUEST["lti_message_type"]) ) return false;
        if ( !isset($_REQUEST["lti_version"]) ) return false;
        $good_message_type = $_REQUEST["lti_message_type"] == "basic-lti-launch-request" ||
            $_REQUEST["lti_message_type"] == "ToolProxyReregistrationRequest";
        $good_lti_version = $_REQUEST["lti_version"] == "LTI-1p0" || $_REQUEST["lti_version"] == "LTI-2p0";
        if ($good_message_type and $good_lti_version ) return(true);
        return false;
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

        $test_token = '';

        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
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

    public static function postLaunchHTML($newparms, $endpoint, $debug=false, $iframeattr=false) {
        global $LastOAuthBodyBaseString;
        $r = "<div id=\"ltiLaunchFormSubmitArea\">\n";
        if ( $iframeattr =="_blank" ) {
            $r = "<form action=\"".$endpoint."\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" target=\"_blank\" encType=\"application/x-www-form-urlencoded\">\n" ;
        } else if ( $iframeattr ) {
            $r = "<form action=\"".$endpoint."\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" target=\"basicltiLaunchFrame\" encType=\"application/x-www-form-urlencoded\">\n" ;
        } else {
            $r = "<form action=\"".$endpoint."\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n" ;
        }
        $submit_text = $newparms['ext_submit'];
        foreach($newparms as $key => $value ) {
            $key = htmlspec_utf8($key);
            $value = htmlspec_utf8($value);
            if ( $key == "ext_submit" ) {
                $r .= "<input type=\"submit\" name=\"";
            } else {
                $r .= "<input type=\"hidden\" name=\"";
            }
            $r .= $key;
            $r .= "\" value=\"";
            $r .= $value;
            $r .= "\"/>\n";
        }
        if ( $debug ) {
            $r .= "<script language=\"javascript\"> \n";
            $r .= "  //<![CDATA[ \n" ;
            $r .= "function basicltiDebugToggle() {\n";
            $r .= "    var ele = document.getElementById(\"basicltiDebug\");\n";
            $r .= "    if(ele.style.display == \"block\") {\n";
            $r .= "        ele.style.display = \"none\";\n";
            $r .= "    }\n";
            $r .= "    else {\n";
            $r .= "        ele.style.display = \"block\";\n";
            $r .= "    }\n";
            $r .= "} \n";
            $r .= "  //]]> \n" ;
            $r .= "</script>\n";
            $r .= "<a id=\"displayText\" href=\"javascript:basicltiDebugToggle();\">";
            $r .= LTI::get_string("toggle_debug_data","basiclti")."</a>\n";
            $r .= "<div id=\"basicltiDebug\" style=\"display:none\">\n";
            $r .=  "<b>".LTI::get_string("basiclti_endpoint","basiclti")."</b><br/>\n";
            $r .= $endpoint . "<br/>\n&nbsp;<br/>\n";
            $r .=  "<b>".LTI::get_string("basiclti_parameters","basiclti")."</b><br/>\n";
            foreach($newparms as $key => $value ) {
                $key = htmlspec_utf8($key);
                $value = htmlspec_utf8($value);
                $r .= "$key = $value<br/>\n";
            }
            $r .= "&nbsp;<br/>\n";
            $r .= "<p><b>".LTI::get_string("basiclti_base_string","basiclti")."</b><br/>\n".$LastOAuthBodyBaseString."</p>\n";
            $r .= "</div>\n";
        }
        $r .= "</form>\n";
        if ( $iframeattr && $iframeattr != '_blank' ) {
            $r .= "<iframe name=\"basicltiLaunchFrame\"  id=\"basicltiLaunchFrame\" src=\"\"\n";
            $r .= $iframeattr . ">\n<p>".LTI::get_string("frames_required","basiclti")."</p>\n</iframe>\n";
        }
        if ( ! $debug ) {
            $ext_submit = "ext_submit";
            $ext_submit_text = $submit_text;
            $r .= " <script type=\"text/javascript\"> \n" .
                "  //<![CDATA[ \n" .
                "    document.getElementById(\"ltiLaunchForm\").style.display = \"none\";\n" .
                "    nei = document.createElement('input');\n" .
                "    nei.setAttribute('type', 'hidden');\n" .
                "    nei.setAttribute('name', '".$ext_submit."');\n" .
                "    nei.setAttribute('value', '".$ext_submit_text."');\n" .
                "    document.getElementById(\"ltiLaunchForm\").appendChild(nei);\n" .
                "    document.ltiLaunchForm.submit(); \n" .
                "  //]]> \n" .
                " </script> \n";
        }
        $r .= "</div>\n";
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

    public static function addCustom(&$parms, $custom) {
        foreach ( $custom as $key => $val) {
          $key = strtolower($key);
          $nk = "";
          for($i=0; $i < strlen($key); $i++) {
            $ch = substr($key,$i,1);
            if ( $ch >= "a" && $ch <= "z" ) $nk .= $ch;
            else if ( $ch >= "0" && $ch <= "9" ) $nk .= $ch;
            else $nk .= "_";
          }
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


    public static function getOAuthKeyFromHeaders()
    {
        $request_headers = OAuthUtil::get_headers();
        // print_r($request_headers);

        if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
            $header_parameters = OAuthUtil::split_header($request_headers['Authorization']);

            // echo("HEADER PARMS=\n");
            // print_r($header_parameters);
            return $header_parameters['oauth_consumer_key'];
        }
        return false;
    }

    public static function handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret)
    {
        $request_headers = OAuthUtil::get_headers();
        // print_r($request_headers);

        // Must reject application/x-www-form-urlencoded
        if ($request_headers['Content-Type'] == 'application/x-www-form-urlencoded' ) {
            throw new Exception("OAuth request body signing must not use application/x-www-form-urlencoded");
        }

        if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
            $header_parameters = OAuthUtil::split_header($request_headers['Authorization']);

            // echo("HEADER PARMS=\n");
            // print_r($header_parameters);
            $oauth_body_hash = $header_parameters['oauth_body_hash'];
            // echo("OBH=".$oauth_body_hash."\n");
        }

        if ( ! isset($oauth_body_hash)  ) {
            throw new Exception("OAuth request body signing requires oauth_body_hash body");
        }

        // Verify the message signature
        $store = new TrivialOAuthDataStore();
        $store->add_consumer($oauth_consumer_key, $oauth_consumer_secret);

        $server = new OAuthServer($store);

        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);
        $request = OAuthRequest::from_request();

        global $LastOAuthBodyBaseString;
        $LastOAuthBodyBaseString = $request->get_signature_base_string();
        // echo($LastOAuthBodyBaseString."\n");

        try {
            $server->verify_request($request);
        } catch (Exception $e) {
            $message = $e->getMessage();
            throw new Exception("OAuth signature failed: " . $message);
        }

        $postdata = file_get_contents('php://input');
        // echo($postdata);

        $hash = base64_encode(sha1($postdata, TRUE));

        global $LastOAuthBodyHashInfo;
        $LastOAuthBodyHashInfo = "hdr_hash=$oauth_body_hash body_len=".strlen($postdata)." body_hash=$hash";

        if ( $hash != $oauth_body_hash ) {
            throw new Exception("OAuth oauth_body_hash mismatch");
        }

        return $postdata;
    }

    public static function sendOAuthGET($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $accept_type)
    {
        $test_token = '';
        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
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

    public static function sendOAuthBodyPOST($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body)
    {
        $hash = base64_encode(sha1($body, TRUE));

        $parms = array('oauth_body_hash' => $hash);

        $test_token = '';
        $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
        $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, "POST", $endpoint, $parms);
        $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

        // Pass this back up "out of band" for debugging
        global $LastOAuthBodyBaseString;
        $LastOAuthBodyBaseString = $acc_req->get_signature_base_string();

        $header = $acc_req->to_header();
        $header = $header . "\r\nContent-Type: " . $content_type . "\r\n";

        return Net::doBody($endpoint, "POST", $body,$header);
    }

    /*  $postBody = str_replace(
          array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
          array($sourcedid, $_REQUEST['grade'], $operation, uniqid()),
          self::getPOXGradeRequest());
    */

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

    public static function replaceResultRequest($grade, $sourcedid, $endpoint, $oauth_consumer_key, $oauth_consumer_secret) {
        $method="POST";
        $content_type = "application/xml";
        $operation = 'replaceResultRequest';
        $postBody = str_replace(
            array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
            array($sourcedid, $grade, $operation, uniqid()),
            self::getPOXGradeRequest());

        $response = sendOAuthBodyPOST($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $postBody);
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
            throw new Exception('Error: Unable to parse XML response' . $e->getMessage());
        }

        if ( $operation == 'readResultResponse' ) {
           try {
               $retval['language'] =(string) $parms->result->resultScore->language;
               $retval['textString'] = (string) $parms->result->resultScore->textString;
           } catch (Exception $e) {
                throw new Exception("Error: Body parse error: ".$e->getMessage());
           }
        }
        return $retval;
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
}
