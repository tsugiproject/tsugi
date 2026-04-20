<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("settings_util.php");
session_start();

if ( ! isLoggedIn() ) {
    $_SESSION['login_return'] = $CFG->wwwroot . '/settings';
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

LTIX::getConnection();

$key_count = settings_key_count();

$sql = "SELECT count(C.context_id) AS count
        FROM {$CFG->dbprefix}lti_context AS C
        LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
        WHERE C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID ) 
         OR C.user_id = :UID";

$course_count = 0;
$uid = loggedInUserId();
if ( $uid ) {
    $row = $PDOX->rowDie($sql, array(':UID' => $uid));
    $course_count = U::get($row, 'count', 0);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>My Settings</h1>
<p>This page is for instructors to manage their courses and the use of these
applications in their courses.
</p>
<ul>
<li><p><a href="context/" aria-label="View My Contexts (Courses) - <?= (int)$course_count ?> context(s)">View My Contexts (Courses)</a>
(<?= (int)$course_count ?>)
</p>
</li>
<?php if ( $CFG->providekeys ) { ?>
<li><p><a href="key" aria-label="Manage LMS Access Keys - <?= (int)$key_count ?> key(s)">Manage LMS Access Keys</a>
(<?= (int)$key_count ?>)<br/>
These tools can be integrated into Learning Management Systems
that support the Learning Tools Interoperability specification.
</p>
</li>
<?php } ?>
<li><p><a href="expire/" aria-label="Manage Data Expiry">Manage Data Expiry</a>
<br/>
This allows you to manage Personally Identifiable Information (PII) for your learners in this system.
</p>
</li>
<li><p><a href="encrypt" aria-label="Encrypt Strings">Encrypt Strings</a>
<br/>
Use this tool to encrypt strings using LTIX encryption methods. Note: This tool only supports encryption - decryption is only available to administrators.
</p>
</li>
<li><p><a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/cc/" target="_blank" rel="noopener noreferrer" aria-label="Download a copy of this course as an IMS Common Cartridge (opens in new window)">Download a copy of this course as an IMS Common Cartridge</a>
</br>
You can import this content into an LMS like Sakai, Canvas, Blackboard, D2L or Moodle.
</p>
</li>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<li><p>(Experimental) <a href="gclass_login" aria-label="Connect to Google Classroom">Connect to Google Classroom</a>
<?php
$count = U::get($_SESSION,'gc_count');
if ( $count ) {
    echo('(Connected to ' . (int)$count . ' classroom(s))');
} else {
    echo('(Not connected)');
}
?>
<br/>
These 
<?php
if ( isset($_SESSION['gc_count']) ) {
    echo('<a href="../store" aria-label="Browse tools in the store">tools</a>');
} else {
    echo('tools');
}
?>
 can be used in Google Classroom courses.
</p>
</li>
<li>
<p>
<a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer" aria-label="Manage my Google Account (opens in new window)">Manage my Google Account</a> (new window)<br/>
Use this page to view and manage which applications (including this one) that have access to your
Google information.
</p>
</li>
<?php } ?>
</ul>
<p>If you are an administrator for the overall site, you
can visit the administrator dashboard.
</p>
<p>
<strong>Note:</strong> The modal popups in this screen work best in the FireFox browser.
<?php

$OUTPUT->footer();

