<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
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

if ( $assn_id != false && $json != null && isset($_POST['notes']) ) {
    if ( $submit_row !== false ) {
        $_SESSION['error'] = 'Cannot submit an assignment twice';
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }

    $blob_ids = array();
    foreach ( $json->parts as $part ) {
        $partno = 0;
        if ( $part->type == 'image' ) {
            $fname = 'uploaded_file_'.$partno;
            $partno++;
            if( ! isset($_FILES[$fname]) ) {
                $_SESSION['error'] = 'Problem with uplaoded files - perhaps too much data was uploaded';
                die( 'Location: '.sessionize('index.php') ) ;
                return;
            }
            $fdes = $_FILES[$fname];
            $blob_id = uploadFileToBlob($db, $LTI, $fdes);
            if ( $blob_id === false ) {
                $_SESSION['error'] = 'Problem storing files';
                die( 'Location: '.sessionize('index.php') ) ;
                return;
            }
            $blob_ids[] = $blob_id;
        }
    }

    $submission = new stdClass();
    $submission->notes = $_POST['notes'];
    $submission->blob_ids = $blob_ids;
    $json = json_encode($submission);
    $stmt = pdoQuery($db,
        "INSERT INTO {$p}peer_submit 
            (assn_id, user_id, json, created_at, updated_at) 
            VALUES ( :AID, :UID, :JSON, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':AID' => $assn_id,
            ':JSON' => $json,
            ':UID' => $LTI['user_id'])
        );
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment submitted';
        header( 'Location: '.sessionize('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.sessionize('index.php') ) ;
    }
    return;
}

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
    echo('<form name="myform" enctype="multipart/form-data" method="post" action="'.
         sessionize('index.php').'">');

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
    echo('<input type="submit" name="doSubmit" value="Submit">');
    echo('</form>');
    echo("\n<p>Make sure each file is smaller than 1MB.</p>\n");
    footerContent();
}

// We have a submission already
$submit = json_decode($submit_row['json']);
print_r($submit);

footerContent();
