<?php

require_once $CFG->dirroot."/admin/admin_util.php";

$REDIRECTED = false;

if ( $CFG->adminpw === false || strpos($CFG->adminpw,"warning:") === 0 ) {
    unset($_SESSION["admin"]);
    die('Please set an $CFG->adminpw to a value');
}

if ( ! ( isset($_SESSION['id']) || $CFG->DEVELOPER) ) {
    unset($_SESSION["admin"]);
    header('HTTP/1.x 404 Not Found');
    die();
}

if ( isset($_POST['passphrase']) ) {
    unset($_SESSION["admin"]);
    if ( $_POST['passphrase'] == $CFG->adminpw ) {
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

