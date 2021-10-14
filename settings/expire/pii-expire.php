<?php
use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../settings_util.php");
require_once("expire_util.php");

session_start();

if ( ! U::get($_SESSION,'id') ) {
    $login_return = U::reconstruct_query($CFG->wwwroot . '/settings/expire');
    $_SESSION['login_return'] = $login_return;
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

\Tsugi\Core\LTIX::getConnection();

$limit = 5000;

$days = $_REQUEST['pii_days'];

$check = sanity_check_days('PII', $days);

if ( is_string($check) ) die($check);

$pii_count = get_pii_count($days);

// Note pii_where includes only non-null PII users
$sql = "UPDATE {$CFG->dbprefix}lti_user 
    SET displayname=NULL, email=NULL " .get_pii_where($days)."
    ORDER BY login_at LIMIT $limit";

if ( isset($_POST['doDelete']) && isset($_POST['pii_days']) ) {
    echo("<pre>\n");
    $start = time();

    $stmt = $PDOX->prepare($sql);
    $stmt->execute();

    $count = $stmt->rowCount();
    echo("Rows updated: $count\n");
    $delta = time() - $start;
    echo("\nEllapsed time: $delta seconds\n");
    echo("</pre>\n");
    echo("<p>Process complete - you can close this window.</p>\n");
    return;
}

?>
<h1>Expire Personally Identifable Information</h1>
<p>
Preparing to delete PII &gt; <?= $days ?>  days old for <?= $pii_count ?> users
using the following SQL:
<pre>
<?= $sql ?>
</pre>
<form method="post">
<input type="hidden" name="pii_days" value="<?= $days ?>">
<input type="submit" name="doDelete" value="Delete PII for <?= $pii_count ?> Users">
</form>
<p>
Note that online we limit the number of records that an be deleted per request to 
keep requests from timing out.   Server administrators have offline commands they
can run on the server to expire more data at once or even automate the expiry
of this type of data.
</p>
