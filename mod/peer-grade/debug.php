<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Instructor only");

headerContent();
?>
</head>
<body>
<?php
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


