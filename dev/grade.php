<?php
error_log(__DIR__);
require_once(__DIR__.'/../config.php');

use Tsugi\OAuth\OAuthUtil;
use Tsugi\Util\LTI;

$old_error_handler = set_error_handler("myErrorHandler");

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    // echo("YO ". $errorno . $errstr . "\n");
    if ( strpos($errstr, 'deprecated') !== false ) return true;
    return false;
}

function response($output) {
    error_log($output);
    echo($output);
}

ini_set("display_errors", 1);

if ( !isset ( $_REQUEST['b64'] ) ) {
   error_log("Missing b64 parameter");
   die("Missing b64 parameter");
}

// Make sure to add the file to the session id in case
// multiple people are running this on the same server
$b64 = $_REQUEST['b64'];
session_id(md5($b64 . __FILE__));
session_start();

// For my application, We only allow application/xml
$request_headers = OAuthUtil::get_headers();
error_log(\Tsugi\UI\Output::safe_var_dump($request_headers));

$hct = \Tsugi\Util\LTI::getContentTypeHeader();

if (strpos($hct,'application/xml') === false ) {
   header('Content-Type: text/plain');
   // print_r($request_headers);
   error_log("Must be content type xml, found ".$hct);
   die("Must be content type xml, found ".$hct);
}

header('Content-Type: application/xml; charset=utf-8');

// Get skeleton response
$response = LTI::getPOXResponse();

// Pull out the key and secret from the parameter
$b64dec = base64_decode($b64);
$b64 = explode(":::", $b64dec);

$oauth_consumer_key = $b64[0];
$oauth_consumer_secret = $b64[1];
$operation = "unknown";
$message_ref = 42;

if ( strlen($oauth_consumer_key) < 1 || strlen($oauth_consumer_secret) < 1 ) {
   response(sprintf($response,uniqid(),'failure', "Missing key/secret B64=$b64dec B64key=$oauth_consumer_key secret=$oauth_consumer_secret",$message_ref,$operation,""));
   exit();
}

$header_key = LTI::getOAuthKeyFromHeaders();
if ( strlen($header_key) < 1 ) {
   response(sprintf($response,uniqid(),'failure', "Empty header key. Note that some proxy configurations do not pass the Authorization header.",$message_ref,$operation,""));
   exit();
} else if ( $header_key != $oauth_consumer_key ) {
   response(sprintf($response,uniqid(),'failure', "B64key=$oauth_consumer_key HDR=$header_key",$message_ref,$operation,""));
   exit();
}

try {
    $body = LTI::handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret);
    $xml = new SimpleXMLElement($body);
    $imsx_header = $xml->imsx_POXHeader->children();
    $parms = $imsx_header->children();
    $message_ref = (string) $parms->imsx_messageIdentifier;

    $imsx_body = $xml->imsx_POXBody->children();
    $operation = $imsx_body->getName();
    $parms = $imsx_body->children();
} catch (Exception $e) {
    global $LastOAuthBodyBaseString;
	global $LastOAuthBodyHashInfo;
    $retval = sprintf($response,uniqid(),'failure', $e->getMessage().
        " B64key=$oauth_consumer_key HDRkey=$header_key secret=$oauth_consumer_secret",uniqid(),$operation,"") .
        "<!--\n".
        "Base String:\n".$LastOAuthBodyBaseString."\n".
		"Hash Info:\n".$LastOAuthBodyHashInfo."\n-->\n";
    response($retval);
    exit();
}

$sourcedid = (string) $parms->resultRecord->sourcedGUID->sourcedId;
if ( !isset($sourcedid) && strlen($sourcedid) > 0 ) {
   response(sprintf($response,uniqid(),'failure', "Missing required lis_result_sourcedid",$message_ref,$operation,""));
   exit();
}

$gradebook = isset($_SESSION['cert_gradebook']) ? $_SESSION['cert_gradebook'] : Array();

$top_tag = str_replace("Request","Response",$operation);
$body_tag = "\n<".$top_tag."/>";
if ( $operation == "replaceResultRequest" ) {
    $score =  (string) $parms->resultRecord->result->resultScore->textString;
    $fscore = (float) $score;
    if ( ! is_numeric($score) ) {
        response(sprintf($response,uniqid(),'failure', "Score must be numeric",$message_ref,$operation,$body_tag));
        exit();
    }
    $fscore = (float) $score;
    if ( $fscore < 0.0 || $fscore > 1.0 ) {
        response(sprintf($response,uniqid(),'failure', "Score not between 0.0 and 1.0",$message_ref,$operation,$body_tag));
        exit();
    }
    response(sprintf($response,uniqid(),'success', "Score for $sourcedid is now $score",$message_ref,$operation,$body_tag));
    $gradebook[$sourcedid] = $score;
} else if ( $operation == "readResultRequest" ) {
    $score =  $gradebook[$sourcedid];
    $body = '
    <readResultResponse>
      <result>
        <resultScore>
          <language>en</language>
          <textString>%s</textString>
        </resultScore>
      </result>
    </readResultResponse>';
    $body = sprintf($body,$score);
    response(sprintf($response,uniqid(),'success', "Score read successfully",$message_ref,$operation,$body));
} else if ( $operation == "deleteResultRequest" ) {
    unset( $gradebook[$sourcedid]);
    response(sprintf($response,uniqid(),'success', "Score deleted",$message_ref,$operation,$body_tag));
} else {
    response(sprintf($response,uniqid(),'unsupported', "Operation not supported - $operation",$message_ref,$operation,""));
}
$_SESSION['cert_gradebook'] = $gradebook;
// print_r($gradebook);
?>
