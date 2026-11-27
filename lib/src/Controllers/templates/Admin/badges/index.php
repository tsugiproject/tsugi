<?php
// In the top frame, we use cookies for session.

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Badges\BadgeService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
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
$sql = "SELECT badge_guid, user_id, context_id, badge_code, badge_title, user_displayname, user_email, context_title, issued_at, linkedin_clicks
        FROM {$CFG->dbprefix}badges";
$orderfields = array("issued_at", "badge_guid", "user_displayname", "badge_title", "context_title", "user_email", "linkedin_clicks");

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$count_parms = array();
$count_sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}badges";
$search_clause = Table::searchClause($searchfields, $count_parms);
if ( $search_clause !== '' ) {
    $count_sql .= " WHERE ( ".$search_clause." )";
}
$count_row = $PDOX->rowDie($count_sql, $count_parms);
$badge_count = ($count_row && isset($count_row['count'])) ? (int) $count_row['count'] : 0;
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
$badge_label = $badge_count === 1 ? 'badge' : 'badges';
echo('<p>'.number_format($badge_count).' '.$badge_label."</p>\n");
$view_url = false;
$params = false;
$extra_buttons = array("Admin" => $CFG->wwwroot."/admin");
Table::pagedTable($newrows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);

$OUTPUT->footer();
