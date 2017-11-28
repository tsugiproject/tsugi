<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("util.php");

$request_headers = apache_request_headers();
$agent = U::get($request_headers,'User-Agent');
if ( $agent && stripos($agent,'Google Web Preview') !== false ) {
    // TODO: Be more general :)
    echo('<center><img src="'.$CFG->apphome.'/logo.png"></center>'."\n");
    return;
}

session_start();

// Lets be really picky about the path...
$path = U::parse_rest_path();
if ( count($path) != 3 ) {
    $_SESSION['error'] = 'Invalid resource id format';
    header('Location: '.$CFG->apphome);
    return;
}
$pieces = explode(':',$path[2]);
if ( count($pieces) != 4 || strlen($pieces[1]) != 6 || strlen($pieces[3]) != 6 
     || ! is_numeric($pieces[0]) || ! is_numeric($pieces[2]) ) {
    $_SESSION['error'] = 'Invalid resource id format';
    header('Location: '.$CFG->apphome);
    return;
}

echo("<pre>\n");

$gc_course = $pieces[0];
$user_mini_sig = $pieces[1];
$link_id = $pieces[2];
$link_mini_sig = $pieces[3];
echo("user_mini_sig=".$user_mini_sig."\n");
echo("link_mini_sig=".$link_mini_sig."\n");

$context_url = $gc_course . ':' . $user_mini_sig;
echo("context_url=".$context_url."\n");
$context_key = 'gclass:' . $context_url;
echo("context_key=".$context_key."\n");
$context_sha256 = lti_sha256($context_key);
echo("context_sha256=".$context_sha256."\n");

$PDOX = LTIX::getConnection();

// Load up the stuff course / link stuff / big inner join

$sql = "SELECT * 
    FROM {$CFG->dbprefix}lti_context AS C
    JOIN {$CFG->dbprefix}lti_user AS U
        ON C.user_id = U.user_id
    JOIN {$CFG->dbprefix}lti_link AS L
        ON C.context_id = L.context_id
    WHERE context_sha256 = :context_sha256 AND context_key = :context_key
    AND link_id = :LID
    LIMIT 1";

$row = $PDOX->rowDie($sql,
    array(
        ':context_sha256' => $context_sha256,
        ':context_key' => $context_key,
        ':LID' => $link_id )
);

if ( ! $row ) {
    $_SESSION['error'] = 'Could not lookup resource id';
    header('Location: '.$CFG->apphome);
    return;
}

$gc_secret = $row['gc_secret'];
$path = $row['path'];
$owner_id = $row['user_id'];
$gc_coursework = $row['link_key'];
$user_email = $row['email'];

// Do some validation...
$plain = $CFG->google_classroom_secret.$gc_course.$owner_id.$CFG->google_classroom_secret;
echo("plain=".$plain."\n");
$user_mini_check = lti_sha256($plain);
$user_mini_check = substr($user_mini_check,0,6);
echo("user_mini_check=".$user_mini_check."\n");

$plain = $gc_secret.$context_url.$link_id.$gc_secret;
echo("plain=".$plain."\n");
$link_mini_check = lti_sha256($plain);
$link_mini_check = substr($link_mini_check,0,6);
echo("link_mini_check=".$link_mini_check."\n");

if ( $link_mini_check != $link_mini_sig || $user_mini_check != $user_mini_sig ) {
    $_SESSION['error'] = 'Could not validate resource id';
    header('Location: '.$CFG->apphome);
    return;
}

// Time to talk to Google.

// Try access token from session when LTIX adds it.
$accessTokenStr = retrieve_existing_token($owner_id);
if ( ! $accessTokenStr ) {
    $_SESSION['error'] = 'Classroom connection not set up, see your instructor';
    header('Location: '.$CFG->apphome);
    return;
}

// Get the API client and construct the service object.
$client = getClient($accessTokenStr);
if ( ! $client ) {
    $_SESSION['error'] = 'Classroom connection failed';
    error_log('Classroom connection failed id='.$owner_id);
    error_log($accessTokenStr);
    header('Location: '.$CFG->apphome);
    return;
}

// Lets talk to Google
$service = new Google_Service_Classroom($client);

// Get the user's profile information

$x = $client->getAccessToken();
$access_token = $x['access_token'];
echo("AT=$access_token \n");

// v1/userProfiles/{userId}
$user_info_url = "https://classroom.googleapis.com/v1/userProfiles/me" ."?alt=json&access_token=" .
           $access_token;

echo("UIU=".$user_info_url."\n");
$response = \Tsugi\Util\Net::doGet($user_info_url);
echo($response."\n");
$user = json_decode($response);
var_dump($user);

// Check for membership
$instructor = false;
if ( $user->emailAddress == $user_email ) {
    echo("+++++++++++++ INSTRUCTOR\n");
    $instructor = true;
} else {

    // v1/userProfiles/{userId}
    // https://classroom.googleapis.com/v1/courses/{courseId}/students/{userId}
    $membership_info_url = "https://classroom.googleapis.com/v1/courses/".$gc_course."/students/me" ."?alt=json&access_token=" .
           $access_token;

    echo("IURL=".$membership_info_url."\n");
    $response = \Tsugi\Util\Net::doGet($membership_info_url);
    echo($response."\n");
    $membership = json_decode($response);
    var_dump($membership);
}



// https://developers.google.com/classroom/guides/manage-coursework
// service.courses().courseWork().studentSubmissions().list(
//    courseId=<course ID or alias>,
//    courseWorkId='-',
//    userId="me").execute()

// Returns a student of a course.
// https://developers.google.com/classroom/reference/rest/v1/courses.students/get
// https://classroom.googleapis.com/v1/courses/{courseId}/students/{userId}

?>
<h1>I am a launch</h1>
<pre>
Row:
<?php
print_r($row);
?>
Path:
<?php
print_r($path);
?>
Post:
<?php
print_r($_POST);
?>
<hr/>
Get:
<?php
print_r($_GET);
?>
<hr/>
Apache Request Headers:
<?php
print_r($request_headers);
error_log(\Tsugi\UI\Output::safe_var_dump($request_headers));
