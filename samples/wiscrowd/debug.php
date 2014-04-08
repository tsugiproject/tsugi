<?php
require_once "../../config.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Sanity checks
$LTI = requireData(array('user_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Instructor only");

headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

echo("<p>Debug dump of session data.</p>\n");
togglePre("Session data",safeVarDump($_SESSION));

?>
<!-- Note that sessionize() is needed in the onclick code because it is
  JavaScript and PHP does not automatially add the PHPSESSID to strings
  inside of JavaScript code. -->
<form method="post">
<input type="submit" name="doDone" 
  onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Done">
</form>
<?php

footerContent();


