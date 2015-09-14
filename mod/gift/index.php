<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "parse.php";
// require_once "score.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Get any due date information
$dueDate = SettingsForm::getDueDate();

// Load the quiz
$gift = $LINK->getJson();

// parse the quiz questions
$questions = false;
$errors = array("No questions found");
if ( strlen($gift) > 0 ) {
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
}


if ( count($_POST) > 0 ) {
    if ( $questions == false ) {
        $_SESSION['error'] ='Internal error: No questions';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $_SESSION['gift_submit'] = $_POST;
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

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

?>
<form method="post">
<ol id="quiz">
</ol>
<input type="submit">
</form>

<?php

$qj = json_encode($questions);
echo("<pre>\n");
var_dump($errors);
echo(htmlent_utf8(jsonIndent($qj)));
echo("</pre>\n");

$OUTPUT->footerStart();

require_once('templates.php');

?>
<script>
TEMPLATES = [];
$(document).ready(function(){
    $.getJSON('<?= addSession('quiz.php')?>', function(quiz) {
        window.console && console.log(quiz);
        for(var i=0; i<quiz.questions.length; i++) {
            question = quiz.questions[i];
            type = question.type;
            console.log(type);
            if ( TEMPLATES[type] ) {
                template = $TEMPLATES[type];
            } else {
                source  = $('#'+type).html();
                if ( source == undefined ) {
                    window.console && console.log("Did not find template for question type="+type);
                    continue;
                }
                template = Handlebars.compile(source);
                TEMPLATES[type] = template;
            }
            $('#quiz').append(template(question));


        }
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
