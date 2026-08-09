<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;

LTIX::getConnection();

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

if ( $id === false || $id <= 0 || $token === false ) {
    error_log("Unsubscribe missing id or token");
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
    echo("Sorry, user $id not found");
    return;
}

$check = MailService::computeCheck($id);
if ( !hash_equals($check, (string) $token) ) {
    echo("Sorry, token is not valid ");
    error_log("Unsubscribe bad token for user=$id");
    if ( isset($_SESSION["admin"]) ) {
        echo($check);
    }
    return;
}

if ( isset($_POST['id']) && isset($_POST['token']) ) {
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
    echo('You are unsubscribed. Thank you.');
    return;
}

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
