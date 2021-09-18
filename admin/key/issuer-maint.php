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

if ( U::get($_POST, 'ones') ) {
    $sql = "SELECT * FROM (
            SELECT I.*, K.key_id, COUNT(K.key_id) AS count FROM {$CFG->dbprefix}lti_issuer AS I
                LEFT JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
                GROUP BY I.issuer_id, key_id
            ) C
            WHERE C.count = 1
        ";
    $rows = $PDOX->allRowsDie($sql);
    // echo("<pre>\n");var_dump($rows);die();
    if ( !$rows || !is_array($rows) || count($rows) < 1 ) {
        $_SESSION['success'] = "Nothing found to move...";
        header("Location: issuer-maint.php");
        return;
    }

    $count = 0;
    foreach($rows as $row) {
        $sql = "UPDATE {$CFG->dbprefix}lti_key SET
                issuer_id = NULL,
                lms_issuer = :lms_issuer,
                lms_issuer_sha256 = :lms_issuer_sha256,
                lms_client = :lms_client,
                lms_oidc_auth = :lms_oidc_auth,
                lms_keyset_url = :lms_keyset_url,
                lms_token_url = :lms_token_url,
                lms_token_audience = :lms_token_audience
            WHERE key_id = :ID
        ";

        $values = array(
            ":ID" => $row['key_id'],
            ":lms_issuer" => $row['issuer_key'],
            ":lms_issuer_sha256" => hash('sha256', trim($row['issuer_key'])),
            ":lms_client" => $row['issuer_client'],
            ":lms_oidc_auth" => $row['lti13_oidc_auth'],
            ":lms_keyset_url" => $row['lti13_keyset_url'],
            ":lms_token_url" => $row['lti13_token_url'],
            ":lms_token_audience" => $row['lti13_token_audience'],
        );
        $PDOX->queryDie($sql, $values);
        $count = $count + 1;
        // echo("<pre>\n");echo("$sql\n");var_dump($values);die();

    }

    $_SESSION['success'] = "Moved LMS Data to Key ($count)";
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
<?php if ( $ones > 0 ) { ?>
<form method="post">
<input type="submit" name="ones" class="btn btn-warning" value="Move Single Issuers to Key Table" />
</form>

<?php } ?>
<?php
$OUTPUT->footer();
