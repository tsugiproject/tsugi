<?php
require_once "../../config.php";
require_once "parse.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

function percent($x) {
    return sprintf("%.1f%%", $x * 100);
}

$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Get the settings
$max_tries = Settings::linkGet('tries')+0;
if ( $max_tries < 1 ) $max_tries = 1;
$delay = Settings::linkGet('delay')+0;

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

// Load the previous attempt
$attempt = json_decode($RESULT->getJson());
$when = 0;
$tries = 0;
if ( $attempt && is_object($attempt) ) {
    if ( isset($attempt->when) ) $when = $attempt->when + 0;
    if ( isset($attempt->tries) ) $tries = $attempt->tries + 0;
}

// Decide if it is OK to submit this quiz
$ok = true;
$why = '';
if ( $tries >= $max_tries ) {
    $ok = false;
    $why = 'This quiz can only be attempted ('.$max_tries.') time(s).';
} else if ( $when > 0 && ($when + $delay) > time() ) {
    $ok = false;
    $why = 'You cannot retry this quiz for '.SettingsForm::getDueDateDelta(($when + $delay) - time());
}

$oldgrade = $RESULT->grade;
if ( count($_POST) > 0 ) {
    if ( $questions == false ) {
        $_SESSION['error'] ='Internal error: No questions';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    if ( $USER->instructor || $ok ) {
        // No problem
    } else {
        // No error message in session because status is always displayed
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $_SESSION['gift_submit'] = $_POST;
    $quiz = make_quiz($_POST, $questions, $errors);

    $gradetosend = $quiz['score']*1.0;
    $scorestr = "Your score of ".percent($gradetosend)." has been saved.";
    if ( $dueDate->penalty > 0 ) {
        $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
        $scorestr = "Effective Score = $gradetosend after ".percent($dueDate->penalty)." late penalty";
    }
    if ( $oldgrade > $gradetosend ) {
        $scorestr = "New score of ".percent($gradetosend)." is < than previous grade of ".percent($oldgrade).", previous grade kept";
        $gradetosend = $oldgrade;
    }

    $result = array("when" => time(), "tries" => $tries+1, "submit" => $_POST);
    $RESULT->setJson(json_encode($result));

    // Use LTIX to send the grade back to the LMS.
    $debug_log = array();
    $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
    $_SESSION['debug_log'] = $debug_log;

    if ( $retval === true ) {
        $_SESSION['success'] = $scorestr;
    } else if ( is_string($retval) ) {
        $_SESSION['error'] = "Grade not sent: ".$retval;
    } else {
        echo("<pre>\n");
        var_dump($retval);
        echo("</pre>\n");
        die();
    }

    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View
$OUTPUT->header();
$OUTPUT->bodyStart();

// Settings button and dialog

// echo('<span style="position: fixed; right: 10px; top: 5px;">');
echo('<span style="float: right; margin-bottom: 10px;">');
if ( $USER->instructor ) {
    echo('<a href="configure.php" class="btn btn-default">Edit Quiz Content</a> ');
    echo('<a href="grades.php" target="_blank"><button class="btn btn-info">Grade detail</button></a> '."\n");
}
$OUTPUT->exitButton();
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::text('tries',__('The number of tries allowed for this quiz.  Leave blank or set to 1 for a single try.'));
SettingsForm::text('delay',__('The number of seconds between retries.  Leave blank or set to zero to allow immediate retries.'));
SettingsForm::dueDate();
SettingsForm::done();
SettingsForm::end();

$OUTPUT->welcomeUserCourse();

$OUTPUT->flashMessages();

// Clean up the JSON for presentation
if ( $gift === false || strlen($gift) < 1 ) {
    echo('<p class="alert-warning" style="clear:both;">This quiz has not yet been configured</p>'."\n");
    $OUTPUT->footer();
    return;
}

if ( $RESULT->grade > 0 ) {
    echo('<p class="alert alert-info" style="clear:both;">Your current grade on this assignment is: '.percent($RESULT->grade).'</p>'."\n");
}

if ( ! $ok ) {
    if ( $USER->instructor ) {
        echo('<p class="alert alert-info" style="clear:both;">'.$why.' (except for the fact that you are the instructor)</p>'."\n");
    } else {
        echo('<p class="alert alert-danger" style="clear:both;">'.$why.'</p>'."\n");
    }
}

// parse the GIFT questions
$questions = array();
$errors = array();
parse_gift($gift, $questions, $errors);

?>
<form method="post">
<ol id="quiz">
</ol>
<?php
if ( $ok || $USER->instructor ) {
    echo('<input type="submit">'."\n");
}
?>
</form>

<?php

/*
$qj = json_encode($questions);
echo("<pre>\n");
var_dump($attempt);
var_dump($errors);
echo(htmlent_utf8(jsonIndent($qj)));
echo("</pre>\n");
*/

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
                template = TEMPLATES[type];
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
    }).fail( function() { alert('Unable to load quiz data'); } );
});
</script>

<?php
$OUTPUT->footerStart();
