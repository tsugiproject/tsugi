<?php

use \Tsugi\Util\U;
use \Tsugi\UI\GoogleLoginHandler;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

header('Content-Type: text/html; charset=utf-8');
session_start();
session_regenerate_id(true);
error_log('Session in login '.session_id());

// Determine callback URL
$come_back = $CFG->wwwroot.'/lms/login';

// Process login with redirect callback
$result = GoogleLoginHandler::processLogin($come_back, function($result) {
    global $CFG;
    if ( isset($_SESSION['login_return']) ) {
        $url = $_SESSION['login_return'];
        unset($_SESSION['login_return']);
        return $url;
    } else if ( $result->did_insert ) {
        return $CFG->wwwroot.'/lms/profile';
    } else {
        return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
    }
});

// Handle errors
if ( $result->error ) {
    $_SESSION["error"] = $result->error;
    $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
    header('Location: '.$home.'/');
    return;
}

// Handle successful login redirect
if ( $result->success && $result->redirect_url ) {
    header('Location: '.$result->redirect_url);
    return;
}

// Display login form
$loginUrl = $result->login_url ? $result->login_url : GoogleLoginHandler::getLoginUrl($come_back);

$login_return = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
if ( isset($_SESSION['login_return']) ) $login_return = $_SESSION['login_return'];

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div style="margin: 30px">
<p>
We here at <?= $CFG->servicename ?> use Google Accounts as our sole login.
We do not want to spend a lot of time verifying identity, resetting passwords,
detecting robot-login storms, and other issues so we let Google do that hard work.
</p>
<form method="post">
    <input class="btn btn-warning" type="button"
    onclick="location.href='<?= $login_return ?>'; return false;" value="Cancel"
        style="height: 2.5em;"/>
    <a href="<?= $loginUrl ?>"><img src="<?= $CFG->staticroot ?>/img/google_signin_buttons/2x/btn_google_signin_dark_normal_web@2x.png"
      title="<?= htmlentities(__('Sign in with Google')) ?>"
      style="height: 3em;"></a>
</form>
<p>
So you must have a Google account and we will require your
name and email address to login.  We do not need and do not receive your password - only Google
will ask you for your password.  When you press login, you will be directed to the Google
authentication system where you will be given the option to share your
information with <?= $CFG->servicename ?>.
</p>
</div>
<?php
$OUTPUT->footer();
