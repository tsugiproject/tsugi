<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

$LTI = \Tsugi\Core\LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
if ( $USER->instructor ) {
    echo('<span style="position: fixed; right: 10px; top: 5px;">');
    echo('<a href="grades.php" target="_blank"><button class="btn btn-primary">Grade detail</button></a> '."\n");
    $OUTPUT->settingsButton();
    echo('</span>');
}
if ( $USER->instructor ) {
    SettingsForm::start();
    SettingsForm::dueDate();
    SettingsForm::done();
    SettingsForm::end();
}

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

require("a02.php");

$OUTPUT->footer();


