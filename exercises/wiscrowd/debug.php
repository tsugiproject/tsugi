<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Instructor only");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

echo("<p>Debug dump of session data.</p>\n");
$OUTPUT->togglePre("Session data",$OUTPUT->safe_var_dump($_SESSION));

?>
<!-- Note that addSession() is needed in the onclick code because it is
  JavaScript and PHP does not automatially add the PHPSESSID to strings
  inside of JavaScript code. -->
<form method="post">
<input type="submit" name="doExit"
  onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Exit">
</form>
<?php

$OUTPUT->footer();


