<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));

// Model
$p = $CFG->dbprefix;

if ( isset($_POST['json']) ) {
    $json = $_POST['json'];
    if ( get_magic_quotes_gpc() ) $json = stripslashes($json);
    $json = json_decode($json);
    if ( $json === null ) {
        $_SESSION['error'] = "Bad JSON Syntax";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    $json = json_encode($json);
    $stmt = $PDOX->queryReturnError(
        "INSERT INTO {$p}peer_assn
            (link_id, json, created_at, updated_at)
            VALUES ( :ID, :JSON, NOW(), NOW())
            ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
        array(
            ':JSON' => $json,
            ':ID' => $LINK->id)
        );
    Cache::clear("peer_assn");
    if ( $stmt->success ) {
        $_SESSION['success'] = 'Assignment updated';
        header( 'Location: '.addSession('index.php') ) ;
    } else {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.addSession('configure.php') ) ;
    }
    return;
}

// Load up the assignment
$row = loadAssignment($LTI);
$json = "";
if ( $row !== false ) $json = $row['json'];

// Clean up the JSON for presentation
if ( strlen($json) < 1 ) $json = getDefaultJson();
$json = jsonIndent($json);

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
if ( ! $USER->instructor ) die("Requires instructor role");

?>
<p>Be careful in making any changes if this assignment has submissions.</p>
<p>
The assignment is configured by carefully editing the json below without 
introducing syntax errors.  Someday this will have a slick configuration 
screen but for now we edit the json.  I borrowed this user interface from the early
days of Coursera configuration screens :).
See the instructions below for detail on how to configure the assignment
and the kinds of changes that are safe to make after the assignment starts.
</p>
<form method="post" style="margin-left:5%;">
<textarea name="json" rows="25" cols="80" style="width:95%" >
<?php echo($json); ?>
</textarea>
<p>
<input type="submit" value="Save">
<input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel"></p>
</form>
<p><b>Configuration options:</b></p>
<ul>
<li>The title, description and grading text will be displayed to the user.  These can be edited
at any time - even after the assignment has started.</li>
<li>The 'parts' are an array of parts, each item has a title and type.  The title text for a part can be 
edited at any time.  You should not change the number, type, or order of the parts once the assignment 
has started.  The type can be one of the following:
<ul>
<li>image - The user will be prompted for an uploaded image.  This image needs to be &lt; 1M in size
and must be a JPG or PNG.   These strict limitations are to insure that the database does not get too big
and that students don't upload viruses for the other students.</li>
<li>url - This is a url for the user to view.</li>
</ul>
</li>
<li>totalpoints - this is the number of points for the assignment.   Each of the peer-graders
will assign a value up to this number.  Currently the grading policy is to take the 
highest score from peers since this is really intended for pass/fail assignments and getting
feedback from peers rather than carefully crafted assignments with subtle differences in the scores.</li>
<li>maxpoints - this is the maximum points that comes from the other students</li>
<li>minassess - this is the minimum number of peer assessments each student must do</li>
<li>asssesspoints - this is the number of points students get for each peer assessment that they do</li>
<li>maxassess - this is the maximum number of peer assessments the student can do above and beyond
the minimum</li>
</ul>
<p>
You can change any of these last five point values while an assessment is running, but you should 
probably then use "Grade Maintenance" to regrade all assignments to make sure that grading is 
consistent across all students.  Changing 'maxassess' or 'minassess' will not delete any assessments
that have already been done - it simply changes the policy in terms of new assessments the students 
are allowed to do.
</p>
<p>
If you change the type or number of parts while an assessment is live things might 
actually fail (nasty error messages on student screens).  If you need to make such a 
change, it is probably better to start over with a 
new assignment.</p>
<p>
This code is open source and relatively easy to work with.   It is entirely possible to add new features
to this over time if there is interest.
</p>

<?php

$OUTPUT->footer();
