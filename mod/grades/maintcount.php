<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

headerJson();

// Sanity checks
$LTI = \Tsugi\Core\LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));
if ( ! $USER->instructor ) die("Requires instructor");
$p = $CFG->dbprefix;

// Grab our link_id
$link_id = 0;
if ( isset($_GET['link_id']) ) {
    $link_id = $_GET['link_id']+0;
}

// Load to make sure it is within our context
$link_info = false;
if ( $USER->instructor && $link_id > 0 ) {
    $link_info = loadUserInfo($link_id);
}
if ( $link_info === false ) die("Invalid link");

// Check how much work we have to do
$row = $PDOX->rowDie(
    "SELECT COUNT(*) AS count FROM {$p}lti_result AS R
    JOIN {$p}lti_service AS S ON R.service_id = S.service_id
    WHERE link_id = :LID AND grade IS NOT NULL AND
        (server_grade IS NULL OR retrieved_at < R.updated_at) AND
        sourcedid IS NOT NULL AND service_key IS NOT NULL",
    array(":LID" => $link_id)
);
$toretrieve = $row['count'];

$row = $PDOX->rowDie(
    "SELECT COUNT(*) AS count FROM {$p}lti_result AS R
    JOIN {$p}lti_service AS S ON R.service_id = S.service_id
    WHERE link_id = :LID AND grade IS NOT NULL AND
        sourcedid IS NOT NULL AND service_key IS NOT NULL",
    array(":LID" => $link_id)
);
$total = $row['count'];

$row = $PDOX->rowDie(
    "SELECT COUNT(*) AS count
    FROM {$p}lti_result AS R
    JOIN {$p}lti_service AS S ON R.service_id = S.service_id
    WHERE link_id = :LID AND grade IS NOT NULL AND
        server_grade IS NOT NULL AND
        server_grade < grade AND
        sourcedid IS NOT NULL AND service_key IS NOT NULL",
    array(":LID" => $link_id)
);
$mismatch = $row['count'];

echo(json_encode(array("total" => $total, "toretrieve" => $toretrieve,
    "mismatch" => $mismatch)));
