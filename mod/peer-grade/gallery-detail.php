<?php
require_once "../../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Result;
use \Tsugi\Grades\GradeUtil;

// Sanity checks
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( !isset($_REQUEST['user_id']) ) {
    die('user_id required');
}
$user_id = $_REQUEST['user_id'];

$url_goback = 'gallery.php';
$url_stay = 'gallery-detail.php';

// Model
$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    $_SESSION['error'] = 'This assignment is not yet set up';
    header( 'Location: '.addSession($url_goback) ) ;
    return;
}

if ( $assn_json->gallery == 'off' ) {
    die('Gallery not enabled for assignment');
}


$submit_id = false;
$submit_json = null;

$submit_row = loadSubmission($assn_id, $user_id);
if ( $submit_row !== null ) {
    $submit_id = $submit_row['submit_id'];
    $submit_json = json_decode($submit_row['json']);
}

if ( $submit_json === null ) {
    $_SESSION['error'] = 'Unable to load submission '.$user_id;
    header( 'Location: '.addSession($url_goback) ) ;
    return;
}

// Load the previous rating data
$row = $PDOX->rowDie("
    SELECT rating FROM {$p}peer_grade
    WHERE submit_id = :SID AND user_id = :UID",
    array( ":SID" => $submit_id,
        ":UID" => $USER->id)
);
if ( $row === FALSE ) {
    $previous_rating = 0;
} else {
    $previous_rating = $row['rating'];
    if ( $previous_rating < 1 ) $previous_rating = 0;
}

// Handle the flag data
if ( isset($_POST['doFlag']) && isset($_POST['submit_id']) ) {

    $submit_id = $_POST['submit_id']+0;
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$p}peer_flag
            (submit_id, user_id, note, created_at, updated_at)
            VALUES ( :SID, :UID, :NOTE, NOW(), NOW())
            ON DUPLICATE KEY UPDATE note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $USER->id,
            ':NOTE' => $_POST['note'])
    );
    $_SESSION['success'] = "Flagged for the instructor to examine, please continue grading.";
    header( 'Location: '.addSession($url_stay.'?user_id='.$user_id) ) ;
    return;
}


