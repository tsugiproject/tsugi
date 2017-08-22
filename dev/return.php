<?php
// tsugi.php already included config.php

session_start();

$success = '';
$error = '';
if ( isset($_GET['lti_log']) ) {
    $success .= "Log Message:" . $_GET['lti_log'] . "<br/>\n";
    error_log("LTI Log: ".$_GET['lti_log']);
}
if ( isset($_GET['lti_msg']) ) {
    $success .= "Message:" . $_GET['lti_msg'] . "<br/>\n";
    error_log("LTI Log: ".$_GET['lti_msg']);
}
if ( isset($_GET['lti_errorlog']) ) {
    $error .= "Error Log Message:" . $_GET['lti_errorlog'] . "<br/>\n";
    error_log("LTI Error Log: ".$_GET['lti_errorlog']);
}
if ( isset($_GET['lti_errormsg']) ) {
    $error .= "Error Message:" . $_GET['lti_errormsg'] . "<br/>\n";
    error_log("LTI Error Log: ".$_GET['lti_errormsg']);
}
$_SESSION['success'] = "Back from the tool";
if ( strlen($error) > 0 ) $_SESSION['error'] = $error;
if ( strlen($success) > 0 ) $_SESSION['success'] = $error;
header('Location: '.addSession('index'));
