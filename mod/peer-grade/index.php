<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$p = $CFG->dbprefix;

// Model 
$stmt = pdoQueryDie($db,
    "SELECT assn_id, json FROM {$p}peer_assn WHERE link_id = :ID",
    array(":ID" => $LTI['link_id'])
);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$json = null;
$assn_id = false;
if ( $row !== false ) {
    $json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

// Load up the submission and parts if they exist
$submit_row = loadSubmission($db, $LTI, $assn_id);
$part_rows = loadParts($db, $LTI, $submit_row);

// View 
headerContent();
?>
</head>
<body>
<?php
flashMessages();
welcomeUserCourse($LTI);

if ( $instructor ) {
    echo('<p><a href="configure.php">Configure this Assignment</a></p>');
}

if ( $json == null ) {
    echo('<p>This assignment is not yet configured</p>');
    footerContent();
    return;
} 

echo("<p><b>".$json->title."</b></p>\n");

if ( $submit_row == false ) {
    echo('<form method="post">');
    $partno = 0;
    foreach ( $json->parts as $part ) {
        if ( $part->type == "image" ) {
            echo("\n<p>");
            echo(htmlent_utf8($part->title).' ('.$part->points.' points) '."\n");
            echo('<input name="uploaded_file_'.$partno.'" type="file"><p/>');
            $partno++;
        }
    }
    echo("<p>Enter optional comments below</p>\n");
    echo('<textarea rows="5" cols="60" name="notes"></textarea><br/>');
    echo('<input type="submit" value="Submit">');
    echo('</form>');
    echo("\n<p>Make sure each if is smaller than 1MB.</p>\n");
}

footerContent();
