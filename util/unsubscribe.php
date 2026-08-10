<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;

LTIX::getConnection();

/**
 * Apply unsubscribe for a validated user (idempotent).
 * @return bool True when suppress/opt-out ran (or already opted out)
 */
function mail_unsubscribe_apply(array $row): bool {
    global $CFG, $PDOX;

    $id = (int) U::get($row, 'user_id', 0);
    if ( $id < 1 ) {
        return false;
    }

    $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}lti_user SET subscribe = -1, updated_at = NOW()
            WHERE user_id = :UID",
        array(':UID' => $id)
    );
    $profile_id = U::get($row, 'profile_id');
    if ( $profile_id !== null && $profile_id !== false && (int) $profile_id > 0 ) {
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}profile SET subscribe = -1, updated_at = NOW()
                WHERE profile_id = :PID",
            array(':PID' => (int) $profile_id)
        );
    }
    $email = U::get($row, 'email', '');
    if ( is_string($email) && $email !== '' && strpos($email, '@') !== false ) {
        MailService::suppress($email, 'unsubscribe');
    }
    error_log("Unsubscribed user_id=$id");
    return true;
}

$id = false;
$token = false;
if ( isset($_POST['id']) && isset($_POST['token']) ) {
    $id = $_POST['id'] + 0;
    $token = $_POST['token'];
} else if ( isset($_GET['id']) && isset($_GET['token']) ) {
    $id = $_GET['id'] + 0;
    $token = $_GET['token'];
}

if ( U::strlen($token) < 1 ) {
    $token = false;
}

$is_post = isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST';
$is_one_click = $is_post && MailService::isOneClickUnsubscribeRequest();

if ( $id === false || $id <= 0 || $token === false ) {
    error_log("Unsubscribe missing id or token");
    if ( $is_one_click ) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo('Bad request');
        return;
    }
    echo("Unsubscribe process requires both a 'id' and 'token' parameter.");
    return;
}

$row = $PDOX->rowDie(
    "SELECT user_id, email, profile_id, subscribe
        FROM {$CFG->dbprefix}lti_user
        WHERE user_id = :UID",
    array(':UID' => $id)
);
if ( $row === false || $row === null ) {
    error_log("Unsubscribe user $id missing");
    if ( $is_one_click ) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo('Not found');
        return;
    }
    echo("Sorry, user $id not found");
    return;
}

$check = MailService::computeCheck($id);
if ( !hash_equals($check, (string) $token) ) {
    error_log("Unsubscribe bad token for user=$id");
    if ( $is_one_click ) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo('Invalid token');
        return;
    }
    echo("Sorry, token is not valid ");
    if ( isset($_SESSION["admin"]) ) {
        echo($check);
    }
    return;
}

// POST: one-click (RFC 8058) or confirmation form — no login required.
if ( $is_post ) {
    mail_unsubscribe_apply($row);
    if ( $is_one_click ) {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo('OK');
        return;
    }
    echo('You are unsubscribed. Thank you.');
    return;
}

// GET: confirmation page (visible link flow).
?>
<h2>Unsubscribing from E-Mail <?php echo(htmlentities((string) $CFG->maildomain)); ?></h2>
<p>If you want to unsubscribe from e-mail from
<a href="<?php echo(htmlentities($CFG->wwwroot)); ?>"><?php echo(htmlentities((string) $CFG->servicename)); ?></a> press
"Unsubscribe" below.
</p>
<form method="post" action="unsubscribe">
  <input type="hidden" name="id" value="<?php echo((int) $id); ?>">
  <input type="hidden" name="token" value="<?php echo(htmlent_utf8($token)); ?>">
  <input type="submit" value="Unsubscribe">
</form>
<p>
You can re-subscribe later from your profile if you like.
</p>
