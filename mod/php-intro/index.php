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

// All the assighments we support
$assignments = array('a02.php');

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
if ( $USER->instructor ) {
    echo('<span style="position: fixed; right: 10px; top: 5px;">');
    echo('<a href="grades.php" target="_blank"><button class="btn btn-info">Grade detail</button></a> '."\n");
    $OUTPUT->settingsButton();
    echo('</span>');
}
if ( $USER->instructor ) {
    SettingsForm::start();
    SettingsForm::select("exercise", __('Please select an assignment'),$assignments);
    SettingsForm::dueDate();
    SettingsForm::done();
    SettingsForm::end();
}

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

if ( $assn && in_array($assn, $assignments) ) {
    require($assn);
} else {
    if ( $USER->instructor ) {
        echo("<p>Please use settings to select an assignment for this tool.</p>\n");
    } else {
        echo("<p>This tool needs to be configured - please see your instructor.</p>\n");
    }
}
        

$OUTPUT->footer();


