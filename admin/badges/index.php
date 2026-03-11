<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Badges\BadgeService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

if ( ! BadgeService::tableExists() ) {
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    $OUTPUT->flashMessages();
    echo("<h2>Badges Awarded</h2>\n");
    echo("<p>The badges table has not been created yet. Run the database upgrade to create it.</p>\n");
    echo('<p><a href="'.$CFG->wwwroot.'/admin" class="btn btn-default">Back to Admin</a></p>'."\n");
    $OUTPUT->footer();
    return;
}

$query_parms = array();
$searchfields = array("badge_guid", "user_displayname", "user_email", "badge_code", "badge_title", "context_title", "issued_at");
$sql = "SELECT badge_guid, user_id, context_id, badge_code, badge_title, user_displayname, user_email, context_title, issued_at
        FROM {$CFG->dbprefix}badges";
$orderfields = array("issued_at", "badge_guid", "user_displayname", "badge_title", "context_title", "user_email");

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrow['_href_badge_guid'] = $CFG->wwwroot . '/assertions/' . urlencode($row['badge_guid']) . '.html';
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

echo("<h2>Badges Awarded</h2>\n");
$view_url = false;
$params = false;
$extra_buttons = array("Admin" => $CFG->wwwroot."/admin");
Table::pagedTable($newrows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);

$OUTPUT->footer();
