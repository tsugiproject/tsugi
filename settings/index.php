<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("settings_util.php");
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in.');
}

LTIX::getConnection();

$key_count = settings_key_count();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame" 
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<h1>My Settings</h1>
<p>This page is for instructors to manage their courses and the use of these
applications in their courses.
</p>
<p>
<?php echo(settings_status($key_count)) ?>
</p>
<ul>
<?php if ( $CFG->providekeys ) { ?>
<li><a href="key">Manage LMS Access Keys</a>
( <?= $key_count ?> approved key(s) )
</li>
<?php } ?>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<li><a href="../gclass/login">Connect to Google Classroom</a>
( <?= count(U::get($_SESSION,'gc_courses')) ?> connected course(s) )
</li>
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
<p>If you are an administrator for the overall site, you
can visit the administrator dashboard.
</p>
<?php

$OUTPUT->footer();

