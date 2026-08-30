<?php

use \Tsugi\Util\U;
use \Tsugi\UI\Output;

require_once $CFG->dirroot."/admin/admin_util.php";

// Site menu callbacks (e.g. PY4E buildMenu) assume a full schema; use Tsugi default in admin.
unset($CFG->top_menu_callback);
Output::clearTopNavSession();

$REDIRECTED = false;
$rest_path = U::rest_path();

if ( $CFG->adminpw === false ) {
    unset($_SESSION["admin"]);
    die('Please set $CFG->adminpw to a plaintext or hashed string');
}

// Make sure we have an initialized database before sending to login
try {
    define('PDO_WILL_CATCH', true);
    $PDOX = \Tsugi\Core\LTIX::getConnection();
    $stmt = $PDOX->queryReturnError("SELECT key_id FROM {$CFG->dbprefix}lti_key  LIMIT 1");
    $havedatabase = $stmt->success;
} catch(\PDOException $ex){
    $havedatabase = false;
}

if ( $havedatabase && $CFG->google_client_id && ! isLoggedIn() ) {
    \Tsugi\Controllers\Login::setReturnUrl($rest_path->full);
    Output::doRedirect(\Tsugi\Controllers\Login::loginUrl());
    return;
}

if ( ! adminEmailAllowed() ) {
    unset($_SESSION["admin"]);
    if ( isset($_POST['passphrase']) ) {
        error_log("Admin unlock denied email=".U::get($_SESSION, 'email', '').
            " IP=".$_SERVER["REMOTE_ADDR"]);
        U::flashError('This account is not allowed to unlock admin');
        $redirect = U::addSession(U::reconstruct_query($rest_path->current));
        Output::doRedirect($redirect);
        $REDIRECTED = true;
        return;
    }
}

if ( isset($_POST['passphrase']) ) {
    if ( ! \Tsugi\Controllers\Tool::csrfOk() ) {
        U::flashError('Missing or invalid CSRF token');
        $rest_path = \Tsugi\Util\U::rest_path();
        $redirect = U::addSession(U::reconstruct_query($rest_path->current));
        Output::doRedirect($redirect);
        $REDIRECTED = true;
        return;
    }
    if ( adminUnlockBanned() ) {
        U::flashError('Too many failed admin unlock attempts. Try again in 5 minutes.');
        $redirect = U::addSession(U::reconstruct_query($rest_path->current));
        Output::doRedirect($redirect);
        $REDIRECTED = true;
        return;
    }
    unset($_SESSION["admin"]);
    $apw = $CFG->adminpw;
    $phrase = $_POST['passphrase'];
    $hash = 'sha256:'.lti_sha256($phrase);
    if ( (strpos($apw, 'sha256:') === false && $phrase === $apw ) ||
       (strpos($apw, 'sha256:') === 0 && $hash === $apw ) ) {

        adminUnlockClearFails();
        $_SESSION["admin"] = "yes";
        session_regenerate_id(true);
        error_log("Admin login IP=".$_SERVER["REMOTE_ADDR"].
            (isLoggedIn() ? " id=".loggedInUserId().' email='.U::get($_SESSION, 'email', '') : " developer mode"));
    } else {
        $locked = adminUnlockRecordFail();
        error_log("Admin bad pw IP=".$_SERVER["REMOTE_ADDR"].
            (isLoggedIn() ? " id=".loggedInUserId().' email='.U::get($_SESSION, 'email', '') : " developer mode").
            ($locked ? " locked=5m" : ""));
        if ( $locked ) {
            U::flashError('Too many failed admin unlock attempts. Try again in 5 minutes.');
        }
    }
    $rest_path = \Tsugi\Util\U::rest_path();
    $redirect = U::reconstruct_query($rest_path->current);
    $redirect = U::addSession($redirect);

    Output::doRedirect($redirect);
    $REDIRECTED = true;
    return;
}

if ( isset($_SESSION['admin']) ) return;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
if ( ! adminEmailAllowed() ) {
    echo('<p>This account is not allowed to unlock admin.</p>'."\n");
    $OUTPUT->footer();
    return;
}
if ( adminUnlockBanned() ) {
    echo('<p>Too many failed admin unlock attempts. Try again in 5 minutes.</p>'."\n");
    $OUTPUT->footer();
    return;
}
?>
<form method="post" action="<?= htmlspecialchars($CFG->wwwroot) ?>/admin/">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<label for="passphrase">Admin Unlock:<br/>
<input type="password" autocomplete="off" name="passphrase" size="80">
</label>
<input type="submit">
</form>

<?php
$OUTPUT->footer();

