<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Crypt\AesCtr;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../config.php';

$PDOX = LTIX::getConnection();

session_start();

define('APPLICATION_NAME', $CFG->servicedesc);
define('SCOPES', implode(' ', array(
  Google_Service_Classroom::CLASSROOM_COURSES_READONLY,
  Google_Service_Classroom::CLASSROOM_ROSTERS_READONLY,
  Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS,
  Google_Service_Classroom::CLASSROOM_PROFILE_PHOTOS,
  Google_Service_Classroom::CLASSROOM_COURSEWORK_STUDENTS)
));

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient($accessTokenStr) {
    global $CFG;
    $options = array(
        'client_id' => $CFG->google_client_id,
        'client_secret' => $CFG->google_client_secret, 
        'redirect_uri' => $CFG->wwwroot . '/gclass/login'
    );
    $client = new Google_Client($options);
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAccessType('offline');

    $accessToken = false;
    // Are we coming back from an authorization?
    if ( U::get($_GET,'code') ) {
        $authCode = $_GET['code'];
        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    } else if ( $accessTokenStr ) {
        $accessToken = json_decode($accessTokenStr, true);
    }

    if ( $accessToken ) {
        $client->setAccessToken($accessToken);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        header('Location: '.filter_var($authUrl, FILTER_SANITIZE_URL));
        return;
    }

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    }

    return $client;
}

if ( ! U::get($_SESSION,'id') ) {
    die_with_error_log('Error: Must be logged in to use Google Classroom');
}

if ( ! U::get($_SESSION,'gc_courses') ) {
    die_with_error_log('Error: Must be logged in to use Google Classroom');
}

$courses = $_SESSION['gc_courses'];

if ( ! U::get($_GET,'rlid') ) {
    die_with_error_log('Error: rlid parameter is required');
}

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

/* object(stdClass)#25 (3) {
  ["title"]=>
  string(28) "Quiz: Request-Response Cycle"
  ["launch"]=>
  string(65) "http://localhost:8888/wa4e/mod/gift/?quiz=01-Request-Response.txt"
  ["resource_link_id"]=>
  string(15) "php_01_rrc_quiz"
} */
$lti = $l->getLtiByRlid($_GET['rlid']);
if ( ! $lti ) {
    die_with_error_log('Invalid resource link id');
}

// Try access token from session when LTIX adds it.
$accessTokenStr = LTIX::decrypt_secret(LTIX::ltiParameter('gc_token', false));
if ( ! $accessTokenStr ) {
    die_with_error_log('Error: Access Token not in session');
}

// Get the API client and construct the service object.
$client = getClient($accessTokenStr);

// Check if we need to update/store the access token
$newAccessTokenStr = json_encode($client->getAccessToken());
if ( $newAccessTokenStr != $accessTokenStr ) {
    $sql = "UPDATE {$CFG->dbprefix}lti_user
        SET gc_token = :TOK WHERE user_id = :UID";
    $PDOX->queryDie($sql,
        array(':UID' => $_SESSION['id'], ':TOK' => $newAccessTokenStr)
    );
    if ( U::get($_SESSION,'lti') ) {
        $_SESSION['lti']['gc_token'] = LTIX::encrypt_secret($newAccessTokenStr);
    }
    error_log('Token updated user_id='.$_SESSION[ 'id'].' token='.$newAccessTokenStr);
}

$gc_course = false;
if ( U::get($_GET,'gc_course') ) {
    foreach( $courses as $course ) {
        if ( $course->getId() == $_GET['gc_course'] ) {
            $gc_course = $_GET['gc_course'];
            break;
        }
    }
}

// Handle the actual install..
if ( $gc_course ) {
    // Lets talk to Google...
    echo("<pre>\n");
    $plain = $gc_course.'::'.$lti->resource_link_id.'::'.$_SESSION['id'];
    echo("plain1=".$plain."\n");
    $encr = AesCtr::encrypt($plain, $CFG->google_classroom_secret, 256);
    echo("aes=".$encr."\n");
    $launch = U::add_url_parm($CFG->wwwroot.'/tsugi/gclass/launch/','resource',$encr);
    echo("launch=".$launch);
    
    
    die('YADA');
    $service = new Google_Service_Classroom($client);

    header('Location: '.filter_var($CFG->apphome.'/lessons?nostyle=yes',FILTER_SANITIZE_URL));
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
?>
<center>
Please select a course
<form>
<input type="hidden" name="rlid" value="<?= htmlentities($_GET['rlid']) ?>"/>
<select name="gc_course">
<option value="">Please Select a Course</option>
<?php
foreach( $courses as $course ) {
    echo('<option value="'.htmlentities($course->getId()).'">'.htmlentities($course->getName())."</option>\n");
}
?>
</select>
<br/>
<input type="submit" value="Submit">
</form>
</center>
<?php
$OUTPUT->footer();

