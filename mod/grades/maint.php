<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

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
    echo("</head><body>\n");
    echo('<p id="abort"><a href="maint.php?link_id='.$link_id.'">Interrupt 
        Maintenance Process</a></p>'."\n");

    echo("Records to be processed: ".$total."<br/>");

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
            flush();
        } else {
            $success++;
            // echo("Grade=".$grade."<br/>\n");
            // print_r($row); echo("<br/>\n");
            if ( $success % 10 == 0 ) {
                echo("Grade=$grade ($ets) $count / $total <br/>\n");
                flush();
            }
        }
        $_SESSION['error'] = 'Interrupted at '.$count.' / '.$total. ' success='.$success.' fail='.$fail;
        if ( $count > 100 ) break;
    }
    unset($_SESSION['error']);
    $et = (microtime(true) - $begin) ;
    $ets = sprintf("%1.3f",$et);

    $msg = $count . " Records processed ".$ets." seconds. success=".$success.' fail='.$fail;
    $_SESSION["success"] = $msg;
    echo($msg."<br/>\n");
    echo('<script type="text/javascript"> document.getElementById("abort").style.display = "none"; </script>');
    echo('<a href="maint.php?link_id='.$link_id.'">Continue</a></p>'."\n");
    return;
}

if ( isset($_POST['checkServerGrades']) || isset($_POST['fixServerGrades']) ) {
    headerContent();
    echo(togglePreScript());
    echo("</head><body>\n");

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

    if ( isset($_POST['fixServerGrades']) ) {
        $msg = $count . " Mis-matched grade entries. fixed=$success fail=$fail";
    } else {
        $msg = $count . " Mis-matched grade entries.";
    }
    $_SESSION["success"] = $msg;
    echo($msg."<br/>\n");
    echo('<a href="maint.php?link_id='.$link_id.'">Continue</a></p>'."\n");
    return;
}

// View 
headerContent();
startBody();
flashMessages();
?>

<form method="post">
  <button name="resetServerGrades" class="btn btn-default">Restart Server Grade Retrieval</button>
  <button name="getServerGrades" class="btn btn-default">Retrieve Server Grades</button>
  <button name="checkServerGrades" class="btn btn-default">Check Server Grades</button>
  <button name="fixServerGrades" class="btn btn-warning">Fix Server Grades</button>
  <button onclick="window.close();" class="btn btn-primary">Done</button>
</post>

<?php
footerContent();
