<?php

use \Tsugi\Util\U;

// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

header('Content-Type: text/html; charset=utf-8');
LTIX::session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

$query_parms = false;

$searchfields = array("M.membership_id", "C.context_id", "M.user_id", "role", "role_override", 
	"M.created_at", "M.updated_at", "email", "displayname", "user_key");

$sql = "SELECT membership_id, 'detail' AS 'Membership', M.context_id AS Context, M.user_id as User, 
            role, role_override, M.created_at, M.updated_at, email, displayname, user_key
        FROM {$CFG->dbprefix}lti_membership as M
        JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
        JOIN {$CFG->dbprefix}lti_context AS C ON M.context_id = C.context_id
        WHERE M.context_id = :CID AND
         C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
         OR C.user_id = :UID";
$query_parms = array(
    ":CID" => $_REQUEST['context_id'],
    ":UID" => $_SESSION['id']
);

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">View Contexts</a>
</p>
<?php

Table::pagedTable($newrows, $searchfields, $searchfields, "member-detail");

$OUTPUT->footer();

