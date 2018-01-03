<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Google\GoogleClassroom;

require_once("util.php");

$request_headers = apache_request_headers();
$agent = U::get($request_headers,'User-Agent');
if ( isset($CFG->logo_url) && $agent && stripos($agent,'Google Web Preview') !== false ) {
    echo('<center><img src="'.$CFG->logo_url.'"></center>'."\n");
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
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$user_id = $_SESSION['id'];
$user_email = $_SESSION['email'];
$user_displayname = $_SESSION['displayname'];
$user_key = $_SESSION['user_key'];
$user_avatar = U::get($_SESSION,'avatar', null);

$PDOX = LTIX::getConnection();

// Load up the stuff course / link stuff / big inner join

$sql = "SELECT gc_secret, O.user_id AS owner_id, O.email AS owner_email,
        role, role_override,
        C.context_id AS context_id, C.title AS context_title,
        L.path AS path, link_key, L.link_id as link_id, L.title AS link_title,
        result_id, gc_submit_id,
        K.key_id AS key_id, K.secret AS key_secret, K.key_key AS key_key
    FROM {$CFG->dbprefix}lti_context AS C
    JOIN {$CFG->dbprefix}lti_user AS O
        ON C.user_id = O.user_id
    JOIN {$CFG->dbprefix}lti_link AS L
        ON C.context_id = L.context_id
    JOIN {$CFG->dbprefix}lti_key AS K
        ON C.key_id = K.key_id
    LEFT JOIN {$CFG->dbprefix}lti_result AS R
        ON L.link_id = R.link_id AND R.user_id = :UID
    LEFT JOIN {$CFG->dbprefix}lti_membership AS M
        ON C.context_id = M.context_id AND M.user_id = :UID
    WHERE context_sha256 = :context_sha256 AND context_key = :context_key
    AND L.link_id = :LID
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
$context_id = $row['context_id'];
$context_title = $row['context_title'];
$key_id = $row['key_id'];
$key_key = $row['key_key'];
$key_secret = $row['key_key'];
$gc_submit_id = $row['gc_submit_id'];
$result_id = $row['result_id'];
$link_title = $row['link_title'];
$role = $row['role'];
if ( $row['role_override'] > $row['role'] ) {
    $role = $row['role_override'];
}

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

// Try access token from session when LTIX adds it.
$accessTokenStr = GoogleClassroom::retrieve_instructor_token($owner_id);
if ( ! $accessTokenStr ) {
    $_SESSION['error'] = 'Classroom connection not set up, see your instructor';
    header('Location: '.$CFG->apphome);
    return;
}

// Get the API client and construct the service object.
$client = GoogleClassroom::getClient($accessTokenStr, $owner_id);
if ( ! $client ) {
    $_SESSION['error'] = 'Classroom connection failed';
    error_log('Classroom connection failed id='.$owner_id);
    error_log($accessTokenStr);
    header('Location: '.$CFG->apphome);
    return;
}

// Lets talk to Google
$service = new Google_Service_Classroom($client);

// If we don't have a membership record...
if ( $role == null ) {
    if ( $user_email == $owner_email ) $role = LTIX::ROLE_INSTRUCTOR;

    // Check if the current user is a student...
    if ( ! $role ) {
        // TODO: Look into using the APIs to do this.
        $access_token_data = $client->getAccessToken();
        $access_token = $access_token_data['access_token'];

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

        $student_id = false;

        // If the current user is a student we are golden
        if ( isset($membership->courseId) ) {
            $role = LTIX::ROLE_LEARNER;
        } else {
            $_SESSION['error'] = 'You are not enrolled in this class';
            error_log('Classroom connection failed id='.$owner_id);
            error_log($accessTokenStr);
            header('Location: '.$CFG->apphome);
            return;
        }

        if ( isset($membership->userId) ) {
            $student_id = $membership->userId;
        } else {
            $_SESSION['error'] = 'You are do not have a studentId in this class';
            error_log('Classroom connection failed id='.$owner_id);
            error_log($accessTokenStr);
            header('Location: '.$CFG->apphome);
            return;
        }

        // Insert the membership record
        $json = new \stdClass();
        $json->student_id = $student_id;
        $json = json_encode($json);

        $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
            ( context_id, user_id, role, json, created_at, updated_at ) VALUES
            ( :context_id, :user_id, :role, :json, NOW(), NOW() )
            ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()";
        $PDOX->queryDie($sql, array(
            ':context_id' => $context_id,
            ':user_id' => $user_id,
            ':json' => $json,
            ':role' => $role));
    }
}

// Make sure we have a submit_id in the database
if ( $gc_submit_id === null ) {
    try {
        // Get the student's submission...
        $studentSubmissions = $service->courses_courseWork_studentSubmissions;

        // Submissions associated with this courseWork for this student
        $retval = $studentSubmissions->listCoursesCourseWorkStudentSubmissions(
            $gc_course, $gc_coursework, array('userId' => $student_id));
        // var_dump($retval);

        // Grab the first submission
        $submissions = $retval->studentSubmissions;
        $first = $submissions[0];
        // var_dump($first);
        $gc_submit_id = $first->id;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Could not retrieve submission for this assignment.';
        error_log('Could not retrieve submission for this assignment id='.$owner_id);
        error_log($accessTokenStr);
        header('Location: '.$CFG->apphome);
        return;
    }
}

// Insert the result record if necessary
if ( $result_id == null || $row['gc_submit_id'] === null ) {
    $sql = "INSERT INTO {$CFG->dbprefix}lti_result
        ( link_id, user_id, gc_submit_id, created_at, updated_at ) VALUES
        ( :link_id, :user_id, :GCS, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE gc_submit_id=:GCS, updated_at=NOW()";
    $PDOX->queryDie($sql, array(
        ':link_id' => $link_id,
        ':user_id' => $user_id,
        ':GCS' => $gc_submit_id));
    $result_id = $PDOX->lastInsertId();
    error_log('New student='.$user_id.' context='.$context_id.' owner='.$user_email);
}

// Set up the LTI launch information

$lti = array();
$lti['key_id'] = $key_id;
$lti['key_key'] = $key_key;

if ( strlen($key_secret) ) {
    $lti['secret'] = LTIX::encrypt_secret($key_secret);
}

$lti['user_id'] = $user_id;
$lti['user_key'] = $user_key;
$lti['user_email'] = $user_email;
$lti['user_displayname'] = $user_displayname;
if ( strlen($user_avatar) ) {
    $lti['user_image'] = $user_avatar;
}
$lti['role'] = $role;

$lti['context_id'] = $context_id;
$lti['context_key'] = $context_key;
$lti['context_title'] = $context_title;

$lti['link_id'] = $link_id;
$lti['link_title'] = $link_title;

$lti['result_id'] = $result_id;

// Grades will do this:
// $retval = $studentSubmissions->patch($gc_course, $gc_coursework, $gc_submit_id, $sub, $opt);
$lti['gc_owner_id'] = $owner_id;
$lti['gc_course'] = $gc_course;
$lti['gc_coursework'] = $gc_coursework;
$lti['gc_submit_id'] = $gc_submit_id;

// Set that data in the session.
$_SESSION['lti'] = $lti;

$launch = U::add_url_parm($path, 'PHPSESSID', session_id());

if ( ! U::get($_GET,'debug') ) {
    header('Location: '.$launch);
    return;
}
?>
<a href="<?= $launch ?>" target="_blank"><?= $launch ?></a>
<pre>
LTI:
<?php
print_r($lti);
?>
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
