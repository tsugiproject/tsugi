<?php
require_once "../../config.php";

session_start();
// Set up global values from session
require_once $CFG->dirroot."/lib/webauto.php";

?><html>
<head>
  <title>Automatic Web Grading Tool</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<? echo(togglePreScript()); ?>
</head>
<body style="font-family:sans-serif; background-color:#add8e6">
<?php do_analytics(); ?>

