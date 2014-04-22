<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// No Buffering
noBuffer();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Requires instructor");
$p = $CFG->dbprefix;

// Grab our link_id
$link_id = 0;
if ( isset($_GET['link_id']) ) {
    $link_id = $_GET['link_id']+0;
}

// Load to make sure it is within our context
$link_info = false;
if ( $instructor && $link_id > 0 ) {
    $link_info = loadLinkInfo($pdo, $link_id);
}
if ( $link_info === false ) die("Invalid link");

if ( isset($_POST['resetServerGrades']) ) {
    $lstmt = pdoQueryDie($pdo,
        "UPDATE {$p}lti_result SET server_grade=NULL, retrieved_at=NULL
        WHERE link_id = :LID",
        array(":LID" => $link_id)
    );

    $msg = $lstmt->rowcount() . " Records reset.";
    $_SESSION["success"] = $msg;
    header("Location: ".sessionize('maint.php?link_id='.$link_id));
    return;
}

// http://manzzup.blogspot.com/2013/11/real-time-updating-of-php-output-using.html
function superFlush() {
    echo str_pad("",1024," ");
    flush();
}

if ( isset($_POST['getServerGrades']) ) {
    $row = pdoRowDie($pdo,
        "SELECT COUNT(*) AS count FROM {$p}lti_result AS R
        JOIN {$p}lti_service AS S ON R.service_id = S.service_id
        WHERE link_id = :LID AND grade IS NOT NULL AND 
            (server_grade IS NULL OR retrieved_at < R.updated_at) AND
            sourcedid IS NOT NULL AND service_key IS NOT NULL",
        array(":LID" => $link_id)
    );
    $total = $row['count'];
    if ( $total == 0 ) {
        $_SESSION['success'] = 'No records remain to retrieve';
        header("Location: ".sessionize('maint.php?link_id='.$link_id));
        return;
    }

    headerContent();
    echo(togglePreScript());
    session_write_close();
    echo("</head><body>\n");

    echo("Records to be processed: ".$total."<br/>");
    superFlush();

    $stmt = pdoQueryDie($pdo,
        "SELECT result_id, sourcedid, service_key FROM {$p}lti_result AS R
        JOIN {$p}lti_service AS S ON R.service_id = S.service_id
        WHERE link_id = :LID AND grade IS NOT NULL AND 
            (server_grade IS NULL OR retrieved_at < R.updated_at) AND
            sourcedid IS NOT NULL AND service_key IS NOT NULL",
        array(":LID" => $link_id)
    );

    $count = 0;
    $success = 0;
    $begin = microtime(true);
    $fail = 0;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $count = $count + 1;
        $start = microtime(true);
        // UPDATEs automatically on success
        $grade = getGrade($pdo, $row['result_id'], $row['sourcedid'], $row['service_key']);
        $et = (microtime(true) - $start) ;
        $ets = sprintf("%1.3f",$et);
        if ( $grade == false ) {
            $fail++;
            togglePre("Error at ".$count.' / '.$total.' ('.$ets.')',$LastPOXGradeResponse);
            superFlush();
        } else {
            $success++;
            // echo("Grade=".$grade."<br/>\n");
            // print_r($row); echo("<br/>\n");
            if ( $success % 10 == 0 ) {
                echo("Grade=$grade ($ets) $count / $total <br/>\n");
                superFlush();
            }
        }
        if ( $count > 1000 ) break;
    }
    // unset($_SESSION['error']);
    $et = (microtime(true) - $begin) ;
    $ets = sprintf("%1.3f",$et);

    $msg = $count . " Records processed ".$ets." seconds. success=".$success.' fail='.$fail;
    echo($msg."<br/>\n");
    echo('<script type="text/javascript"> alert("Retrieve Server Grades Complete"); </script>');
    return;
}

if ( isset($_POST['fixServerGrades']) ) {
    headerContent();
    echo(togglePreScript());
    echo("</head><body>\n");
    session_write_close();

    $stmt = pdoQueryDie($pdo,
        "SELECT result_id, link_id, grade, server_grade, note, 
            sourcedid, service_key, 
            U.user_id AS user_id, displayname, email 
        FROM {$p}lti_result AS R
        JOIN {$p}lti_service AS S ON R.service_id = S.service_id
        JOIN {$p}lti_user AS U ON R.user_id = U.user_id
        WHERE link_id = :LID AND grade IS NOT NULL AND
            server_grade IS NOT NULL AND
            server_grade < grade AND
            sourcedid IS NOT NULL AND service_key IS NOT NULL",
        array(":LID" => $link_id)
    );

    $count = 0;
    $success = 0;
    $fail = 0;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        echo(htmlent_utf8($row['displayname']));
        echo(' (');
        echo(htmlent_utf8($row['email']));
        echo(') ');
        echo("local=".$row['grade']." server=".$row['server_grade']);
        echo("<br/>\n");
        $count = $count + 1;
        if ( isset($_POST['fixServerGrades']) ) {
            $debuglog = array();
            $status = sendGradeDetail($row['grade'], null, null, $debuglog, $pdo, $row); // This is the slow bit
            if ( $status === true ) {
                echo('Grade submitted to server'."<br/>\n");
                $success++;
            } else {
                echo("Problem sending grade ".$status."<br/>\n");
                $fail++;
            }
        }
    }

    $msg = $count . " Mis-matched grade entries. fixed=$success fail=$fail";
    echo($msg."<br/>\n");
    echo('<script type="text/javascript"> alert("Fix Mis-matched Grades Complete"); </script>');
    return;
}

