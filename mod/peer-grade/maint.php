<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
require_once "peer_util.php";

// No Buffering
noBuffer();

// Sanity checks
$LTI = ltiRequireData(array('user_id', 'link_id', 'role','context_id'));
if ( ! $USER->instructor ) die("Requires instructor");
$p = $CFG->dbprefix;

// Grab our link_id
$link_id = $LINK->id;
$assn = loadAssignment($pdo, $LTI);
$assn_json = null;
$assn_id = false;
if ( $assn === false ) {
    die("Not a peer-graded assignment");
} else {
    $assn_json = json_decode($assn['json']);
    $assn_id = $assn['assn_id'];
}

if ( isset($_POST['restartReGrade']) ) {
    $lstmt = pdoQueryDie($pdo,
        "UPDATE {$p}peer_submit SET regrade=NULL
        WHERE assn_id = :AID",
        array(":AID" => $assn_id)
    );

    $msg = $lstmt->rowcount() . " Records reset.";
    $_SESSION["success"] = $msg;
    header("Location: ".addSession('maint.php'));
    return;
}

if ( isset($_POST['reGradePeer']) ) {
    $OUTPUT->header();
    echo($OUTPUT->togglePreScript());
    echo("</head><body>\n");
    session_write_close();

    $stmt = pdoQueryDie($pdo,
        "SELECT submit_id, S.user_id AS user_id, R.result_id AS result_id,
                grade, sourcedid, service_key, displayname, email
            FROM {$CFG->dbprefix}peer_submit AS S
            JOIN {$CFG->dbprefix}peer_assn AS A
                ON S.assn_id = A.assn_id
            JOIN {$CFG->dbprefix}lti_result AS R
                ON S.user_id = R.user_id AND A.link_id = R.link_id
            JOIN {$CFG->dbprefix}lti_service AS X
                ON R.service_id = X.service_id
            JOIN {$CFG->dbprefix}lti_user AS U
                ON R.user_id = U.user_id
            WHERE S.assn_id = :AID AND regrade IS NULL",
        array(":AID" => $assn_id)
    );

    $unchanged = 0;
    $changed = 0;
    $fail = 0;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $computed_grade = computeGrade($pdo, $assn_id, $assn_json, $row['user_id']);

        $s2 = pdoQueryDie($pdo,
            "UPDATE {$CFG->dbprefix}peer_submit SET regrade=1
            WHERE submit_id = :SID",
            array(":SID" => $row['submit_id'])
        );

        if ( $row['grade'] >= $computed_grade ) {
            echo(htmlent_utf8($row['displayname']).' ('.htmlent_utf8($row['email']).') ');
            if ( $row['grade'] > $computed_grade ) {
                echo('grade='.$row['grade'].' computed='.$computed_grade." (unchanged)<br/>\n");
            } else {
                echo('grade='.$row['grade']." (matched)<br/>\n");
            }
            $unchanged++;
            continue;
        }

        $s2 = pdoQueryDie($pdo,
            "UPDATE {$CFG->dbprefix}lti_result
            SET grade=:GRA, updated_at=NOW()
            WHERE result_id = :RID",
                array(":GRA" => $computed_grade, ":RID" => $row['result_id'])
        );

        // Send the grade to the server
        $debug_log = array();
        $status = gradeSendDetail($computed_grade, $debug_log, $pdo, $row); // This is the slow bit
        if ( $status === true ) {
            echo(htmlent_utf8($row['displayname']).' ('.htmlent_utf8($row['email']).') ');
            echo("Grade $computed_grade submitted to server<br/>\n");
            $changed++;
        } else {
            echo('<pre class="alert alert-danger">'."\n");
            $msg = "result_id=".$row['result_id']."\n".
                "grade=".$row['grade']." computed_grade=".$computed_grade."\n".
                "error=".$status;
            echo_log("Problem Sending Grade: ".session_id()."\n".$msg."\n".
              "service_key=".$row['service_key']." sourcedid=".$row['sourcedid']);
            echo("</pre>\n");

            $OUTPUT->togglePre("Error sending grade",$LastPOXGradeResponse);
            flush();
            echo("Problem sending grade ".$status."<br/>\n");
            $fail++;
            continue;
        }
        flush();
    }

    echo("<p>Run complete unchanged=$unchanged changed=$changed send failure=$fail</p>\n");
    echo('<script type="text/javascript"> alert("Recompute Grades Complete"); </script>');
    return;
}

// View
$OUTPUT->header();
?>
<script type="text/javascript">
function showFrame() {
    document.getElementById('iframediv').style.display = "block";
}
</script>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

$iframeurl = addSession($CFG->wwwroot . '/mod/peer-grade/maint.php?link_id=' . $link_id);
?>

<div>
<form style="display: inline" method="POST">
  <button name="restartReGrade" class="btn btn-warning">Restart Re-Grade</button>
</form>
<form style="display: inline" method="POST" target="my_iframe" action="<?php echo($iframeurl); ?>">
  <button name="reGradePeer" onclick="showFrame();" class="btn btn-warning">Re-Compute Peer Grades</button>
  <button onclick="window.close();return false;" class="btn btn-primary">Done</button>
</form>
<p>These are maintenance tools make sure you know how to use them.
<ul>
<li><b>Re-Compute Peer Grades</b> Loops through all peer-graded submissions
and re-computes the effective grade, checking to see if the local grade
is correct.  If there is a mis-match, the local grade is updated
and the new grade is sent to the server.  This process can be stopped
and restarted as it marks entries once they have been regraded.
</li>
<li><b>Restart Re-Grades</b> Clears the "regraded" flag for all subissions so that the
next <b>Re-Compute Peer Grades</b> starts from the beginning.
</li>
</ul>
<pre>
Context: <?php echo($CONTEXT->id);
    if ( isset($CONTEXT->title) ) echo(' '.htmlent_utf8($CONTEXT->title)) ; ?>
Link id: <?php echo($link_id);
    if ( isset($LINK->title) ) echo(' '.htmlent_utf8($LINK->title)) ; ?>
</pre>

<p><b>Remaining Regrades:</b> <span id="total"><img src="<?php echo($OUTPUT->getSpinnerUrl()); ?>"></span>
<img id="totspinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display:none">
</p>

<div id="iframediv" style="display:none">
<p>Depending on buffering - output in this iframe may take a while to appear.
The number above will update as the job progreses.
Once the output starts, make sure to scroll to the bottom to see the current activity.
If you want to abort this job, navigate away using "Done".
This job may take so long it times out.  If it times out you can restart it
and it willpick up where it left off.
</p>
<iframe id="my_iframe" width="98%" height="600px" style="border: 1px black solid">
</iframe>
</div>


<?php
$OUTPUT->footerStart();
?>
<script type="text/javascript">
$UPDATE_INTERVAL = false;
function updateNumbers() {
    window.console && console.log('Calling updateNumbers');
    $.ajaxSetup({ cache: false }); // For IE...
    $.getJSON('<?php echo(addSession($CFG->wwwroot.'/mod/peer-grade/maintcount.php')); ?>',
    function(data) {
        if ( $UPDATE_INTERVAL === false ) $UPDATE_INTERVAL = setInterval(updateNumbers,10000);
        window.console && console.log(data);
        $('#totspinner').hide();
        oldtotal = $('#total').text();
        $('#total').text(data.total);
        if ( oldtotal.length > 1 && oldtotal != data.total ) $('#totspinner').show();
    });
}
updateNumbers();
</script>
<?
$OUTPUT->footerEnd();
