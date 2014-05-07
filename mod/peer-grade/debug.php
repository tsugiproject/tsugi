<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);
if ( ! $instructor ) die("Instructor only");

html_header_content();
html_start_body();
flash_messages();
welcome_user_course($LTI);

html_toggle_pre("Session data",safe_var_dump($_SESSION));

?>
<form method="post">
<input type="submit" name="doDone" onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Done">
</form>
<?php
flush();

html_footer_content();


