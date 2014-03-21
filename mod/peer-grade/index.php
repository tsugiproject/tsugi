<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];
$p = $CFG->dbprefix;

// Model 
$row = loadAssignment($pdo, $LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

// Load up the submission and parts if they exist
$submit_id = false;
$submit_row = loadSubmission($pdo, $assn_id, $LTI['user_id']);
if ( $submit_row !== false ) $submit_id = $submit_row['submit_id'];


// Handle the submission post
if ( $assn_id != false && $assn_json != null && 
    isset($_POST['notes']) && isset($_POST['doSubmit']) ) {
    if ( $submit_row !== false ) {
        $_SESSION['error'] = 'Cannot submit an assignment twice';
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }

    $blob_ids = array();
    $urls = array();
    $partno = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == 'image' ) {
            $fname = 'uploaded_file_'.$partno;
            if( ! isset($_FILES[$fname]) ) {
                $_SESSION['error'] = 'Problem with uploaded files - perhaps too much data was uploaded';
                die( 'Location: '.sessionize('index.php') ) ;
                return;
            }
            $fdes = $_FILES[$fname];
            $blob_id = uploadFileToBlob($pdo, $LTI, $fdes);
            if ( $blob_id === false ) {
                $_SESSION['error'] = 'Problem storing files';
                header( 'Location: '.sessionize('index.php') ) ;
                return;
            }
            $blob_ids[] = $blob_id;
        } else if ( $part->type == 'url' ) {
            $url = $_POST['input_url_'.$partno];
            if ( strpos($url,'http://') === false && strpos($url,'http://') === false ) {
                $_SESSION['error'] = 'URLs must start with http:// or https:// ';
                header( 'Location: '.sessionize('index.php') ) ;
                return;
            }
            $urls[] = $_POST['input_url_'.$partno];
        }
        $partno++;
    }

    $submission = new stdClass();
    $submission->notes = $_POST['notes'];
    $submission->blob_ids = $blob_ids;
    $submission->urls = $urls;
    $json = json_encode($submission);
    $stmt = pdoQuery($pdo,
        "INSERT INTO {$p}peer_submit 
            (assn_id, user_id, json, created_at, updated_at) 
            VALUES ( :AID, :UID, :JSON, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':AID' => $assn_id,
            ':JSON' => $json,
            ':UID' => $LTI['user_id'])
        );
    cacheClear('peer_submit');
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment submitted';
        header( 'Location: '.sessionize('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.sessionize('index.php') ) ;
    }
    return;
}

// Check to see how much grading we have done
$grade_count = 0;
$stmt = pdoQueryDie($pdo,
    "SELECT COUNT(grade_id) AS grade_count 
     FROM {$p}peer_submit AS S JOIN {$p}peer_grade AS G
     ON S.submit_id = G.submit_id
        WHERE S.assn_id = :AID AND G.user_id = :UID",
    array( ':AID' => $assn_id, ':UID' => $LTI['user_id'])
);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row !== false ) {
    $grade_count = $row['grade_count']+0;
}

// See how much grading is left to do
$to_grade = loadUngraded($pdo, $LTI, $assn_id);

// See how many grades I have done
$grade_count = loadMyGradeCount($pdo, $LTI, $assn_id);

// Retrieve our grades...
$our_grades = retrieveSubmissionGrades($pdo, $submit_id);

// Handle the flag...
if ( $assn_id != false && $assn_json != null && is_array($our_grades) &&
    isset($_POST['submit_id']) && isset($_POST['grade_id']) && isset($_POST['note']) && 
    isset($_POST['doFlag']) && $submit_id == $_POST['submit_id'] ) {

    // Make sure we have a valid grade_id
    $found = false;
    foreach ( $our_grades as $grade ) {
        if ( $grade['grade_id'] == $_POST['grade_id'] ) {
            $found = true;
        }
    }
    if ( ! $found ) {
        $_SESSION['error'] = 'Cannot a grade that is not yours';
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }
    
    $grade_id = $_POST['grade_id']+0;
    $stmt = pdoQueryDie($pdo,
        "INSERT INTO {$p}peer_flag 
            (submit_id, grade_id, user_id, note, created_at, updated_at) 
            VALUES ( :SID, :GID, :UID, :NOTE, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':GID' => $grade_id,
            ':UID' => $LTI['user_id'],
            ':NOTE' => $_POST['note'])
    );
    $_SESSION['success'] = "Flagged for the instructor to examine";
    header( 'Location: '.sessionize($url_stay) ) ;
    return;
}

