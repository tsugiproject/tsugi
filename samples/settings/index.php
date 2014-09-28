<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

// Retrieve the launch data if present
$LTI = LTIX::requireData(array('user_id', 'role','context_id'));
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

// Handle the incoming post saving the settings form
if ( SettingsForm::handleSettingsPost() ) {
    $_SESSION['debug_log'] = Settings::getDebugArray();
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Handle our own manual set of the manual_key setting
if ( isset($_POST['manual_key']) ) {
    Settings::linkSet('manual_key', $_POST['manual_key']);
    $_SESSION['debug_log'] = Settings::getDebugArray();
    $_SESSION['success'] = "Setting updated";
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Start of the output
$OUTPUT->header();

// Start of the view
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

// Place the settings button in the upper right.
if ( $USER->instructor ) SettingsForm::button(true);

// Put out the hidden settings form using predefined UI routines
if ( $USER->instructor ) {
    SettingsForm::start();
    echo('<label for="sample_key">Please enter a string for "sample_key" below.<br/>'."\n");
    SettingsForm::text('sample_key');
    echo("</label>\n");
    SettingsForm::end();
}

echo("<h1>Settings Test Harness</h1>\n");
$OUTPUT->welcomeUserCourse();

if ( $USER->instructor ) {
    echo("<p>Press the settings button in the upper left to change the settings.</p>\n");
}

// Load the old values for the settings
$sk = Settings::linkGet('sample_key');
echo("<p>The currentsetting for sample_key is: <b>".htmlent_utf8($sk)."</b></p>\n");

$mk = Settings::linkGet('manual_key');
echo("<p>The currentsetting for manual_key is: <b>".htmlent_utf8($mk)."</b></p>\n");

// Lets show how to set a setting in our own code
if ( $USER->instructor ) {
?>
<form method="post">
Enter value for 'manual_key' setting:
<input type="text" name="manual_key" size="40" value="<?= htmlent_utf8($mk)?>"><br/>
<input type="submit" name="send" value="Update 'manual_key' setting">
</form>
<hr/>
<?php
}

if ( isset($_SESSION['debug_log']) && count($_SESSION['debug_log']) > 0) {
    echo("<p>Debug output from setting send:</p>\n");
    $OUTPUT->dumpDebugArray($_SESSION['debug_log']);
}
unset($_SESSION['debug_log']);

echo("\n<hr/>\n<pre>Global Tsugi Objects:\n\n");
var_dump($USER);
var_dump($CONTEXT);
var_dump($LINK);

echo("\n<hr/>\n");
echo("Session data (low level):\n");
echo(safe_var_dump($_SESSION));

$OUTPUT->footer();

