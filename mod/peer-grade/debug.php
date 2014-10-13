<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
if ( ! $USER->instructor ) die("Instructor only");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

$OUTPUT->togglePre("Session data",safe_var_dump($_SESSION));

?>
<form method="post">
<input type="submit" name="doExit" onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Exit">
</form>
<?php
flush();

$OUTPUT->footer();


