<?php
define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

setcookie("adminmenu","true", 0, "/");

\Tsugi\Core\LTIX::getConnection();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
require_once("sanity-db.php");
?>
<h1>Welcome Adminstrator</h1>
<ul>
<li><a href="upgrade.php" target="_new">Upgrade Database</a></li>
<li><a href="nonce.php" target="_new">Check Nonces</a></li>
<li><a href="context/index.php">View Contexts</a></li>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="key/index.php">Manage Access Keys</a></li>
<?php } ?>
<li><a href="install/index.php">Manage Installed Modules</a></li>
</ul>
<?php
$OUTPUT->footer();

