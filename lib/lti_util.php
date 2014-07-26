<?php

// Just turn if off...
if ( function_exists ( 'libxml_disable_entity_loader' ) ) libxml_disable_entity_loader();

require_once 'lti.class.php';

$ltiUtilTogglePre_div_id = 1;
// Useful for debugging
function ltiUtilTogglePre($title, $content) {
    global $ltiUtilTogglePre_div_id;
    echo('<b>'.$title);
    echo(' (<a href="#" onclick="dataToggle('.
        "'ltiUtilTogglePre_".$ltiUtilTogglePre_div_id."'".');return false;">Toggle</a>)</b><br/>'."\n");
    echo('<pre id="ltiUtilTogglePre_'.$ltiUtilTogglePre_div_id.'" style="display:none; border: solid 1px">'."\n");
    echo(htmlent_utf8($content));
    echo("</pre>\n");
    $ltiUtilTogglePre_div_id = $ltiUtilTogglePre_div_id + 1;
}

function ltiUtilToggleHead() {
   return '<script language="javascript"> 
function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
} 
  //]]> 
</script>
';
}

// These are like static covers and are deprecated already :)
// TODO: Remove lti global functions when all the code is cleaned up

function ltiIsRequest() 
{
    return \Tsugi\LTI::IsRequest();
}

function signParameters($oldparms, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret,
    $submit_text = false, $org_id = false, $org_desc = false)
{
    return \Tsugi\LTI::signParameters($oldparms, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret,
    $submit_text, $org_id, $org_desc);
}

function postLaunchHTML($newparms, $endpoint, $debug=false, $iframeattr=false) 
{
    return \Tsugi\LTI::postLaunchHTML($newparms, $endpoint, $debug, $iframeattr);
}

function ltiBodyRequest($url, $method, $data, $optional_headers = null)
{
    return \Tsugi\LTI::BodyRequest($url, $method, $data, $optional_headers);
}

function addCustom(&$parms, $custom) 
{
    return \Tsugi\LTI::addCustom($parms, $custom);
}

function getLastOAuthBodyBaseString() 
{
    return \Tsugi\LTI::getLastOAuthBodyBaseString();
}

function getLastOAuthBodyHashInfo() 
{
    return \Tsugi\LTI::getLastOAuthBodyHashInfo();
}

function getOAuthKeyFromHeaders()
{
    return \Tsugi\LTI::getOAuthKeyFromHeaders();
}

function handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret)
{
    return \Tsugi\LTI::handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret);
}

function sendOAuthGET($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $accept_type)
{
    return \Tsugi\LTI::sendOAuthGET($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $accept_type);
}

function sendOAuthBodyPOST($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body)
{
    return \Tsugi\LTI::sendOAuthBodyPOST($endpoint, $oauth_consumer_key, $oauth_consumer_secret, $content_type, $body);
}

function getPOXGradeRequest() 
{
    return \Tsugi\LTI::getPOXGradeRequest();
}

function getPOXResponse() 
{
    return \Tsugi\LTI::getPOXResponse();
}

function getPOXRequest() 
{
    return \Tsugi\LTI::getPOXRequest();
}

function replaceResultRequest($grade, $sourcedid, $endpoint, $oauth_consumer_key, $oauth_consumer_secret) 
{
    return \Tsugi\LTI::replaceResultRequest($grade, $sourcedid, $endpoint, $oauth_consumer_key, $oauth_consumer_secret);
}

function parseResponse($response) 
{
    return \Tsugi\LTI::parseResponse($response);
}

function ltiCompareBaseStrings($string1, $string2)
{
    return \Tsugi\LTI::CompareBaseStrings($string1, $string2);
}

