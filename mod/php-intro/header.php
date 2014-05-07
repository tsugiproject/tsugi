<?php
require_once "../../config.php";

session_start();
// Set up global values from session
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/lib/webauto.php";

?><html>
<head>
  <title>Automatic Web Grading Tool</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php echo(html_toggle_preScript()); ?>
</head>
<body style="font-family:sans-serif; background-color:#add8e6">
<?php html_do_analytics(); ?>

