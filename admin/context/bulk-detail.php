<?php
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

LTIX::getConnection();
header('Content-Type: text/html; charset=utf-8');
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    U::flashError("Bulk mail is limited to site administrators");
    header('Location: '.$CFG->wwwroot.'/admin');
    return;
}

$bulk_id = isset($_REQUEST['bulk_id']) ? $_REQUEST['bulk_id'] + 0 : 0;
if ( $bulk_id < 1 ) {
    U::flashError('Missing bulk_id');
    header('Location: '.$CFG->wwwroot.'/admin/mail/bulk');
    return;
}

$row = $PDOX->rowDie(
    "SELECT B.*, C.title AS context_title
        FROM {$CFG->dbprefix}mail_bulk B
        LEFT JOIN {$CFG->dbprefix}lti_context C ON C.context_id = B.context_id
        WHERE B.bulk_id = :BID",
    array(':BID' => $bulk_id)
);
if ( $row === false || $row === null ) {
    U::flashError('Bulk campaign not found');
    header('Location: '.$CFG->wwwroot.'/admin/mail/bulk');
    return;
}

$meta = array();
$json = U::get($row, 'json', '');
if ( is_string($json) && $json !== '' ) {
    $decoded = json_decode($json, true);
    if ( is_array($decoded) ) {
        $meta = $decoded;
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$context_id = (int) U::get($row, 'context_id', 0);
?>
<h1>Bulk campaign #<?= (int) $bulk_id ?></h1>
<p>
  <a href="<?= htmlentities($CFG->wwwroot) ?>/admin/mail/bulk">All campaigns</a>
<?php if ( $context_id > 0 ) { ?>
  | <a href="membership?context_id=<?= $context_id ?>">Context membership</a>
  | <a href="bulk-mail.php?context_id=<?= $context_id ?>">New bulk mail</a>
<?php } ?>
</p>
<table class="table table-striped" style="max-width:900px;">
<tr><th>created_at</th><td><?= htmlentities((string) U::get($row, 'created_at', '')) ?></td></tr>
<tr><th>context</th><td><?= htmlentities((string) U::get($row, 'context_title', '')).' (#'.$context_id.')' ?></td></tr>
<tr><th>from user_id</th><td><?= (int) U::get($row, 'user_id', 0) ?></td></tr>
<tr><th>subject</th><td><?= htmlentities((string) U::get($row, 'subject', '')) ?></td></tr>
<tr><th>sent</th><td><?= htmlentities((string) U::get($meta, 'sent', '')) ?></td></tr>
<tr><th>skipped</th><td><?= htmlentities((string) U::get($meta, 'skipped', '')) ?></td></tr>
<tr><th>failed</th><td><?= htmlentities((string) U::get($meta, 'failed', '')) ?></td></tr>
<tr><th>filters</th><td>
  days=<?= htmlentities((string) U::get($meta, 'days', '')) ?>
  ; exclude_recent_bulk_days=<?= htmlentities((string) U::get($meta, 'exclude_recent_bulk_days', '')) ?>
  ; limit=<?= htmlentities((string) U::get($meta, 'limit', '')) ?>
  ; include_opted_out=<?= htmlentities((string) U::get($meta, 'include_opted_out', '')) ?>
  ; premium_only=<?= htmlentities((string) U::get($meta, 'premium_only', '')) ?>
  ; audience_count=<?= htmlentities((string) U::get($meta, 'audience_count', '')) ?>
</td></tr>
</table>
<h3>Body</h3>
<pre style="white-space:pre-wrap;max-width:900px;border:1px solid #ddd;padding:10px;"><?= htmlentities((string) U::get($row, 'body', '')) ?></pre>

<?php
if ( isset($meta['errors']) && is_array($meta['errors']) && count($meta['errors']) > 0 ) {
    echo '<h3>Sample errors</h3><ul>';
    foreach ( $meta['errors'] as $err ) {
        echo '<li>'.htmlentities((string) $err).'</li>';
    }
    echo '</ul>';
}

$query_parms = array(':BID' => $bulk_id);
$searchfields = array("sent_id", "to_user", "from_user", "subject", "json", "created_at");
$orderfields = array("created_at", "sent_id", "to_user");
$params = $_GET;
$params['bulk_id'] = $bulk_id;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
$sql = "SELECT sent_id, user_to AS to_user, user_from AS from_user, subject, json, created_at
    FROM {$CFG->dbprefix}mail_sent
    WHERE bulk_id = :BID";
$view = false;
$extra_buttons = array(
    "Campaigns" => $CFG->wwwroot."/admin/mail/bulk",
    "Admin" => $CFG->wwwroot."/admin",
);
echo '<h3>Per-recipient log (mail_sent)</h3>';
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
