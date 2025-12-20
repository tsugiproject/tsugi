<?php

use \Tsugi\Util\U;
use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($_SESSION['context_id']) ) {
    die('Context required');
}

$context_id = $_SESSION['context_id'];
$user_id = $_SESSION['id'];

// Check if user is instructor/admin for this context
$is_instructor = isInstructor();

if ( ! $is_instructor ) die('Requires instructor');

$p = $CFG->dbprefix;

$user_info = false;
$links = array();
$class_sql = false;
$summary_sql = false;

$link_id = 0;
if ( isset($_GET['link_id']) ) {
    $link_id = $_GET['link_id']+0;
}

$link_info = false;
if ( $link_id > 0 ) {
    $link_info = $PDOX->rowDie(
        "SELECT link_id, title FROM {$p}lti_link 
         WHERE link_id = :LID AND context_id = :CID",
        array(':LID' => $link_id, ':CID' => $context_id)
    );
}

// Get context title from session or database
$context_title = U::get($_SESSION, 'context_title');
if ( ! $context_title ) {
    $context_info = $PDOX->rowDie(
        "SELECT title FROM {$p}lti_context WHERE context_id = :CID",
        array(':CID' => $context_id)
    );
    $context_title = $context_info ? $context_info['title'] : 'Unknown Context';
}

if ( isset($_GET['link_id'] ) ) {
    $query_parms = array(":LID" => $link_id);
    $searchfields = array("R.user_id", "displayname", "grade", "R.updated_at", "server_grade", "retrieved_at");
    // Optimized: Start with lti_result filtered by link_id and grade, then join to user
    // Removed redundant context_id check since link_id is already unique
    $class_sql =
        "SELECT R.user_id AS user_id, U.displayname, R.grade,
            R.updated_at as updated_at, R.server_grade, R.retrieved_at
        FROM {$p}lti_result AS R
        INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
        WHERE R.link_id = :LID AND R.grade IS NOT NULL AND R.deleted = 0";
} else {
    $query_parms = array(":CID" => $context_id);
    $orderfields = array("R.user_id", "displayname", "email", "user_key", "grade_count");
    $searchfields = array("R.user_id", "displayname", "email", "user_key");
    // Optimized: Start with lti_link filtered by context_id, then join to results
    // This reduces the initial dataset before joining
    $summary_sql =
        "SELECT R.user_id AS user_id, U.displayname, U.email, COUNT(R.grade) AS grade_count, U.user_key
        FROM {$p}lti_link as L
        INNER JOIN {$p}lti_result AS R ON L.link_id = R.link_id AND R.grade IS NOT NULL AND R.deleted = 0
        INNER JOIN {$p}lti_user as U ON R.user_id = U.user_id
        WHERE L.context_id = :CID
        GROUP BY R.user_id, U.displayname, U.email, U.user_key";
}

$lstmt = $PDOX->queryDie(
    "SELECT DISTINCT L.title as title, L.link_id AS link_id
    FROM {$p}lti_link AS L JOIN {$p}lti_result as R
        ON L.link_id = R.link_id AND R.grade IS NOT NULL
    WHERE L.context_id = :CID",
    array(":CID" => $context_id)
);
$links = $lstmt->fetchAll();

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('View My Grades'), 'index.php');
if ( $links !== false && count($links) > 0 ) {
    $submenu = new \Tsugi\UI\Menu();
    foreach($links as $link) {
        $submenu->addLink($link['title'], 'grades.php?link_id='.$link['link_id']);
    }
    $menu->addRight(__('Activity Detail'), $submenu);
}

// Record analytics (instructors don't get recorded, but we can still track the page)
$analytics_path = '/lms/grades';
$analytics_title = 'Grade Book';
$analytics_link_id = lmsRecordLaunchAnalytics($analytics_path, $analytics_title);

// If instructor/admin, add an analytics button
$is_admin = isAdmin();
$show_analytics = $is_instructor || $is_admin;
$analytics_page_url = $show_analytics ? 'analytics.php' : null;

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();

echo("<p>Class: ".htmlspecialchars($context_title)."</p>\n");
if ( $link_info ) echo("<p>Link: ".htmlspecialchars($link_info["title"])."</p>\n");

if ( $summary_sql !== false ) {
    Table::pagedAuto($summary_sql, $query_parms, $searchfields, $orderfields, "index.php?detail=yes");
}

if ( $class_sql !== false ) {
    Table::pagedAuto($class_sql, $query_parms, $searchfields, $searchfields, "index.php?detail=yes");
}

$OUTPUT->footer();
