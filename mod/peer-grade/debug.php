<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Instructor only");

headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

togglePre("Session data",safeVarDump($_SESSION));

?>
<form method="post">
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Cancel">
</form>
<?php
flush();

footerContent();


