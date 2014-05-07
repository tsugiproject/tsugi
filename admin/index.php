<?php 
define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

require_once("../pdo.php");
require_once("../lib/lms_lib.php");

headerContent();
startBody();
topNav();
?>
<h1>Welcome Adminstrator</h1>
<ul>
<li><a href="upgrade.php" target="_new">Upgrade Database</a></li>
<li><a href="../core/key/index.php">Manage Access Keys</a></li>
</ul>
<?php
footerContent();

