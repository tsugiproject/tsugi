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
$user_id = $_SESSION['id'];

if ( !isset($_SESSION['lti']) ) {
    die_with_error_log('Error: Please log out and back in');
}

if ( !isset($_SESSION['lti']['key_id']) ) {
    die_with_error_log('Error: Session is missing key_id');
}
$key_id = $_SESSION['lti']['key_id'];


if ( ! U::get($_SESSION,'gc_courses') ) {
    die_with_error_log('Error: Must be logged into Google Classroom to make assignments');
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
$accessTokenStr = LTIX::decrypt_secret(U::get($_SESSION,'gc_token'));
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

// Lets talk to Google, get a new copy of courses
$service = new Google_Service_Classroom($client);

// Print the first 100 courses the user has access to.
$optParams = array(
  'pageSize' => 100
);
$courses = $service->courses->listCourses($optParams);

$gc_course = false;
$gc_title = false;
if ( U::get($_GET,'gc_course') ) {
    foreach( $courses as $course ) {
        if ( $course->getId() == $_GET['gc_course'] ) {
            $gc_course = $_GET['gc_course'];
            $gc_title = $course->getName();
            break;
        }
    }
}

// Handle the actual install..
if ( $gc_course ) {
    // Lets talk to Google...
    echo("<pre>\n");
    $plain = $_SESSION['id'].$CFG->google_classroom_secret;
    echo("plain=".$plain."\n");
    $mini_sig = lti_sha256($plain);
    echo("mini_sig=".$mini_sig."\n");
    $mini_sig = substr($mini_sig,0,6);
    echo("mini_sig=".$mini_sig."\n");
    $context_url = $gc_course . ':' . $mini_sig;
    echo("context_url=".$context_url."\n");
    $context_key = 'gclass:' . $context_url;
    echo("context_key=".$context_key."\n");
    $context_sha256 = lti_sha256($context_key);
    echo("context_sha256=".$context_sha256."\n");

    $row = $PDOX->rowDie(
        "SELECT * FROM {$CFG->dbprefix}lti_context
            WHERE context_sha256 = :context LIMIT 1",
        array(':context' => $context_sha256)
    );

    $context_id = false;
    if ( $row != false ) {
        if ( $row['user_id'] != $_SESSION['id'] ) {
            die_with_error_log('Error: Incorrect course ownership');
        }
        $context_id = $row['context_id'];
        if ( $row['title'] != $gc_title ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_context
                SET title = :title WHERE context_id = :CID";
            $PDOX->queryDie($sql,
                array(':title' => $gc_title, ':CID' => $context_id)
            );
        }
    }

    if ( ! $context_id ) {
        $sql = "INSERT INTO {$CFG->dbprefix}lti_context
            ( context_key, context_sha256, title, key_id, user_id, created_at, updated_at ) VALUES
            ( :context_key, :context_sha256, :title, :key_id, :user_id, NOW(), NOW() )";
        $PDOX->queryDie($sql, array(
            ':context_key' => $context_key,
            ':context_sha256' => $context_sha256,
            ':title' => $gc_title,
            ':user_id' => $user_id,
            ':key_id' => $key_id));
        $context_id = $PDOX->lastInsertId();
    }
    
    echo("context_id=$context_id");

    // Set up membership
    $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
        ( context_id, user_id, role, created_at, updated_at ) VALUES
        ( :context_id, :user_id, :role, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()";
    $PDOX->queryDie($sql, array(
        ':context_id' => $context_id,
        ':user_id' => $user_id,
        ':role' => LTIX::ROLE_INSTRUCTOR));

    // Make a new courseWork Item in Classroom
/*
    // https://developers.google.com/classroom/guides/manage-coursework
    $courseWork = json_decode('{  
        "title": "Ant colonies",  
        "description": "Read the article about ant colonies and complete the quiz.",  
        "materials": [  
            {"link": { "url": "http://example.com/ant-colonies" }},  
            {"link": { "url": "http://example.com/ant-quiz" }}  
        ],  
        "workType": "ASSIGNMENT",  
        "state": "PUBLISHED"
        }
    ');
    $courseWorkStr = json_encode($courseWork, JSON_PRETTY_PRINT);
echo($courseWorkStr);
*/
    // courseWork = service.courses().courseWork().create(  
        // courseId='<course ID or alias>', body=courseWork).execute()  
    // print('Assignment created with ID {0}'.format(courseWork.get('id')))

    // https://developers.google.com/resources/api-libraries/documentation/classroom/v1/php/latest/class-Google_Service_Classroom_CourseWork.html
    $materials = array(
        array('link' => 
            array("url" => "http://example.com/ant-colonies")
        ),
        array('link' => 
            array("url" => "http://example.com/ant-quiz")
        )
    );
    $cw = new Google_Service_Classroom_CourseWork();
    $cw->setTitle($lti->title);
    $cw->setMaterials($materials);
    $cw->setWorkType("ASSIGNMENT");
    $cw->setState("PUBLISHED");
    var_dump($cw);

    $courseWorkService = $service->courses_courseWork;
    $courseWorkObject = $courseWorkService->create($gc_course, $cw);
var_dump($courseWorkObject);
    $courseWorkId = $courseWorkObject->id;
    echo("ID=$courseWorkId\n");

die('Yada');
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

