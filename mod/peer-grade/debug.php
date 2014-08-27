<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

// Sanity checks
$LTI = \Tsugi\Core\LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));
if ( ! $USER->instructor ) die("Instructor only");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

$OUTPUT->togglePre("Session data",safe_var_dump($_SESSION));

?>
<form method="post">
<input type="submit" name="doDone" onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Done">
</form>
<?php
flush();

$OUTPUT->footer();


