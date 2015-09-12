<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "parse.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

$oldsettings = Settings::linkGetAll();

// Get any due date information
$dueDate = SettingsForm::getDueDate();

// View
$OUTPUT->header();
$OUTPUT->bodyStart();

// Settings button and dialog

// echo('<span style="position: fixed; right: 10px; top: 5px;">');
echo('<span style="float: right">');
if ( $USER->instructor ) {
    echo('<a href="configure.php" class="btn btn-default">Configure Quiz</a> ');
    echo('<a href="grades.php" target="_blank"><button class="btn btn-info">Grade detail</button></a> '."\n");
}
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

$OUTPUT->flashMessages();

$OUTPUT->welcomeUserCourse();

$gift = $LINK->getJson();

// Clean up the JSON for presentation
if ( $gift === false || strlen($gift) < 1 ) {
    echo('<p class="alert-warning">This quiz has not yet been configured</p>'."\n");
    $OUTPUT->footer();
    return;
}

// parse the GIFT questions
$questions = array();
$errors = array();
parse_gift($gift, $questions, $errors);

$qj = json_encode($questions);

echo("<pre>\n");
var_dump($errors);
echo(htmlent_utf8(jsonIndent($qj)));
echo("</pre>\n");

$OUTPUT->footerStart();

require_once('templates.php');

?>
<script>
$(document).ready(function(){
    $.getJSON('<?= addSession('quiz.php')?>', function(quiz) {
        window.console && console.log(quiz);
/*
        var source  = $("#list-template").html();
        var template = Handlebars.compile(source);
        var context = {};
        context.loggedin = 
            <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        context.profiles = profiles;
        $('#list-area').replaceWith(template(context));
*/
    }).fail( function() { alert('getJSON fail'); } );
});
</script>

<?php
$OUTPUT->footerStart();
