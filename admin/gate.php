<?php

use \Tsugi\Util\U;
use \Tsugi\UI\Output;

require_once $CFG->dirroot."/admin/admin_util.php";

$REDIRECTED = false;
$rest_path = U::rest_path();

if ( $CFG->adminpw === false ) {
    unset($_SESSION["admin"]);
    die('Please set $CFG->adminpw to a plaintext or hashed string');
}

// Make sure we have an initialized database before sending to login.php
try {
    define('PDO_WILL_CATCH', true);
    $PDOX = \Tsugi\Core\LTIX::getConnection();
    $stmt = $PDOX->queryReturnError("SELECT key_id FROM {$CFG->dbprefix}lti_key  LIMIT 1");
    $havedatabase = $stmt->success;
} catch(\PDOException $ex){
    $havedatabase = false;
}

if ( $havedatabase && $CFG->google_client_id && ! U::get($_SESSION,'id') ) {
    $_SESSION['login_return'] = $rest_path->full;
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

if ( isset($_POST['passphrase']) ) {
    unset($_SESSION["admin"]);
    $apw = $CFG->adminpw;
    $phrase = $_POST['passphrase'];
    $hash = 'sha256:'.lti_sha256($phrase);
    if ( (strpos($apw, 'sha256:') === false && $phrase == $apw ) ||
       (strpos($apw, 'sha256:') === 0 && $hash == $apw ) ) {

        $_SESSION["admin"] = "yes";
        error_log("Admin login IP=".$_SERVER["REMOTE_ADDR"].
            (isset($_SESSION['id']) ? " id=". $_SESSION['id'].' email='.$_SESSION['email'] : " developer mode"));
    } else {
        error_log("Admin bad pw IP=".$_SERVER["REMOTE_ADDR"].
            (isset($_SESSION['id']) ? " id=". $_SESSION['id'].' email='.$_SESSION['email'] : " developer mode"));
    }
    $rest_path = \Tsugi\Util\U::rest_path();
    $redirect = U::reconstruct_query($rest_path->current);
    $redirect = U::addSession($redirect);

    Output::doRedirect($redirect);
    $REDIRECTED = true;
    return;
}

if ( isset($_SESSION['admin']) ) return;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
?>
<form method="post">
<label for="passphrase">Admin Unlock:<br/>
<input type="password" autocomplete="off" name="passphrase" size="80">
</label>
<input type="submit">
</form>

<?php
$OUTPUT->footer();

