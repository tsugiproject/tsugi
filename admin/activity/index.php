<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$query_parms = false;
$searchfields = array("A.link_id", "L.title", "C.title", "L.path", "A.created_at", "A.updated_at");
$sql = "SELECT A.link_id AS link_id, L.title AS link_title, link_count, C.title AS context_title, A.created_at, A.updated_at, event, L.path
        FROM {$CFG->dbprefix}lti_link_activity AS A
        LEFT JOIN {$CFG->dbprefix}lti_link AS L ON A.link_id = L.link_id
        LEFT JOIN {$CFG->dbprefix}lti_context AS C ON L.context_id = C.context_id";
$orderfields = array("A.updated_at", "A.link_count", "link_title", "context_title", "event", "path", "A.created_at");

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrow['link_id'] = ($newrow['link_id']*10000)+$newrow['event'];
    if ( $newrow['event'] > 0 ) {
        $newrow['link_title'] .= ' (' . $newrow['event'] . ')';
    }
    unset($newrow['event']);
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$view_url = "activity-detail";
$params=false; // Defaults to _GET
$extra_buttons = array("Admin" =>   $CFG->wwwroot."/admin");
Table::pagedTable($newrows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);

$OUTPUT->footer();

