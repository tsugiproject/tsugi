<?php
require_once "../../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\Core\Cache;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($_POST) < 1 ) {
    $_SESSION['error'] = 'File upload size exceeded, please re-upload a smaller file';
    error_log("Upload size exceeded");
    header('Location: '.addSession('index.php'));
    return;
}

// Model
$row = loadAssignment($LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false && strlen($row['json']) > 0 ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

// Load up the submission and parts if they exist
$submit_id = false;
$submit_row = loadSubmission($assn_id, $USER->id);
if ( $submit_row !== false ) $submit_id = $submit_row['submit_id'];

// Handle the submission post
if ( $assn_id != false && $assn_json != null &&
    isset($_POST['notes']) && isset($_POST['doSubmit']) ) {
    if ( $submit_row !== false ) {
        $_SESSION['error'] = 'Cannot submit an assignment twice';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    // Check all files to be within our size limit
    foreach($_FILES as $fdes) {
        if ( $fdes['size'] > 1024*1024 ) {
            $_SESSION['error'] = 'Error - '.$fdes['name'].' has a size of '.$fdes['size'].' (1M max size per file)';
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
    }

    $blob_ids = array();
    $urls = array();
    $code_ids = array();
    $partno = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == 'image' ) {
            $fname = 'uploaded_file_'.$partno;
            if( ! isset($_FILES[$fname]) ) {
                $_SESSION['error'] = 'Problem with uploaded files - perhaps your files were too large';
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }

            $fdes = $_FILES[$fname];
            $filename = isset($fdes['name']) ? basename($fdes['name']) : false;

            // Check to see if they left off a file
            if( $fdes['error'] == 4) {
                $_SESSION['error'] = 'Missing file, make sure to select all files before pressing submit';
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }

            // Sanity-check the file
            $safety = BlobUtil::checkFileSafety($fdes);
            if ( $safety !== true ) {
                $_SESSION['error'] = "Error: ".$safety;
                error_log("Upload Error: ".$safety);
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }

            // Check the kind of file
            if ( ! BlobUtil::isPngOrJpeg($fdes) ) {
                $_SESSION['error'] = 'Files must either contain JPG, or PNG images: '.$filename;
                error_log("Upload Error - Not an Image: ".$filename);
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }

            $blob_id = BlobUtil::uploadFileToBlob($fdes);
            if ( $blob_id === false ) {
                $_SESSION['error'] = 'Problem storing file in server: '.$filename;
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }
            $blob_ids[] = $blob_id;
        } else if ( $part->type == 'url' ) {
            $url = $_POST['input_url_'.$partno];
            if ( strpos($url,'http://') === false && strpos($url,'http://') === false ) {
                $_SESSION['error'] = 'URLs must start with http:// or https:// ';
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }
            $urls[] = $_POST['input_url_'.$partno];
        } else if ( $part->type == 'code' ) {
            $code = $_POST['input_code_'.$partno];
            if( strlen($code) < 1 ) {
                $_SESSION['error'] = 'Missing: '.$part->title;
                header( 'Location: '.addSession('index.php') ) ;
                return;
            }
            $PDOX->queryDie("
                INSERT INTO {$p}peer_text
                    (assn_id, user_id, data, created_at, updated_at)
                    VALUES ( :AID, :UID, :DATA, NOW(), NOW() )",
                array(
                    ':AID' => $assn_id,
                    ':DATA' => $code,
                    ':UID' => $USER->id)
            );
            $code_ids[] = $PDOX->lastInsertId();
        }
        $partno++;
    }

    $submission = new stdClass();
    $submission->notes = $_POST['notes'];
    $submission->blob_ids = $blob_ids;
    $submission->urls = $urls;
    $submission->codes = $code_ids;
    $json = json_encode($submission);
    $stmt = $PDOX->queryReturnError(
        "INSERT INTO {$p}peer_submit
            (assn_id, user_id, json, created_at, updated_at)
            VALUES ( :AID, :UID, :JSON, NOW(), NOW())
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':AID' => $assn_id,
            ':JSON' => $json,
            ':UID' => $USER->id)
        );
    Cache::clear('peer_submit');
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment submitted';
        header( 'Location: '.addSession('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.addSession('index.php') ) ;
    }
    return;
}

// Check to see how much grading we have done
$grade_count = 0;
$to_grade = 0;
if ( $assn_json && $assn_json->maxassess > 0 ) {
    // See how much grading is left to do
    $to_grade = loadUngraded($LTI, $assn_id);

    // See how many grades I have done
    $grade_count = loadMyGradeCount($LTI, $assn_id);
}

// Retrieve our grades...
$our_grades = retrieveSubmissionGrades($submit_id);

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
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $grade_id = $_POST['grade_id']+0;
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$p}peer_flag
            (submit_id, grade_id, user_id, note, created_at, updated_at)
            VALUES ( :SID, :GID, :UID, :NOTE, NOW(), NOW())
            ON DUPLICATE KEY UPDATE note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':GID' => $grade_id,
            ':UID' => $USER->id,
            ':NOTE' => $_POST['note'])
    );
    $_SESSION['success'] = "Flagged for the instructor to examine";
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View
$OUTPUT->header();
?>
<link href="<?php echo($OUTPUT::getLocalStatic(__FILE__)); ?>/static/prism.css" rel="stylesheet"/>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
if ( $USER->instructor ) {
    SettingsForm::start();
    SettingsForm::dueDate();
    SettingsForm::done();
    SettingsForm::end();
} 

