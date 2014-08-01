<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "lib.php";

use \Tsugi\Core\Table;

// Sanity checks
$LTI = \Tsugi\Core\LTIX::requireData(array('user_id', 'link_id', 'role','context_id', "result_id"));
if ( ! $USER->instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

// Get basic grade data
$query_parms = array(":LID" => $LINK->id);
$orderfields =  array("R.updated_at", "displayname", "email", "grade");
$searchfields = $orderfields;
$sql = 
    "SELECT R.user_id AS user_id, displayname, email,
        grade, note, R.updated_at AS updated_at
    FROM {$p}lti_result AS R
    JOIN {$p}lti_user AS U ON R.user_id = U.user_id
    WHERE R.link_id = :LID";

// View 
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
welcomeUserCourse();

if ( isset($GRADE_DETAIL_CLASS) && is_object($GRADE_DETAIL_CLASS) ) {
    $detail = $GRADE_DETAIL_CLASS;
} else {
    $detail = false;
}

Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, "grade-detail.php");

$OUTPUT->footer();
