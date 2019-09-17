<?php
use \Tsugi\Util\U;
use \Tsugi\Blob\Access;

if ( ! isset($_REQUEST['pii_days']) ) die('pii_days required');

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
require_once("../admin_util.php");
require_once("expire_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Core\LTIX;
LTIX::getConnection();

$limit = 1000;

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
keep requests from timing out.   If you want to automate the process of PII expiration,
set the value
<pre>
$CFG-&gt;expire_pii_days = 120;
</pre>
in your <b>config.php</b> and then run the commands:
<pre>
cd tsugi/admin/expire
php pii-batch.php [remove]
</pre>
If you don't include <b>remove</b> it will just do a dry run and tell you what would
have been removed.
</p>