$OUTPUT->welcomeUserCourse();

if ( $USER->instructor ) {
    echo('<p><a href="configure.php" class="btn btn-default">Configure this Assignment</a> ');
    SettingsForm::button();
    echo('<a href="admin.php" class="btn btn-default">Explore Grade Data</a> ');
    echo('<a href="maint.php" target="_new" class="btn btn-default">Grade Maintenance</a> ');
    echo('<a href="debug.php" class="btn btn-default">Session Dump</a></p>');
}

if ( $assn_json != null ) {
    echo('<div style="border: 1px solid black">');
    echo("<p><h4>".$assn_json->title."</h4></p>\n");
    echo('<p>'.htmlent_utf8($assn_json->description)."\n");
    if( isset($assn_json->assignment) ) {
        echo('<br/>Assignment specification: <a href="'.$assn_json->assignment.'" target="_blank">');
        echo($assn_json->assignment."</a>\n");
    }
    if( isset($assn_json->solution) ) {
        echo('<br/>Sample solution: <a href="'.$assn_json->solution.'" target="_blank">');
        echo($assn_json->solution."</a>\n");
    }
    echo('</p></div>');
}

if ( $assn_json == null ) {
    echo('<p>This assignment is not yet configured</p>');
    $OUTPUT->footer();
    return;
}

if ( $submit_row == false ) {
    echo("<p><b>Please Upload Your Submission:</b></p>\n");
    echo('<form name="myform" enctype="multipart/form-data" method="post" action="'.
         addSession('index.php').'">');

    $partno = 0;
    foreach ( $assn_json->parts as $part ) {
        echo("\n<p>");
        echo(htmlent_utf8($part->title)."\n");
        if ( $part->type == "image" ) {
            echo('<input name="uploaded_file_'.$partno.'" type="file"> (Please use PNG or JPG files)</p>');
        } else if ( $part->type == "url" ) {
            echo('<input name="input_url_'.$partno.'" type="url" size="80"></p>');
        } else if ( $part->type == "code" ) {
            echo('<br/><textarea name="input_code_'.$partno.'" rows="10" style="width: 90%"></textarea></p>');
        }
        $partno++;
    }
    echo("<p>Enter optional comments below</p>\n");
    echo('<textarea rows="5" style="width: 90%" name="notes"></textarea><br/>');
    echo('<input type="submit" name="doSubmit" value="Submit" class="btn btn-default"> ');
    $OUTPUT->exitButton('Cancel');
    echo('</form>');
    $upload_max_size = ini_get('upload_max_filesize');
    echo("\n<p>Make sure each file is smaller than 1M.  Total upload limited to ");
    echo(htmlent_utf8($upload_max_size)."</p>\n");
    if ( isset($assn_json->totalpoints) && $assn_json->totalpoints > 0 ) {
        echo("<p>");
        echo(pointsDetail($assn_json));
        echo("</p>");
    }
    $OUTPUT->footer();
    return;
}

