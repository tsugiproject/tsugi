<?php
use \Tsugi\Util\U;
use \Tsugi\UI\GoogleLoginHandler;
use \Tsugi\Controllers\Login;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "config.php";

function login_redirect($path=false) {
    $url = Login::takeReturnUrl();
    if ( $url ) {
        header('Location: '.$url);
        return;
    }
    $configured = Login::configuredReturnUrl();
    if ( $configured ) {
        header('Location: '.$configured);
        return;
    }
    $home = Login::defaultHomeUrl();
    header('Location: '.($path ? rtrim($home, '/').'/'.$path : $home));
}

session_start();
session_regenerate_id(true);
error_log('Session in login '.session_id());

$come_back = Login::oauthRedirectUri();

// Check for Google client ID
if ( ! isset($CFG->google_client_id) || ! $CFG->google_client_id ) {
    echo("<p>"._m('You need to set $CFG->google_client_id in order to use Google\'s Login')."</p>\n");
    if ( strpos($CFG->wwwroot, '//localhost') !== false ) {
        echo("<p>"._m('There is no need to log in to do local adminstration or local development')."</p>\n");
    }
    die();
}

// Process login with redirect callback
$result = GoogleLoginHandler::processLogin($come_back, function($result) {
    return Login::returnAfterLogin($result, null, false);
});

// Handle errors
if ( $result->error ) {
    U::flashError($result->error);
    login_redirect();
    return;
}

// Handle successful login redirect
if ( $result->success && $result->redirect_url ) {
    header('Location: '.$result->redirect_url);
    return;
} else if ( $result->success ) {
    if ( $result->did_insert ) {
        login_redirect('profile');
    } else {
        login_redirect();
    }
    return;
}

// Display login form
$loginUrl = $result->login_url ? $result->login_url : GoogleLoginHandler::getLoginUrl($come_back);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$login_return = Login::cancelUrl();
?>
<div style="margin: 30px">
<p>
We here at <?php echo($CFG->servicename); ?> use Google Accounts as our sole login.
We do not want to spend a lot of time verifying identity, resetting passwords,
detecting robot-login storms, and other issues so we let Google do that hard work.
</p>
<form method="post">
    <a href="<?= $loginUrl ?>"><img src="<?= $CFG->staticroot ?>/img/google_signin_buttons/2x/btn_google_signin_dark_normal_web@2x.png"
    title="<?= htmlentities(__('Sign in with Google')) ?>"
    style="height: 3em;"></a>
    <input class="btn btn-warning" type="button" onclick="location.href='<?php echo($login_return); ?>'; return false;" value="Cancel" style="height: 2.5em;"/>
</form>
<p>
So you must have a Google account and we will require your
name and email address to login.  We do not need and do not receive your password - only Google
will ask you for your password.  When you press login, you will be directed to the Google
authentication system where you will be given the option to share your
information with <?php echo($CFG->servicename); ?>.
</p>
</div>
<?php
$OUTPUT->footer();
