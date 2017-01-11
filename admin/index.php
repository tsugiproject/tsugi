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
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:400px" id="iframe-frame" 
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<h1>Welcome Adminstrator</h1>
<ul>
<li>
  <a href="upgrade.php" title="Upgrade Database" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
  Ugrade Database 
  </a>
<li>
  <a href="nonce.php" title="Check Nonces" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Check Nonces 
  </a></li>
<li>
  <a href="recent.php" title="Recent Logins" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Recent Logins 
  </a></li>
<li><a href="context/index.php">View Contexts</a></li>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="key/index.php">Manage Access Keys</a></li>
<?php } ?>
<li><a href="install/index.php">Manage Installed Modules</a></li>
</ul>
<?php
$OUTPUT->footer();

