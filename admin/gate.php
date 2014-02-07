<?php

require_once("../lib/lms_lib.php");

if ( $CFG->adminpw == "something-super-secret!" || 
    ! ( isset($_SESSION['id']) || $CFG->DEVELOPER) ) {
    unset($_SESSION["admin"]);
    header('HTTP/1.x 404 Not Found');
    die();
}

if ( isset($_POST['passphrase']) ) {
    unset($_SESSION["admin"]);
    if ( $_POST['passphrase'] == $CFG->adminpw ) {
        $_SESSION["admin"] = "yes";
        error_log("Admin login IP=".$_SERVER["REMOTE_ADDR"].
            isset($_SESSION['id']) ? " id=". $_SESSION['id'].' email='.$_SESSION['email'] : " developer mode");
    } else {
        error_log("Admin bad pw IP=".$_SERVER["REMOTE_ADDR"].
            isset($_SESSION['id']) ? " id=". $_SESSION['id'].' email='.$_SESSION['email'] : " developer mode");
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    return;
}

if ( count($_POST) > 0 ) {
    unset($_SESSION["admin"]);
    header('HTTP/1.x 404 Not Found');
    die();
}

if ( ! isset($_SESSION['admin']) ) {
  headerContent();
  startBody();
?>
<form method="post">
<label for="passphrase"><br/>
<input type="password" name="passphrase" size="80">
</label>
<input type="submit">
</form>

<?php 
    footerContent();
    return;
}