if ( $assn_json->maxassess > 0 ) {
    if ( count($to_grade) > 0 && ($USER->instructor || $grade_count < $assn_json->maxassess ) ) {
        echo('<a href="grade.php" class="btn btn-default">Peer grade other students</a> '."\n");
        // Add a done button if needed
        echo("<p> You have peer graded ".$grade_count." other student submissions.
    You must grade at least ".$assn_json->minassess." submissions for full credit on this assignment.
    You <i>can</i> grade up to ".$assn_json->maxassess." submissions if you like.</p>\n");
    
    } else if ( count($to_grade) > 0 ) {
        echo('<p>You have graded the maximum number of submissions. Congratulations!<p>');
    } else {
        echo('<p>There are no submisions waiting to be graded. Please check back later.</p>');
    }
}

// We have a submission already
$submit_json = json_decode($submit_row['json']);
echo("<p><b>Your Submission:</b></p>\n");
showSubmission($LTI, $assn_json, $submit_json, $assn_id, $USER->id);

if ( $submit_row['inst_points'] > 0 ) {
    echo("<p>Instructor grade on assignment: ". $submit_row['inst_points']."</p>\n");
} 

if ( strlen($submit_row['inst_note']) > 0 ) {
    echo("<p>Instructor Note:<br/>");
    echo(htmlent_utf8($submit_row['inst_note']));
    echo("</p>\n");
}

if ( $assn_json->maxassess < 1 ) {
    // Do nothing
} else if ( count($our_grades) < 1 ) {
    echo("<p>No peers have graded your submission yet.</p>");
} else {
    echo("<div style=\"padding:3px\"><p>You have the following grades from other students:</p>");
    echo('<table border="1" class="table table-hover table-condensed table-responsive"><tr>');
    if ( $assn_json->peerpoints > 0 ) echo("<th>Points</th>");
    echo("<th>Comments</th>");
    if ( $assn_json->flag ) echo("<th>Action</th>");
    echo("</tr>\n");

    $max_points = false;
    foreach ( $our_grades as $grade ) {
        if ( $assn_json->peerpoints > 0 ) {
            if ( $max_points === false ) $max_points = $grade['points'];
            $show = $grade['points'];
            if ( $show < $max_points ) $show = '';
            echo("<tr><td>".$show."</td>");
        }
        echo("<td>".htmlent_utf8($grade['note'])."</td>\n");
    
        if ( $assn_json->flag ) echo(
            '<td><form><input type="submit" name="showFlag" value="Flag"'.
            'onclick="$(\'#flag_grade_id\').val(\''.$grade['grade_id'].
             '\'); $(\'#flagform\').toggle(); return false;" class="btn btn-danger">'.
            '</form></td>');
        echo("</tr>\n");
    }
    echo("</table>\n");
    if ( $max_points !== false ) {
        echo("<p>Your overall score from your peers: $max_points </p>\n");
    }
}
$OUTPUT->exitButton();
?>
<form method="post" id="flagform" style="display:none">
<p>&nbsp;</p>
<p>Please be considerate when flagging an item.  It does not mean
that something is inappropriate - it simply brings the item to the
attention of the instructor.</p>
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($USER->id); ?>" name="user_id">
<input type="hidden" value="" id="flag_grade_id" name="grade_id">
<textarea rows="5" cols="60" name="note"></textarea><br/>
<input type="submit" name="doFlag"
    onclick="return confirm('Are you sure you want to bring this peer-grade entry to the attention of the instructor?');"
    value="Submit To Instructor"  class="btn btn-primary">
<input type="submit" name="doCancel" onclick="$('#flagform').toggle(); return false;" value="Cancel Flag" class="btn btn-default">
</form>
<p>
<div id="gradeinfo">Calculating grade....</div>
</p>
<script type="text/javascript">
function gradeLoad() {
    window.console && console.log('Loading and updating your grade...');
    $.getJSON('<?php echo(addSession('update_grade.php')); ?>', function(data) {
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
$OUTPUT->footerStart();
?>
<script type="text/javascript">
$(document).ready(function() {
    gradeLoad();
} );
</script>
<script src="<?php echo($OUTPUT::getLocalStatic(__FILE__)); ?>/static/prism.js" type="text/javascript"></script>
<?php
$OUTPUT->footerEnd();