// Handle the rating data
if ( isset($_POST['rating']) && isset($_POST['submit_id'])
    && isset($_POST['user_id']) ) {

    $session_peer_submit_id = isset($_SESSION['peer_submit_id']) ? $_SESSION['peer_submit_id'] : false;
    unset($_SESSION['peer_submit_id']);

    if ( $assn_json->rating < 1 ) {
        $_SESSION['error'] = 'Rating is not enabled on this assignment';
        header( 'Location: '.addSession($url_stay.'?user_id='.$user_id) ) ;
        return;
    }

    if ( $session_peer_submit_id != $_POST['submit_id'] ) {

        $_SESSION['error'] = 'Error in submission id';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    if ( strlen($_POST['rating']) < 1 ) {
        $_SESSION['error'] = 'Rating is required';
        header( 'Location: '.addSession($url_stay.'?user_id='.$user_id) ) ;
        return;
    }

    $rating = $_POST['rating']+0;
    if ( $rating < 0 || $rating > $assn_json->rating ) {
        $_SESSION['error'] = 'Rating must be between 0 and '.$assn_json->rating;
        header( 'Location: '.addSession($url_stay.'?user_id='.$user_id) ) ;
        return;
    }

    // Check to see if user_id is correct for this submit_id
    $user_id = $_POST['user_id']+0;
    $submit_row = loadSubmission($assn_id, $user_id);
    if ( $submit_row === null || $submit_row['submit_id'] != $_POST['submit_id']) {
        $_SESSION['error'] = 'Mis-match between user_id and session_id';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    // Add / update the rating for the logged in user
    $stmt = $PDOX->queryReturnError(
        "INSERT INTO {$p}peer_grade
            (submit_id, user_id, rating, created_at, updated_at)
            VALUES ( :SID, :UID, :RATING, NOW(), NOW())
            ON DUPLICATE KEY UPDATE rating = :RATING, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $USER->id,
            ':RATING' => $rating)
    );

    if ( ! $stmt->success ) {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    // Update the subission's overall rating
    $stmt = $PDOX->queryDie(
        "UPDATE {$p}peer_submit
            SET rating = IF(rating IS NULL , :RATING, (rating + :DELTA) )
         WHERE submit_id = :SID", 
        array(
            ':SID' => $submit_id,
            ':RATING' => $rating,
            ':DELTA' => ($rating - $previous_rating)
        )
    );

    header( 'Location: '.addSession($url_goback) ) ;
    return;
}

// View
$OUTPUT->header();
?>
<link href="<?php echo($OUTPUT::getLocalStatic(__FILE__)); ?>/static/prism.css" rel="stylesheet"/>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

echo('<div style="border: 1px solid black; padding:3px">');
echo("<p><h4>".$assn_json->title."</h4></p>\n");
echo('<p>'.htmlent_utf8($assn_json->description)."</p>\n");
echo('</div>');
showSubmission($assn_json, $submit_json, $assn_id, $user_id);
echo('<p>'.htmlent_utf8($assn_json->grading)."</p>\n");
?>
<form method="post">
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<?php if ( $assn_json->rating > 0 ) { ?>
<p>
Rate this submission: <span id="jRate"></span>
<input type="hidden" min="0" max="<?php echo($assn_json->rating); ?>" name="rating"
id="rating-input"
value="<?= ($previous_rating > 0 ? $previous_rating : '') ?>" >
</p>
<input type="submit" value="Rate" class="btn btn-primary">
<?php } ?>
<input type="submit" name="doCancel" onclick="location='<?php echo(addSession($url_goback));?>'; return false;" value="Back" class="btn btn-default">
<?php   if ( $assn_json->flag ) { ?>
<input type="submit" name="showFlag" onclick="$('#flagform').toggle(); return false;" value="Flag Content Or Technical Issue" class="btn btn-warning" style="float:right;">
<?php } ?>
</form>
<?php   if ( $assn_json->flag ) { ?>
<form method="post" id="flagform" style="display:none">
<p>Please be considerate when flagging an item.  Only use
flagging when instructor attention is needed.</p>
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<input type="hidden" value="1" name="doFlag">
<textarea rows="5" cols="60" name="note"></textarea><br/>
<input type="submit" name="flagSubmit"
    onclick="return confirm('Are you sure you want to bring this student submission to the attention of the instructor?');"
    value="Submit To Instructor" class="btn btn-primary">
<input type="submit" name="doCancel" onclick="$('#flagform').toggle(); return false;" value="Cancel Flag" class="btn btn-default">
</form>
<?php } ?>
<?php
if ( $USER->instructor ) {
    echo('<br/>Admin Info: ');
    echo('<a href="rate-detail.php?user_id='.$user_id.'">Detail</a>');
}

$_SESSION['peer_submit_id'] = $submit_id;  // Our CSRF touch

$OUTPUT->footerStart();
?>
<script src="<?= $OUTPUT::getLocalStatic(__FILE__) ?>/static/prism.js" type="text/javascript"></script>
<?php if ( $assn_json->rating > 0 ) { ?>
<script src="<?= $CFG->staticroot ?>/static/js/jRate.js" type="text/javascript"></script>
<script>
$("#jRate").jRate(
    {
        startColor: 'cyan',
        endColor: 'blue',
        width: 25,
        height: 25,
        strokeWidth: '10px',
        min: 0,
        max: 5,
        max: <?= $assn_json->rating ?>,
        precision: 1,
        count: <?= $assn_json->rating ?>,
        rating: <?= ($previous_rating > 0 ? $previous_rating : '0') ?>,
        onChange: function(rating) {
            $('#rating-input').val(rating);
        }
    }
);
</script>
<?php } ?>
<?php
$OUTPUT->footerEnd();
