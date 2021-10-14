<?php
use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../settings_util.php");
session_start();

if ( ! U::get($_SESSION,'id') ) {
    $login_return = U::reconstruct_query($CFG->wwwroot . '/settings/privacy');
    $_SESSION['login_return'] = $login_return;
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

\Tsugi\Core\LTIX::getConnection();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Privacy Management</h1>
<p>
  <a href="<?= $CFG->wwwroot ?>/settings" class="btn btn-default">My Settings</a>
</p>
<pre>
<?php
var_dump($_GET);
?>
</pre>
<?php

$OUTPUT->footer();
