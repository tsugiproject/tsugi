<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in.');
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame" 
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<h1>My Settings</h1>
<p>This page is for teachers using this site in their courses.
</p>
<ul>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="key">Manage LMS Access Keys</a></li>
<?php } ?>
<li><a href="context/">View My Contexts (Courses)</a></li>
<!--
<li>
  <a href="recent" title="Recent Logins" target="iframe-frame"
  onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl);" >
  Recent Logins 
  </a></li>
-->
</ul>
<?php

$OUTPUT->footer();

