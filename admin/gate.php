<?php

require_once("../lib/lms_lib.php");

if ( isset($_POST['passphrase']) ) {
    if ( $_POST['passphrase'] == $CFG->adminpw ) {
        $_SESSION["admin"] = "yes";
        error_log("Admin login successful IP=".$_SERVER["REMOTE_ADDR"]);
        die("Location: ".__FILE__);
        return;
    }
}

if ( count($_POST) > 0 ) {
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

