<?php

require_once "../../config.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

session_start();
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));

// View 
headerContent();
?>
</head>
<body>
<?php
flashMessages();
welcomeUserCourse($LTI);

// Everyone gets 100%!
$retval = sendGrade(1.0);
if ( $retval === true ) {
    success_out("Grade sent to server.");
} else if ( is_string($retval) ) {
    error_out("Grade not sent: ".$retval);
} else {
    echo("<pre>\n");
    var_dump($retval);
    echo("</pre>\n");
}

togglePre("Session data",safeVarDump($_SESSION));

flush();

footerContent();