// View 
headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

if ( $instructor ) {
    echo('<p><a href="configure.php">Configure this Assignment</a> | ');
    echo('<a href="admin.php">Explore Grade Data</a> | ');
    echo('<a href="debug.php">Debug Tool</a></p>');
}

if ( $assn_json != null ) {
    echo("<p><b>".$assn_json->title."</b></p>\n");
}

if ( $assn_json == null ) {
    echo('<p>This assignment is not yet configured</p>');
    footerContent();
    return;
} 

if ( $submit_row == false ) {
    echo("<p><b>Please Upload Your Submission:</b></p>\n");
    echo('<p>'.htmlent_utf8($assn_json->description)."</p>\n");
    echo('<form name="myform" enctype="multipart/form-data" method="post" action="'.
         sessionize('index.php').'">');

    $partno = 0;
    foreach ( $assn_json->parts as $part ) {
        echo("\n<p>");
        echo(htmlent_utf8($part->title)."\n");
        if ( $part->type == "image" ) {
            echo('<input name="uploaded_file_'.$partno.'" type="file"> (Please use PNG or JPG files)</p>');
        } else if ( $part->type == "url" ) {
            echo('<input name="input_url_'.$partno.'" type="url" size="80"></p>');
        }
        $partno++;
    }
    echo("<p>Enter optional comments below</p>\n");
    echo('<textarea rows="5" cols="60" name="notes"></textarea><br/>');
    echo('<input type="submit" name="doSubmit" value="Submit"> ');
    doneButton();
    echo('</form>');
    echo("\n<p>Make sure each file is smaller than 1MB.</p>\n");
    footerContent();
    return;
}

if ( count($to_grade) > 0 && ($instructor || $grade_count < $assn_json->maxassess ) ) {
    echo('<p><a href="grade.php">Grade other students</a></p>'."\n");
} else {
    echo('<p>There are no submisions waiting to be graded. Please check back later.</p>');
}

echo("<p> You have graded ".$grade_count." other student submissions.
You must grade at least ".$assn_json->minassess." submissions for full credit on this assignment.
You <i>can</i> grade up to ".$assn_json->maxassess." submissions if you like.</p>\n");

// We have a submission already
$submit_json = json_decode($submit_row['json']);
echo("<p><b>Your Submission:</b></p>\n");
showSubmission($LTI, $assn_json, $submit_json);

if ( count($our_grades) < 1 ) {
    echo("<p>No one has graded your submission yet.</p>");
} else {
    echo("<p>You have the following grades from other students:</p>");
    echo('<table border="1">'."\n<tr><th>Points</th><th>Comments</th><th>Action</th></tr>\n");

    foreach ( $our_grades as $grade ) {
        echo("<tr><td>".$grade['points']."</td><td>".htmlent_utf8($grade['note'])."</td>\n".
        '<td><form><input type="submit" name="showFlag" value="Flag"
        onclick="$(\'#flag_grade_id\').val(\''.$grade['grade_id'].'\'); $(\'#flagform\').toggle(); return false;">'.
        "</form></tr>\n");
    }
    echo("</table>\n");
}
?>
<form method="post" id="flagform" style="display:none">
<p>&nbsp;</p>
<p>Please be considerate when flagging an item.  It does not mean
that something is inappropriate - it simply brings the item to the
attention of the instructor.</p>
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<input type="hidden" value="" id="flag_grade_id" name="grade_id">
<textarea rows="5" cols="60" name="note"></textarea><br/>
<input type="submit" name="doFlag" value="Submit To Instructor">
<input type="submit" name="doCancel" onclick="$('#flagform').toggle(); return false;" value="Cancel Flag">
</form>
<p>
<div id="gradeinfo">Calculating grade....</div>
</p>
<script type="text/javascript">
function loadgrade() {
    window.console && console.log('Loading and updating your grade...');
    $.getJSON('<?php echo(sessionize('update_grade.php')); ?>', function(data) {
        window.console && console.log(data);
        if ( data.grade ) {
            $("#gradeinfo").html('Your current grade is '+data.grade*100.0+'%');
        } else {
            $("#gradeinfo").html('You do not have a grade.');
            window.console && console.log('Take a screen shot of the console output and send to support...');
        }
    });
}
</script>
<?php
footerStart();
?>
<script type="text/javascript">
$(document).ready(function() { 
    loadgrade(); 
} );
</script>
<?php
footerEnd();

