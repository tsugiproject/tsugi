<?php 
define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

require_once("../pdo.php");
require_once("../lib/lms_lib.php");

$OUTPUT->header();
$OUTPUT->start_body();
$OUTPUT->top_nav();
?>
<h1>Welcome Adminstrator</h1>
<ul>
<li><a href="upgrade.php" target="_new">Upgrade Database</a></li>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="../core/key/index.php">Manage Access Keys</a></li>
<?php } ?>
</ul>
<?php
$OUTPUT->footer();

