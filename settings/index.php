<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("settings_util.php");
session_start();

if ( ! U::get($_SESSION,'id') ) {
    $_SESSION['login_return'] = $CFG->wwwroot . '/settings';
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

LTIX::getConnection();

$key_count = settings_key_count();

$sql = "SELECT count(C.context_id)
        FROM {$CFG->dbprefix}lti_context AS C
        LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
        WHERE C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID ) 
         OR C.user_id = :UID";

$course_count = 0;
if ( U::get($_SESSION, 'id') ) {
    $row = $PDOX->rowDie($sql, array(':UID' => $_SESSION['id']));
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
<li><p><a href="context/">View My Contexts (Courses)</a>
(<?= $course_count ?>)
</p>
</li>
<?php if ( $CFG->providekeys ) { ?>
<li><p><a href="key">Manage LMS Access Keys</a>
(<?= $key_count ?>)<br/>
These tools can be integrated into Learning Management Systems
that support the IMS Learning Tools Interoperability specification.
</p>
</li>
<?php } ?>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<li><p><a href="gclass_login">Connect to Google Classroom</a>
<?php
$count = U::get($_SESSION,'gc_count');
if ( $count ) {
    echo('(Connected to '.$count.' classroom(s))');
} else {
    echo('(Not connected)');
}
?>
<br/>
These 
<?php
if ( isset($_SESSION['gc_count']) ) {
    echo('<a href="../store">tools</a>');
} else {
    echo('tools');
}
?>
 can be used in Google Classroom courses.
</p>
</li>
<li>
<p>
<a href="https://myaccount.google.com/security" target="_blank">Manage my Google Account</a> (new window)<br/>
Use this page to view and manage which applications (including this one) that have access to your
Google information.
</p>
</li>
<?php } ?>
</ul>
<p>If you are an administrator for the overall site, you
can visit the administrator dashboard.
</p>
<?php

$OUTPUT->footer();

