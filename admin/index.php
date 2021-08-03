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
    if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
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
   <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="iframe-spinner"><br/>
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame"
    onload="document.getElementById('iframe-spinner').style.display='none';">
   </iframe>
</div>
<h1>Administration Console</h1>
<?php
$recommended = '7.2.0';
echo("<p>\nCurrent PHP Version: ". phpversion(). "\n");
if ( version_compare(PHP_VERSION, $recommended) < 0 ) {
    echo(' - <span style="color: red;">Soon Tsugi will require a minimum version of PHP '.$recommended.".</span>\n");
}
echo("</p>\n");
?>
</p>
<ul>
<li>
  <a href="#" title="Upgrade Database" 
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'upgrade', _TSUGI.spinnerUrl, true); return false;" >
  Upgrade Database
  </a>
</li>
<li>
  <a href="#" title="Check Nonces"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'nonce', _TSUGI.spinnerUrl); return false;" >
  Check Nonces
  </a></li>
<li>
  <a href="#" title="Recent Logins"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'recent', _TSUGI.spinnerUrl); return false;" >
  Recent Logins
  </a></li>
<li>
  <a href="#" title="Check database size"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'dbsize.php', _TSUGI.spinnerUrl); return false;" >
  Check database size
  </a></li>
<li>
  <a href="#" title="Remove 12345 Data"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'clear12345', _TSUGI.spinnerUrl); return false;" >
  Remove 12345 Data
  </a></li>
<?php if ( isset($CFG->websocket_url) ) {?>
<li>
  <a href="#" title="Check Socket Server"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'sock-test', _TSUGI.spinnerUrl, true); return false;" >
  Check socket server at <?= htmlentities($CFG->websocket_url) ?>
  </a>
</li>
<?php } ?>
<li>
  <a href="#" title="Test E-Mail"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'testmail', _TSUGI.spinnerUrl); return false;" >
  Test E-Mail
  </a></li>
<li>
  <a href="#" title="Event Status"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'events', _TSUGI.spinnerUrl, true); return false;" >
  Event Status
  </a>
</li>
<li><a href="context/">View Contexts</a></li>
<li><a href="users/">View Users</a></li>
<li><a href="activity/">View Activity</a></li>
<li><a href="key">Manage Access Keys</a></li>
<li><a href="expire">Manage Data Expiry</a></li>
<li><a href="install">Manage Installed Modules</a></li>
<li><a href="external">Manage External Tools</a></li>
<li>
  <a href="#" title="Blob Status"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'blob_status', _TSUGI.spinnerUrl, true); return false;" >
  BLOB/File Status
  </a>
</li>
<li>
  <a href="#" title="Blob Migration"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'blob_move', _TSUGI.spinnerUrl, true); return false;" >
  BLOB/File Migration
  </a>
</li>
<li>
  <a href="#" title="Blob Cleanup"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'blob_clean', _TSUGI.spinnerUrl, true); return false;" >
  Unreferenced BLOB Cleanup
  </a>
</li>
</ul>
<p>
Best viewed with <a href="https://www.mozilla.org/en-US/firefox/" target="_new">FireFox</a> since 
Chrome tends to hang iframes in Modals.
</p>
<?php if ( $CFG->DEVELOPER ) { ?>
<p>Note: You have $CFG-&gt;DEVELOPER enabled. When this is enabled, there are developer-oriented
"testing" menus shown and the Admin links are more obvious.
You should set DEVELOPER to <b>false</b> for production systems exposed to end users.
</p>
<?php } ?>
<?php

$OUTPUT->footer();

