<?php
require_once "../../config.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Sanity checks
$LTI = ltiRequireData(array('user_id', 'role','context_id'));
if ( ! $USER->instructor ) die("Instructor only");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
welcomeUserCourse();

echo("<p>Debug dump of session data.</p>\n");
$OUTPUT->togglePre("Session data",safe_var_dump($_SESSION));

?>
<!-- Note that sessionize() is needed in the onclick code because it is
  JavaScript and PHP does not automatially add the PHPSESSID to strings
  inside of JavaScript code. -->
<form method="post">
<input type="submit" name="doDone" 
  onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Done">
</form>
<?php

$OUTPUT->footer();


