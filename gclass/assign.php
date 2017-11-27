<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Crypt\AesCtr;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../config.php';

require_once "util.php";

$PDOX = LTIX::getConnection();

session_start();

if ( ! U::get($_GET,'rlid') ) {
    die_with_error_log('Error: rlid parameter is required');
}

if ( ! sanity_check() ) return;

$user_id = $_SESSION['id'];
$key_id = $_SESSION['lti']['key_id'];
$courses = $_SESSION['gc_courses'];

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
$accessTokenStr = retrieve_existing_token();
if ( ! $accessTokenStr ) {
    die_with_error_log('Error: Access Token not in session');
}

// Get the API client and construct the service object.
$client = getClient($accessTokenStr);

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
    // We use the global Google Classsoom secret because we need to 
    // be able to re-lookup an existing course
    // secret:$gc_course:$user_id:secret
    $plain = $CFG->google_classroom_secret.$gc_course.$_SESSION['id'].$CFG->google_classroom_secret;
    echo("plain=".$plain."\n");
    $user_mini_sig = lti_sha256($plain);
    echo("user_mini_sig=".$user_mini_sig."\n");
    $user_mini_sig = substr($user_mini_sig,0,6);
    echo("user_mini_sig=".$user_mini_sig."\n");
    $context_url = $gc_course . ':' . $user_mini_sig;
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
    $gc_secret = false;
    if ( $row != false ) {
        if ( $row['user_id'] != $_SESSION['id'] ) {
            die_with_error_log('Error: Incorrect course ownership');
        }
        $context_id = $row['context_id'];
        $gc_secret = $row['gc_secret'];
        if ( $row['title'] != $gc_title ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_context
                SET title = :title updated_at=NOW() WHERE context_id = :CID";
            $PDOX->queryDie($sql,
                array(':title' => $gc_title, ':CID' => $context_id)
            );
        }
    }

    if ( ! $context_id ) {
        $gc_secret = bin2hex( openssl_random_pseudo_bytes( 128/2 ) ) ;
        $sql = "INSERT INTO {$CFG->dbprefix}lti_context
            ( context_key, context_sha256, title, key_id, gc_secret, user_id, created_at, updated_at )
            VALUES
            ( :context_key, :context_sha256, :title, :key_id, :GCS, :user_id, NOW(), NOW() )";
        $PDOX->queryDie($sql, array(
            ':context_key' => $context_key,
            ':context_sha256' => $context_sha256,
            ':title' => $gc_title,
            ':GCS' => $gc_secret,
            ':user_id' => $user_id,
            ':key_id' => $key_id));
        $context_id = $PDOX->lastInsertId();
    }

    echo("context_id=$context_id\n");

    // Set up membership
    $sql = "INSERT INTO {$CFG->dbprefix}lti_membership
        ( context_id, user_id, role, created_at, updated_at ) VALUES
        ( :context_id, :user_id, :role, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE role=:role, updated_at=NOW()";
    $PDOX->queryDie($sql, array(
        ':context_id' => $context_id,
        ':user_id' => $user_id,
        ':role' => LTIX::ROLE_INSTRUCTOR));

    // Insert a dumy resource link so we know the primary key of the link
    $resource_link_id_tmp = uniqid();
    $endpoint = U::add_url_parm($lti->launch, 'inherit', $lti->resource_link_id);
    $sql = "INSERT INTO {$CFG->dbprefix}lti_link
        ( link_key, link_sha256, title, context_id, path, created_at, updated_at ) VALUES
            ( :link_key, :link_sha256, :title, :context_id, :path, NOW(), NOW() )";
    $PDOX->queryDie($sql, array(
        ':link_key' => $resource_link_id_tmp,
        ':link_sha256' => lti_sha256($resource_link_id_tmp),
        ':title' => $lti->title,
        ':context_id' => $context_id,
        ':path' => $endpoint
    ));
    $link_id = $PDOX->lastInsertId();

    // We use the per-course secret to sign the whole thing
    // secret:gc_course:mini-sig-user:link_id:secret
    $plain = $gc_secret.$context_url.$link_id.$gc_secret;
    echo("plain=".$plain."\n");
    $link_mini_sig = lti_sha256($plain);
    echo("link_mini_sig=".$link_mini_sig."\n");
    $link_mini_sig = substr($link_mini_sig,0,6);

    $launch_url = $CFG->wwwroot . '/gclass/launch/' .
        $context_url . ':' . $link_id . ':' . $link_mini_sig;

    echo("Launch=$launch_url\n");

    // https://developers.google.com/classroom/guides/manage-coursework
    // https://developers.google.com/resources/api-libraries/documentation/classroom/v1/php/latest/class-Google_Service_Classroom_CourseWork.html
    $link = new Google_Service_Classroom_Link();
    $link->setTitle($lti->title);
    $link->setUrl($launch_url);
    $link->setThumbnailUrl($CFG->apphome.'/logo.png');
    $materials = new Google_Service_Classroom_Material();
    $materials->setLink($link);
    // var_dump($materials);

    $cw = new Google_Service_Classroom_CourseWork();
    $cw->setTitle($lti->title);
    $cw->setMaterials($materials);
    $cw->setWorkType("ASSIGNMENT");
    $cw->setState("PUBLISHED");
    // var_dump($cw);

    $courseWorkService = $service->courses_courseWork;
    $courseWorkObject = $courseWorkService->create($gc_course, $cw);
    $resource_link_id = $courseWorkObject->id;
    echo("ID=$resource_link_id\n");

    // Now really fix the link with the real resource_link_id
    $sql = "UPDATE {$CFG->dbprefix}lti_link
        SET link_key = :link_key, link_sha256=:link_sha256 WHERE link_id = :LID";
    $PDOX->queryDie($sql, array(
        ':link_key' => $resource_link_id,
        ':link_sha256' => lti_sha256($resource_link_id),
        ':LID' => $link_id
    ));

    echo("Success\n");
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

