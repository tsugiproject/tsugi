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

if ( $CFG->google_client_id && ! U::get($_SESSION,'id') ) {
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
    header("Location: ".$rest_path->current);
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
<input type="password" name="passphrase" size="80">
</label>
<input type="submit">
</form>

<?php
$OUTPUT->footer();

