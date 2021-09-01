<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

// TODO: Write code to transfer single-use issuers into their keys to handle
// legacy dynamic configuration set ups

if ( U::get($_POST, 'unused') ) {
    $sql = "DELETE FROM {$CFG->dbprefix}lti_issuer 
        WHERE issuer_id IN (
            SELECT issuer_id FROM (
                SELECT I.issuer_id AS issuer_id, COUNT(K.key_id) AS count FROM {$CFG->dbprefix}lti_issuer AS I
                LEFT JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
                GROUP BY I.issuer_id
            ) C
            WHERE C.count = 0
        )
";
    $PDOX->queryDie($sql);
    $_SESSION['success'] = "Issuers deleted";
    header("Location: issuer-maint.php");
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
$sql = "SELECT COUNT(*) AS count FROM (
        SELECT I.issuer_id AS issuer_id, COUNT(K.key_id) AS count FROM {$CFG->dbprefix}lti_issuer AS I
    LEFT JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
    GROUP BY I.issuer_id) C
    WHERE C.count = 0
";
$row = $PDOX->rowDie($sql);
$zeros = $row['count'];

$sql = "SELECT COUNT(*) AS count FROM (
        SELECT I.issuer_id AS issuer_id, COUNT(K.key_id) AS count FROM {$CFG->dbprefix}lti_issuer AS I
    LEFT JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
    GROUP BY I.issuer_id) C
    WHERE C.count = 1
";
$row = $PDOX->rowDie($sql);
$ones = $row['count'];

$sql = "SELECT COUNT(*) AS count FROM (
        SELECT I.issuer_id AS issuer_id, COUNT(K.key_id) AS count FROM {$CFG->dbprefix}lti_issuer AS I
    LEFT JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
    GROUP BY I.issuer_id) C
    WHERE C.count > 1
";
$row = $PDOX->rowDie($sql);
$multi = $row['count'];
?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
Issuer Maintenance</h1>
  <a href="issuers" class="btn btn-default active">All Issuers</a>
<ul>
<li>Multi-use issuers: <?= $multi ?></li>
<li>Single-use issuers: <?= $ones ?></li>
<li>Unused issuers: <?= $zeros ?></li>
</ul>
<?php if ( $zeros > 0 ) { ?>
<form method="post">
<input type="submit" name="unused" class="btn btn-warning" value="Remove Unused Issuers" />
</form>

<?php } ?>
<?php
$OUTPUT->footer();
