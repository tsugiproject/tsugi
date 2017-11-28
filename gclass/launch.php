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

$gc_course = $pieces[0];
$user_mini_sig = $pieces[1];
$link_id = $pieces[2];
$link_mini_sig = $pieces[3];

$context_url = $gc_course . ':' . $user_mini_sig;
$context_key = 'gclass:' . $context_url;
$context_sha256 = lti_sha256($context_key);

if ( ! isset($_SESSION['id']) ) {
    $_SESSION['login_return'] = $path[0].'/'.$path[1].'/'.$path[2];
    header('Location: '.$CFG->apphome.'/login');
    return;
}

$user_id = $_SESSION['id'];
$user_email = $_SESSION['email'];

$PDOX = LTIX::getConnection();

// Load up the stuff course / link stuff / big inner join

$sql = "SELECT gc_secret, link_key, O.user_id AS owner_id, O.email AS owner_email,
        role, L.path AS path
    FROM {$CFG->dbprefix}lti_context AS C
    JOIN {$CFG->dbprefix}lti_user AS O
        ON C.user_id = O.user_id
    JOIN {$CFG->dbprefix}lti_link AS L
        ON C.context_id = L.context_id
    LEFT JOIN {$CFG->dbprefix}lti_membership AS M
        ON C.context_id = M.context_id AND M.user_id = :UID
    WHERE context_sha256 = :context_sha256 AND context_key = :context_key
    AND link_id = :LID
    LIMIT 1";

$row = $PDOX->rowDie($sql,
    array(
        ':context_sha256' => $context_sha256,
        ':context_key' => $context_key,
        ':UID' => $user_id,
        ':LID' => $link_id )
);

if ( ! $row ) {
    $_SESSION['error'] = 'Could not lookup resource id';
    header('Location: '.$CFG->apphome);
    return;
}

$gc_secret = $row['gc_secret'];
$path = $row['path'];
$owner_id = $row['owner_id'];
$gc_coursework = $row['link_key'];
$owner_email = $row['owner_email'];

// Do some validation...
$plain = $CFG->google_classroom_secret.$gc_course.$owner_id.$CFG->google_classroom_secret;
$user_mini_check = lti_sha256($plain);
$user_mini_check = substr($user_mini_check,0,6);

$plain = $gc_secret.$context_url.$link_id.$gc_secret;
$link_mini_check = lti_sha256($plain);
$link_mini_check = substr($link_mini_check,0,6);

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
$client = getClient($accessTokenStr, $owner_id);
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
echo("<pre>\n");
echo("AT=$access_token \n");

$role = false;
if ( $user_email == $owner_email ) $role = LTIX::ROLE_INSTRUCTOR;

// Check if the current user is a student...
if ( ! $role ) {
    // v1/userProfiles/{userId}
    // https://classroom.googleapis.com/v1/courses/{courseId}/students/{userId}
    $membership_info_url = "https://classroom.googleapis.com/v1/courses/".$gc_course.
        "/students/".$_SESSION['email']."?alt=json&access_token=" .  $access_token;

    $response = \Tsugi\Util\Net::doGet($membership_info_url);
    $membership = json_decode($response);

    /* Not a student:
    object(stdClass)#27 (1) {
    ["error"]=>
        object(stdClass)#26 (3) {
        ["code"]=>
        int(404)
        ["message"]=>
        string(31) "Requested entity was not found."
        ["status"]=>
        string(9) "NOT_FOUND"
        }
    }
    
    Student:
    object(stdClass)#24 (3) {
    ["courseId"]=>
    string(10) "9523923149"
    ["userId"]=>
    string(21) "100516595241762316861"
    ["profile"]=>
    ...
    }
    */

    // If the current user is a student we are golden
    if ( isset($membership->courseId) ) {
        $role = 0;
    } else {
        $_SESSION['error'] = 'You are not enrolled in this class';
        error_log('Classroom connection failed id='.$owner_id);
        error_log($accessTokenStr);
        header('Location: '.$CFG->apphome);
        return;
    }

}

echo("role=$role\n");

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
