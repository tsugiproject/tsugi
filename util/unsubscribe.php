<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Core\Mail;

$id = false;
$token = false;
if ( isset($_POST['id']) && isset($_POST['token']) ) {
    $id = $_POST['id'] + 0;
    $token = $_POST['token'];
    error_log("Unsubscribe: $id, $token");
    echo('You are unsubscribed. Thank you.');
    // TODO: Actually unsubscribe
    return;
}

if ( isset($_GET['id']) && isset($_GET['token']) ) {
    $id = $_GET['id'] + 0;
    $token = $_GET['token'];
}

if ( strlen($token) < 1 ) $token = false;

if ( $id === false || $token === false ) {
    error_log("Unsubscribe missing id or token");
    echo("Unsubscribe process requires both a 'id' and 'token parameter.");
    return;
}
/*
require_once("db.php");
require_once("sqlutil.php");

$sql = "SELECT email,first,last,identity FROM Users WHERE id=$id";
$row = retrieve_one_row($sql);
if ( $row === false ) {
    error_log("Unsubscribe user $id missing");
    echo("Sorry, user $id not found");
    return;
}

require_once("mail/maillib.php");
$check = Mail::computeCheck($row[3]);
if ( $token != $check ) {
    echo("Sorry, token is not valid ");
    error_log("Unsubscribe bad token=$token check=$check");
    if ( isset($_SESSION["admin"]) ) echo($check);
    return;
}

// We are past all the checks...
if ( isset($_POST['id']) ) {
    $sql = "UPDATE Users SET subscribe=-1 WHERE id=$id";
    $result = run_mysql_query($sql);
    echo('You are unsubscribed. Thank you.');
    error_log("Unsubscribed is=$id");
    return;
}
*/

?>
<h2>Unsubscribing from E-Mail <?php echo($CFG->maildomain); ?></h2>
<p>If you want to unsubscribe from e-mail from
<a href="<?php echo($CFG->wwwroot); ?>"><?php echo($CFG->servicename); ?></a> press
"Unsubscribe" below.
</p>
<form method="post" action="unsubscribe">
  <input type="hidden" name="id" value="<?php echo($id); ?>">
  <input type="hidden" name="token" value="<?php echo(htmlencode($token)); ?>">
  <input type="submit" value="Unsubscribe">
</form>
<p>
You can re-subscribe later if you like.
</p>