// Check how much work we have to do
$row = pdoRowDie($pdo,
    "SELECT COUNT(*) AS count FROM {$p}lti_result AS R
    JOIN {$p}lti_service AS S ON R.service_id = S.service_id
    WHERE link_id = :LID AND grade IS NOT NULL AND 
        (server_grade IS NULL OR retrieved_at < R.updated_at) AND
        sourcedid IS NOT NULL AND service_key IS NOT NULL",
    array(":LID" => $link_id)
);
$toretrieve = $row['count'];

$row = pdoRowDie($pdo,
    "SELECT COUNT(*) AS count FROM {$p}lti_result AS R
    JOIN {$p}lti_service AS S ON R.service_id = S.service_id
    WHERE link_id = :LID AND grade IS NOT NULL AND 
        sourcedid IS NOT NULL AND service_key IS NOT NULL",
    array(":LID" => $link_id)
);
$total = $row['count'];

// View 
headerContent();
?>
<script type="text/javascript">
function showFrame() {
    $('#my_iframe').prop('src', 'about:blank');  // Seems not to work
    document.getElementById('iframediv').style.display = "block";
}
</script>
<?php
startBody();
flashMessages();

$iframeurl = sessionize($CFG->wwwroot . '/mod/grades/maint.php?link_id=' . $link_id);
?>

<div>
<form style="display: inline" method="POST" target="my_iframe" action="<?php echo($iframeurl); ?>">
  <button name="fixServerGrades" onclick="showFrame();" class="btn btn-warning">Fix Mis-matched Grades</button>
  <button name="getServerGrades" onclick="showFrame();" class="btn btn-warning">Retrieve Server Grades</button>
</form>
<form method="post" style="display: inline">
  <button name="resetServerGrades" 
    onclick="return confirm('Are you sure you want to clear out all of the previously retrieved server grades?');"
class="btn btn-danger">Restart Server Grade Retrieval</button>
  <button onclick="window.close();" class="btn btn-primary">Done</button>
</div>
<p>These are maintenance tools make sure you know how to use them. 
<ul> 
<li><b>Fix Mis-matched Grades</b> copies our local grade to the server 
when there is a mismatch between the local grade and our most recent 
server grade.  It is quick unless there are a lot of mis-matches.
</li>
<li><b>Retrieve Server Grades</b> calls a web service to pul down a 
copy of the grade stored in the server that have not yet 
been retrieved.  This process takes abut 0.3 seconds per "grade to retrieve".
</li>
<li><b>Restart Server Grade Retrieval</b> - resets the local 
copies of server grades so that the next <b>Retrieve Server Grades</b>
will retrieve all grades.</li>
</ul>
<pre>
Context: <?php echo($LTI['context_id']); 
    if ( isset($LTI['context_title']) ) echo(' '.htmlent_utf8($LTI['context_title'])) ; ?> 
Link id: <?php echo($link_id); 
    if ( isset($link_info['title']) ) echo(' '.htmlent_utf8($link_info['title'])) ; ?> 
</pre>

<p><b>Total results:</b> <span id="total"><img src="<?php echo(getSpinnerUrl()); ?>"></span>
<img id="totspinner" src="<?php echo(getSpinnerUrl()); ?>" style="display:none">
</p>
<p><b>Grades to Retrieve:</b> <span id="toretrieve"><img src="<?php echo(getSpinnerUrl()); ?>"></span>
<img id="retspinner" src="<?php echo(getSpinnerUrl()); ?>" style="display:none">
</p>
<p><b>Mis-matched Grades:</b> <span id="mismatch"><img src="<?php echo(getSpinnerUrl()); ?>"></span>
<img id="misspinner" src="<?php echo(getSpinnerUrl()); ?>" style="display:none">
</p>

<div id="iframediv" style="display:none">
<p>Depending on buffering - output in this iframe may take a while to appear.
Make sure to scroll to the bottom to see the current activity.  
The number of grades to retrieve will be updated above even if you do 
not see output below.  Is you want to abort this job, navigate away using 
"Done" and them come back to this page.  This job may take so long it times out.
That is OK - simply come back and restart it - it will pick up where it left off.
</p>
<iframe id="my_iframe" width="98%" height="600px" style="border: 1px black solid">
</iframe>
</div>


<?php
footerStart();
?>
<script type="text/javascript">
$UPDATE_INTERVAL = false;
function updateNumbers() {
    window.console && console.log('Calling updateNumbers');
    $.ajaxSetup({ cache: false }); // For IE...
    $.getJSON('<?php echo(sessionize($CFG->wwwroot.'/mod/grades/maintcount.php?link_id='.$link_id)); ?>', 
    function(data) {
        if ( $UPDATE_INTERVAL === false ) $UPDATE_INTERVAL = setInterval(updateNumbers,15000);
        window.console && console.log(data);
        $('#totspinner').hide();
        $('#retspinner').hide();
        $('#misspinner').hide();
        oldtotal = $('#total').text();
        window.console.log("old="+oldtotal);
        $('#total').text(data.total);
        if ( oldtotal.length > 1 && oldtotal != data.total ) $('#totspinner').show();
        oldtoretrieve = $('#toretrieve').text();
        $('#toretrieve').text(data.toretrieve);
        if ( oldtoretrieve.length > 1 && oldtoretrieve != data.toretrieve ) $('#retspinner').show();
        oldmismatch = $('#mismatch').text();
        $('#mismatch').text(data.mismatch);
        if ( oldmismatch.length > 1 && oldmismatch != data.mismatch ) $('#misspinner').show();
    });
}
updateNumbers();
</script>
<?
footerEnd();
