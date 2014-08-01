<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Retrieve the launch data if present
$LTI = \Tsugi\Core\LTIX::requireData(array('user_id', 'result_id', 'role','link_id'));

// This is a very minimal index.php - just enough to launch
// chatlist.php with the PHPSESSIONID parameter
$OUTPUT->header();
$OUTPUT->bodyStart();
?>
<p>
<a style="color:grey" href="chatlist.php" target="_blank">Launch chatlist.php</a>
</p>
<?php
$OUTPUT->footer();
