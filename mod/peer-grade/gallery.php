<?php
require_once "../../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false && strlen($row['json']) > 0 ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

if ( $assn_id === false ) {
    die('Assignment not configured');
}

if ( $assn_json->gallery == 'off' ) {
    die('Gallery not enabled for assignment');
}

// Load the assignment data
if ( $USER->instructor || $assn_json->gallery == 'always') {
    // It is all good
} else {
    $submit_row = loadSubmission($assn_id, $USER->id);
    if ( $submit_row === false ) {
        die('You have not yet submitted your assignment');
    }
}

// Gets counts and max of the submissions
$query_parms = array(":LID" => $LINK->id);
if ( $USER->instructor ) {
    $orderfields =  array("S.user_id", "rating", "displayname", "email", "S.created_at", "S.updated_at", "user_key", "max_score", "scores", "flagged", "min_score", "inst_points", "S.note");
    $searchfields = array("S.user_id", "displayname", "email", "S.created_at", "S.updated_at", "user_key", "S.note");
} else {
    $orderfields =  array("S.created_at", "S.updated_at", "rating");
    $searchfields = false;
    if ( $assn_json->notepublic == "true" ) {
        $orderfields[] = "note";
        $searchfields = array("S.note");
    }
}

// Load up our data dpending on the kind of assessment we have
$inst_points = $assn_json->instructorpoints > 0 ? "inst_points, " : "";
$max_min_scores = $assn_json->peerpoints > 0 ? "MAX(points) as max_score, MIN(points) AS min_score," : "";
$ratings = $assn_json->rating > 0 ? "S.rating AS rating," : "";
$count_scores = $assn_json->maxassess > 0 ? "COUNT(points) as scores," : "";
$sql =
    "SELECT S.user_id AS user_id, displayname, email, S.json, S.submit_id as _submit_id, S.note AS note,
        $max_min_scores
        $ratings
        $count_scores
        $inst_points 
        COUNT(DISTINCT flag_id) as flagged,
        MAX(G.updated_at) AS updated_at, user_key,
        S.created_at
    FROM {$p}peer_assn AS A JOIN {$p}peer_submit as S
        ON A.assn_id = S.assn_id
    LEFT JOIN {$p}peer_grade AS G
        ON S.submit_id = G.submit_id
    LEFT JOIN {$p}peer_flag AS F
        ON S.submit_id = F.submit_id
    JOIN {$p}lti_user AS U
        ON S.user_id = U.user_id
    WHERE A.link_id = :LID
    GROUP BY S.submit_id";

// View
$OUTPUT->header();
?>
<style>
li {padding-top: 0.5em;}
pre {padding-left: 2em;}
</style>
<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

// Make us a paged table - leave default sort order "whatever"
$parm = $_GET;

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields, $parm);

$rows = $PDOX->allRowsDie($newsql, $query_parms);

if ( $USER->instructor ) {
    $view = "student.php";
} else {
    $view = "gallery-detail.php";
}

Table::pagedHeader($rows, $searchfields, $orderfields, $view, $parm, array('Exit' => 'index.php'));

echo("<ul>\n");
foreach($rows as $row ) {
    $more = false;
    $user_id = $row['user_id']+0;
    $submit_json = json_decode($row['json']);
    $inline = false;

    // We currently only have rating in the detail view
    if ( $assn_json->rating == 0 ) {
        if ( count($assn_json->parts) == 1 ) {
            $part = $assn_json->parts[0];
            if ( $part->type == 'content_item' || $part->type == 'url' ) $inline = true;
            // if ( $part->type == 'image' ) $inline = true;
        }
    }

    if ( count($submit_json->blob_ids) > 0 ) $more = true;
    if ( (count($submit_json->content_items) + count($submit_json->urls)) > 1 ) $more = true;
    $note = $submit_json->notes;
    if ( strlen($note) < 1 ) $note = "Note missing.";
    if ( strlen($note) > 200 ) {
        $note = substr($note, 0, 200);
        $more = true;
    }

    // Until we have rating inline
    if ( $assn_json->rating > 0 ) {
        $more = true;
    } 

    echo("<li>\n");
    if ( $USER->id == $row['user_id'] ) {
        echo('<span style="color: green;">'.htmlentities($note)."</span>\n");
    } else {
        echo(htmlentities($note)."\n");
    }

    if ( $more ) {
        echo('(<a href="gallery-detail.php?user_id='.$row['user_id'].'">More</a>)');
        echo("<br/>\n");
    }
    if ( $inline ) {
        showSubmission($assn_json, $submit_json, $assn_id, $user_id);
    }
    if ( $row['rating'] > 1 ) {
        echo("Rating: ".$row['rating']."\n");
    }
    if ( $USER->instructor ) {
        echo('<br/>Admin Info: '.htmlentities($row['displayname']).' '.htmlentities($row['email'])."\n");
        echo('<a href="gallery-student.php?user_id='.$row['user_id'].'">Detail</a>');
    }
    echo("</li>\n");
}
echo("</ul>\n");

$OUTPUT->footer();
