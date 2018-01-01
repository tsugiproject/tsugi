<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

setcookie("adminmenu","true", time()+365*24*60*60, "/");

require_once("sanity.php");
$PDOX = false;
try {
    define('PDO_WILL_CATCH', true);
    $PDOX = \Tsugi\Core\LTIX::getConnection();
} catch(\PDOException $ex){
    $PDOX = false;  // sanity-db-will re-check this below
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
require_once("sanity-db.php");
?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<h1>Welcome Adminstrator</h1>
<ul>
<li>
  <a href="upgrade" title="Upgrade Database" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
  Upgrade Database
  </a>
</li>
<li>
  <a href="nonce" title="Check Nonces" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Check Nonces
  </a></li>
<li>
  <a href="recent" title="Recent Logins" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Recent Logins
  </a></li>
<li>
  <a href="clear12345" title="Remove 12345 Data" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Remove 12345 Data
  </a></li>
<li>
  <a href="events" title="Event Status" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
  Event Status
  </a>
</li>
<li><a href="context/">View Contexts</a></li>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="key">Manage Access Keys</a></li>
<?php } ?>
<li><a href="install">Manage Installed Modules</a></li>
</ul>
<?php if ( $CFG->DEVELOPER ) { ?>
<p>Note: You have $CFG-&gt;DEVELOPER enabled. When this is enabled, there are developer-oriented
"testing" menus shown and the Admin links are more obvious.
You should set DEVELOPER to <b>false</b> for production systems exposed to end users.
</p>
<?php } ?>
<?php

$OUTPUT->footer();

