<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// All the assignments we support
$assignments = array(
    'a02.php' => 'Howdy application', 
    'a03.php' => 'Guessing Game',
    'a04.php' => 'Rock, Paper, Scissors',
    'a05.php' => 'Shopping Cart',
    'a06.php' => 'CRUD - Tracks',
    'mid-f14-autos.php' => 'CRUD - Autos',
    'crud-videos.php' => 'CRUD - Videos'
);

// View
$OUTPUT->header();
$OUTPUT->bodyStart();

// Settings button and dialog

echo('<span style="position: fixed; right: 10px; top: 5px;">');
if ( $USER->instructor ) {
    echo('<a href="grades.php" target="_blank"><button class="btn btn-info">Grade detail</button></a> '."\n");
}
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::select("exercise", __('Please select an assignment'),$assignments);
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

if ( $assn && isset($assignments[$assn]) ) {
    require($assn);
} else {
    if ( $USER->instructor ) {
        echo("<p>Please use settings to select an assignment for this tool.</p>\n");
    } else {
        echo("<p>This tool needs to be configured - please see your instructor.</p>\n");
    }
}
        

$OUTPUT->footer();


