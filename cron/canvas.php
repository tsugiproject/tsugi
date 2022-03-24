<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use Tsugi\Util\Caliper;

require_once "../config.php";

if ( ! U::isCli() ) {
    echo("Must run comand line\n");
    return;
}

$PDOX = LTIX::getConnection();

$sql = "SELECT event_id, e.launch AS launch, e.created_at AS created_at, u.email AS email, user_key AS user_id,
               l.title AS link_title, l.path AS path, key_key, k.secret AS secret
    FROM {$CFG->dbprefix}cal_event AS e
    LEFT JOIN {$CFG->dbprefix}lti_key AS k ON k.key_id = e.key_id
    LEFT JOIN {$CFG->dbprefix}lti_user AS u ON u.user_id = e.user_id
    LEFT JOIN {$CFG->dbprefix}lti_context AS c ON c.context_id = e.context_id
    LEFT JOIN {$CFG->dbprefix}lti_link AS l ON l.link_id = e.link_id
    LEFT JOIN {$CFG->dbprefix}lti_membership AS m ON m.user_id = e.user_id AND m.context_id = e.context_id
    WHERE e.launch IS NOT NULL
    ORDER BY e.created_at ASC LIMIT 1";
$row = $PDOX->rowDie($sql);

if ( $row === false ) {
    echo("Nothing to process\n");
    return;
}
print_r($row);
$launch = $row['launch'];
$email = $row['email'];
$user_id = $row['user_id'];
$name = $row['link_title'];
$application = $CFG->apphome;
$page = $row['path'];
$key_key = $row['key_key'];
$secret = LTIX::decrypt_secret($row['secret']);

if ( strpos($page, $CFG->apphome) === 0 ) {
    $page = substr($page, strlen($CFG->apphome) );
}
// $dt = new DateTime($row['created_at']);
$format = 'Y-m-d H:i:s';
$dt = DateTime::createFromFormat($format, $row['created_at']);
$timestamp = $dt->getTimeStamp();
$pieces = explode('::',$launch);

print_r($pieces);
if ( $pieces[0] != 'canvas' ) die('Can only handle Canvas');
$caliperUrl = $pieces[1];
$caliperUrl = str_replace('xapi','caliper',$caliperUrl);

$caliperBody = Caliper::sensorCanvasPageView($user_id, $application, $page, $timestamp, $name);

echo("application=$application page=$page\n");
echo("dt=".$timestamp."\n");
echo("key_key=".$key_key."\n");
echo("secret=".$secret."\n");
echo("caliperUrl=".$caliperUrl."\n");

echo($caliperBody);

$content_type='application/json';

$debuglog = array();
$response = LTI::sendJSONBody("POST", $caliperBody, $content_type, $caliperUrl, $key_key, $secret, $debug_log);

$response_code = Net::getLastHttpResponse();

global $LastOAuthBodyBaseString;
$OUTPUT->togglePre("Registration Request Headers",htmlent_utf8(Net::getBodySentDebug()));
$OUTPUT->togglePre("Registration Request Base String",$LastOAuthBodyBaseString);
echo("<p>Http Response code = $response_code</p>\n");
$OUTPUT->togglePre("Registration Response Headers",htmlent_utf8(Net::getBodyReceivedDebug()));
$OUTPUT->togglePre("Registration Response",htmlent_utf8(LTI::jsonIndent($response)));

print_r($debug_log);

$sql = "DELETE FROM {$CFG->dbprefix}cal_event WHERE event_id = :event_id";
$PDOX->queryDie($sql, array(':event_id' => $row['event_id']));

error_log("Send event_id=". $row['event_id'] ." response=". $response_code);

