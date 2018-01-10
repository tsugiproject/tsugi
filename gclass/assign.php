<?php
use \Tsugi\Util\U;
use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Google\GoogleClassroom;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../config.php';

require_once "util.php";

$PDOX = LTIX::getConnection();

session_start();

if ( ! sanity_check() ) return;

// If this is a direct-to tool launch.
$endpoint = U::get($_GET,'lti');
$endpoint_title = U::get($_GET, 'title', 'External Tool');
if ( ! $endpoint ) {
    if ( ! U::get($_GET,'rlid') ) {
        die_with_error_log('Error: rlid parameter is required');
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
    $endpoint = U::add_url_parm($lti->launch, 'inherit', $lti->resource_link_id);
    $endpoint_title = $lti->title;
}

$user_id = $_SESSION['id'];
$key_id = $_SESSION['lti']['key_id'];

// Try access token from session when LTIX adds it.
$accessTokenStr = GoogleClassroom::retrieve_instructor_token();
if ( ! $accessTokenStr ) {
    die_with_error_log('Error: Access Token not in session');
}

// Get the API client and construct the service object.
$client = GoogleClassroom::getClient($accessTokenStr);

// Lets talk to Google, get a new copy of courses
$service = new Google_Service_Classroom($client);

// Print the first 100 courses the user has access to.
$optParams = array(
  'pageSize' => 100
);
$courses = $service->courses->listCourses($optParams);
// echo("<pre>\n");var_dump($courses);echo("</pre>\n");

$gc_course = false;
$gc_title = false;
$gc_url = false;
if ( U::get($_GET,'gc_course') ) {
    foreach( $courses as $course ) {
        if ( $course->getId() == $_GET['gc_course'] ) {
            $gc_course = $_GET['gc_course'];
            $gc_title = $course->getName();
            $gc_url = $course->getAlternateLink();
            break;
        }
    }
}

// Handle the actual install..
if ( $gc_course ) {
    // Lets talk to Google...
    // We use the global Google Classsoom secret because we need to 
    // be able to re-lookup an existing course
    // secret:$gc_course:$user_id:secret
    $plain = $CFG->google_classroom_secret.$gc_course.$_SESSION['id'].$CFG->google_classroom_secret;
    $user_mini_sig = lti_sha256($plain);
    $user_mini_sig = substr($user_mini_sig,0,6);
    $context_url = $gc_course . ':' . $user_mini_sig;
    $context_key = 'gclass:' . $context_url;
    $context_sha256 = lti_sha256($context_key);

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
    $sql = "INSERT INTO {$CFG->dbprefix}lti_link
        ( link_key, link_sha256, title, context_id, path, created_at, updated_at ) VALUES
            ( :link_key, :link_sha256, :title, :context_id, :path, NOW(), NOW() )";
    $PDOX->queryDie($sql, array(
        ':link_key' => $resource_link_id_tmp,
        ':link_sha256' => lti_sha256($resource_link_id_tmp),
        ':title' => $endpoint_title,
        ':context_id' => $context_id,
        ':path' => $endpoint
    ));
    $link_id = $PDOX->lastInsertId();

    // We use the per-course secret to sign the whole thing
    // secret:gc_course:mini-sig-user:link_id:secret
    $plain = $gc_secret.$context_url.$link_id.$gc_secret;
    $link_mini_sig = lti_sha256($plain);
    $link_mini_sig = substr($link_mini_sig,0,6);

    $launch_url = $CFG->wwwroot . '/gclass/launch/' .
    $context_url . ':' . $link_id . ':' . $link_mini_sig;

    // https://developers.google.com/classroom/guides/manage-coursework
    // https://developers.google.com/resources/api-libraries/documentation/classroom/v1/php/latest/class-Google_Service_Classroom_CourseWork.html
    $link = new Google_Service_Classroom_Link();
    $link->setTitle($endpoint_title);
    $link->setUrl($launch_url);
    if ( isset($CFG->logo_url) ) {
        $link->setThumbnailUrl($CFG->logo_url);
    }
    $materials = new Google_Service_Classroom_Material();
    $materials->setLink($link);
    // var_dump($materials);

    $cw = new Google_Service_Classroom_CourseWork();
    $cw->setTitle($endpoint_title);
    $cw->setMaterials($materials);
    $cw->setMaxpoints(100);
    $cw->setWorkType("ASSIGNMENT");
    $cw->setState("PUBLISHED");
    // var_dump($cw);

    $courseWorkService = $service->courses_courseWork;
    $courseWorkObject = $courseWorkService->create($gc_course, $cw);
    $resource_link_id = $courseWorkObject->id;

    // Now really fix the link with the real resource_link_id
    $sql = "UPDATE {$CFG->dbprefix}lti_link
        SET link_key = :link_key, link_sha256=:link_sha256 WHERE link_id = :LID";
    $PDOX->queryDie($sql, array(
        ':link_key' => $resource_link_id,
        ':link_sha256' => lti_sha256($resource_link_id),
        ':LID' => $link_id
    ));

    $OUTPUT->header();
    $OUTPUT->bodyStart();
    echo("<center><p>\n");
    echo(_m('Success installing').'<br/>');
    echo('<strong>');
    echo(htmlentities($endpoint_title));
    echo('</strong>');
    if ( $gc_title ) {
        echo('<br/>'._m('in').' ');
        echo(htmlentities($gc_title));
    }
    echo("</p>\n");
    if ( $gc_url ) {
        $launch = filter_var($gc_url, FILTER_SANITIZE_URL); 
        echo("<p><a href=".$launch.' target=_blank">');
        echo(_m('Go to Classroom site'));
        echo("</p>\n");
    } 
    $OUTPUT->footer();
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
?>
<center>
<p>
Installing assignment in Google Classroom.
</p>
<form>
<input type="hidden" name="rlid" value="<?= htmlentities(U::get($_GET,'rlid') ) ?>"/>
<input type="hidden" name="lti" value="<?= htmlentities(U::get($_GET,'lti') ) ?>"/>
<input type="hidden" name="title" value="<?= htmlentities(U::get($_GET,'title') ) ?>"/>
<p>
<select name="gc_course">
<option value="">Please Select a Course</option>
<?php
foreach( $courses as $course ) {
    echo('<option value="'.htmlentities($course->getId()).'">'.htmlentities($course->getName())."</option>\n");
}
?>
</select>
</p>
<input type="submit" value="Install in Classroom">
</form>
</center>
<?php
$OUTPUT->footer();

