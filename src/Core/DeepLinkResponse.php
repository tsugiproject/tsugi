<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;

/**
 * This is a general purpose DeepLink class with no Tsugi-specific dependencies.
 *
 * Deep Linking 2.0 spec:
 * https://www.imsglobal.org/spec/lti-dl/v2p0
 */
class DeepLinkResponse extends \Tsugi\Util\DeepLinkResponse {

    function __construct($request) {
        parent::__construct($request);
    }

    /**
     * Make up a response
     *
     * @param $endform Some HTML to be included before the form closing tag
     *     $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
     * @param $debug boolean true to pause process to debug.
     * @param $iframeattr A string of attributes to put on the iframe tag
     *
     */
    function prepareResponse($endform=false, $debug=false, $iframeattr=false) {
        global $TSUGI_LAUNCH;

        $params = $this->getContentItemSelection();
        $return_url = $this->returnUrl();


        $debug_log = array();
        $issuer = $TSUGI_LAUNCH->ltiParameter('issuer_client');
        $jwt = LTI13::base_jwt($issuer, 'subject', $debug_log);

        // Yup this is weird - nonce is not a jwt concept in general
        // but ContentItemResponse strangely requires it...  - Learned at D2L
        $jwt["nonce"] = md5(time()-60);

        $launch_jwt = U::GET($_SESSION, 'tsugi_jwt');
        if ( is_object($launch_jwt) && isset($launch_jwt->body) ) {
            $body = $launch_jwt->body;
            if ( isset($body->iss) ) $jwt['aud'] = $body->iss;
            if ( isset($body->{LTI13::DEPLOYMENT_ID_CLAIM}) ) $jwt[LTI13::DEPLOYMENT_ID_CLAIM] = $body->{LTI13::DEPLOYMENT_ID_CLAIM};
        }

        foreach($jwt as $k => $v) {
            $params->{$k} = $v;
        }

        // Easter egg to set message returns (D2L)
        if ( strlen(U::get($_GET, "message_log")) > 0 ) {
            $params->{DeepLinkResponse::MESSAGE_LOG} = U::get($_GET, "message_log");
        }
        if ( strlen(U::get($_GET, "message_msg")) > 0 ) {
            $params->{DeepLinkResponse::MESSAGE_MSG} = U::get($_GET, "message_msg");
        }
        if ( strlen(U::get($_GET, "message_errorlog")) > 0 ) {
            $params->{DeepLinkResponse::MESSAGE_ERRORLOG} = U::get($_GET, "message_errorlog");
        }
        if ( strlen(U::get($_GET, "message_errormsg")) > 0 ) {
            $params->{DeepLinkResponse::MESSAGE_ERRORMSG} = U::get($_GET, "message_errormsg");
        }

        $jws = LTIX::encode_jwt($params);
        $html = LTI13::build_jwt_html($return_url, $jws);
        return $html;
    }

}
