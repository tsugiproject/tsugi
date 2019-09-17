<?php
use \Tsugi\Util\U;
use \Tsugi\Blob\Access;

if ( ! isset($_REQUEST['days']) ) die('days required');
if ( ! isset($_GET['base']) ) die('Base required');

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
require_once("../admin_util.php");
require_once("expire_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Core\LTIX;
LTIX::getConnection();

$base = $_GET['base'];
if ( $base == 'user' ) {
    $table = 'lti_user';
    $limit = 100; // Takes about 10 seconds
    $where = '';
    $where = " AND user_id <> ".$_SESSION['id'].' ';
} else if ( $base == 'context' ) {
    $table = 'lti_context';
    $limit = 10;
    $where = '';
} else if ( $base == 'tenant' ) {
    $table = 'lti_key';
    $limit = 1;
    $where = " AND ".get_safe_key_where().' ';
} else {
    die('Invalid base value');
}

if ( ! isset($_GET['days']) ) die('Required parameter days');
if ( ! is_numeric($_GET['days']) ) die('days must be a number');
$days = $_GET['days'] + 0;
if ($days < 1 ) die('Bad value for days');

$check = sanity_check_days($base, $days);
if ( is_string($check) ) die($check);

$count = get_expirable_records($table, $days);

$sql = "DELETE FROM {$CFG->dbprefix}{$table}\n".
    get_expirable_where($days)."\n".$where.
    "ORDER BY login_at LIMIT $limit";

if ( isset($_POST['doDelete']) && isset($_POST['days']) ) {
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
    <h1>Expire <?= htmlentities(ucfirst($base)) ?> Data</h1>
<p>
Preparing to delete <?= $count ?> <?= htmlentities(ucfirst($base)) ?>s &gt; <?= $days ?>  days 
since last login:
<pre>
<?= $sql ?>
</pre>
<form method="post">
<input type="hidden" name="days" value="<?= $days ?>">
<input type="submit" name="doDelete" value="Delete <?= $count ?> <?= htmlentities(ucfirst($base)) ?>s and All Associated Data">
</form>
<p><b>There is no undo for this - so be very careful!</b></p>
<p>
Note that online we limit the number of records that an be deleted per request to 
be <?= $limit ?> to keep
requests from timing out.   If you want to automate the process of <?= htmlentities($base) ?> expiration,
set the value
<pre>
$CFG-&gt;expire_<?= htmlentities($base) ?>_days = 120;   // Choose your value
</pre>
in your <b>config.php</b> and then run the commands:
<pre>
cd tsugi/admin/expire
php login-batch.php <?= htmlentities($base) ?> [remove]
</pre>
If you don't include <b>remove</b> it will just do a dry run and tell you what would
have been removed.
</p>
