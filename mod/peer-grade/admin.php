<?php
require_once "../../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
if ( ! $USER->instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

// Load the assignment data
$row = loadAssignment();
$assn_json = json_decode($row['json']);

// Gets counts and max of the submissions
$query_parms = array(":LID" => $LINK->id);
$orderfields =  array("S.user_id", "displayname", "email", "S.updated_at", "user_key", "max_score", "scores", "flagged", "min_score", "inst_points");
$searchfields = array("S.user_id", "displayname", "email", "S.updated_at", "user_key");

// Load up our data dpending on the kind of assessment we have
$inst_points = $assn_json->instructorpoints > 0 ? "inst_points, " : "";
$max_min_scores = $assn_json->peerpoints > 0 ? "MAX(points) as max_score, MIN(points) AS min_score," : "";
$count_scores = $assn_json->maxassess > 0 ? "COUNT(points) as scores," : "";
$sql =
    "SELECT S.user_id AS user_id, displayname, email, S.submit_id as _submit_id,
        $max_min_scores
        $count_scores
        $inst_points 
        COUNT(DISTINCT flag_id) as flagged,
        MAX(S.updated_at) AS updated_at, user_key
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
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

// Make us a paged table and by default sort by flagged descending
$parm = $_GET;
if ( ! isset($parm['order_by']) ) {
    $parm['order_by'] = 'flagged';
    $parm['desc'] = '1';
}

Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, "student.php", $parm, array('Exit' => 'index.php') );

$OUTPUT->footer();
