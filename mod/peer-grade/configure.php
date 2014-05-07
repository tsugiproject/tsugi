<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);

// Model 
$p = $CFG->dbprefix;

if ( isset($_POST['json']) ) {
    $json = $_POST['json'];
    if ( get_magic_quotes_gpc() ) $json = stripslashes($json);
    $json = json_decode($json);
    if ( $json === null ) {
        $_SESSION['error'] = "Bad JSON Syntax";
        header( 'Location: '.sessionize('configure.php') ) ;
        return;
    }

    $json = json_encode($json);
    $stmt = pdo_query($pdo,
        "INSERT INTO {$p}peer_assn 
            (link_id, json, created_at, updated_at) 
            VALUES ( :ID, :JSON, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':JSON' => $json,
            ':ID' => $LTI['link_id'])
        );
    cache_clear("peer_assn");
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment updated';
        header( 'Location: '.sessionize('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.sessionize('configure.php') ) ;
    }
    return;
}

// Load up the assignment 
$row = loadAssignment($pdo, $LTI);
$json = "";
if ( $row !== false ) $json = $row['json'];


if ( strlen($json) < 1 ) {
    $json = '{ "title" : "Assignment title",
        "description" : "This assignment consists of two images to be uploaded.  This assignment is worth 10 points. 6 points come from your peers and 4 points come from you grading other student\'s submissions.",
        "grading" : "This is a relatively simple assignment.  Don\'t take off points for little mistakes.  If they seem to have done the assignment give them full credit.   Feel free to make suggestions if there are small mistakes.  Please keep your comments positive and useful.  If you do not take grading seriously, the instructors may delete your response and you will lose points.",
        "parts" : [ 
            { "title" : "Image of MySqlAdmin", 
              "type" : "image" 
            },
            { "title" : "Image of PHP code running with your name", 
              "type" : "image"
            }
        ],
        "totalpoints" : 10,
        "maxpoints" : 6,
        "minassess" : 2,
        "assesspoints" : 2,
        "maxassess" : 5
    }';
    $json = json_decode($json);
    if ( $json === null ) die("Bad JSON constant");
    $json = json_encode($json);
}
$json = json_indent($json);

// View 
html_header_content();
html_start_body();
flash_messages();
if ( ! $instructor ) die("Requires instructor role");

?>
<p>Remember to be careful if this assignment has submissions.</p>
<form method="post" style="margin-left:5%;">
<textarea name="json" rows="15" cols="80" style="width:95%" >
<?php echo($json); ?>
</textarea>
<p>
<input type="submit" value="Save">
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Cancel"></p>
</form>
<?php

html_footer_content();
