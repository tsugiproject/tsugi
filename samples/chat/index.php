<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// This is a very minimal index.php - just enough to launch
// chatlist.php with the PHPSESSIONID parameter
session_start();

// Retrieve the launch data if present
$LTI = requireData(array('user_id', 'result_id', 'role','link_id'));

?>
<html><head>
<script type="text/javascript" 
src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
</head>
<body>
<p>
<a style="color:grey" href="chatlist.php" target="_blank">Launch chatlist.php</a>
</p>
</body>
