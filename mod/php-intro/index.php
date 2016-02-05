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
    'http_headers.php' => 'Exploring HTTP Headers',
    'a02.php' => 'Howdy application', 
    'guess.php' => 'Guessing Game',
    'rps.php' => 'Rock, Paper, Scissors',
    'a05.php' => 'Shopping Cart',
    'a06.php' => 'CRUD - Tracks',
    'mid-f14-autos.php' => 'CRUD - Autos',
    'crud-videos.php' => 'CRUD - Videos',
    'fin-f15-address.php' => 'CRUD 15 - Address',
    'fin-f15-tracks.php' => 'CRUD 15 - Tracks'
);

$oldsettings = Settings::linkGetAll();

$assn = Settings::linkGet('exercise');

// Get any due date information
$dueDate = SettingsForm::getDueDate();
// Let the assignment handle the POST
if ( count($_POST) > 0 && $assn && isset($assignments[$assn]) ) {
    require($assn);
    return;
}

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

if ( $assn && isset($assignments[$assn]) ) {
    try {
        require($assn);
    } catch (Exception $ex) {
        error_out("The autograder did not find something it was looking for in your HTML - test ended.");
        error_log($ex->getMessage());
        error_log($ex->getTraceAsString());
        $detail = 
            "Check the most recently retrieved page (above) and see why the autograder is uphappy.\n" .
            "\nThe detail below may only make sense if you look at the source code for the test.\n".
            'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
        $OUTPUT->togglePre("Internal error detail.",$detail);
    }
} else {
    if ( $USER->instructor ) {
        echo("<p>Please use settings to select an assignment for this tool.</p>\n");
    } else {
        echo("<p>This tool needs to be configured - please see your instructor.</p>\n");
    }
}
        

$OUTPUT->footer();


