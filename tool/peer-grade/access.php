<?php
require_once "../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$user_id = $LAUNCH->user->id;

// Load the assignment data
$row = loadAssignment();
$assn_json = json_decode(upgradeSubmission($row['json']));
if ( $assn_json == null ) {
    $_SESSION['error'] = 'Assignment not defined yet.';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Model for this user
$row = loadAssignment();
$assn_json = null;
$assn_id = false;
$submit_row = false;
$submit_json = null;
$submit_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}


if ( $assn_id ) {
    $submit_row = loadSubmission($assn_id, $user_id);
    if ( $submit_row !== null ) {
        $submit_id = $submit_row['submit_id'];
        $submit_json = json_decode($submit_row['json']);
    }
}

if ( isset($_POST['screen_reader']) && $submit_json ) {
    $submit_json->peer_exempt = 'yes';

    $sql = "UPDATE {$p}peer_submit SET json = :json
        WHERE submit_id = :id";

    $stmt = $PDOX->queryDie($sql,
        array(':json' => json_encode($submit_json), ':id' => $submit_id)
    );

    $_SESSION['success'] = 'Submission updated.';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View
$OUTPUT->header();
?>
<style>
.hidden
{position:absolute;
left:-10000px;
top:auto;
width:1px;
height:1px;
overflow:hidden;}
</style>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();
?>

<h1>Welcome to Acessibility Options For Peer Grading</h1>
<ul>
<li><a href="index.php">Go back to the main page</a></li>
</ul>
<div class="might-be-hidden">
<p>
If you cannot take the required screen shots for this assignment, download 
the following image and upload it anywhere the assignment is asking for an image
upload.  Then add text comments that show that you fulfilled the assignment.
</p>
<p>
<a href="access.png">
<img src="access.png"
alt="I use a screen reader and cannot produce the images required by this assignment.  Please grade my text comments below.  Thank you."/>
</a>
</p>
<?php if ( ! $submit_id ) { ?>
<p>
Once you have submitted your assignment, if you are not able to grade students submissions come back
to this screen for further instructions.
</p>
<?php } else { ?>
<p>
If you cannot grade images from other students because you use a screen reader or other assistive device,
check the box below and we will exempt you from doing peer grading for this assignment.
<form method="post">
<input type="checkbox" name="screen_reader" value="yes">I use a screen reader and cannot grade my peer's images<br>
<input type="submit" value="Submit">
<input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Go back"></p>
</form>
<?php } ?>
</div>
<?php


$OUTPUT->footer();
