<?php
require_once "../config.php";
require_once "parse.php";
require_once "util.php";

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

function percent($x) {
    return sprintf("%.1f%%", $x * 100);
}

$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;

if ( SettingsForm::isSettingsPost() ) {
    // Validate settings...
    if ( isset($_POST['tries']) && $_POST['tries'] != '' && ! is_numeric($_POST['tries']) ) {
        $_SESSION['error'] = __('Tries must be numeric');
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }
    if ( isset($_POST['delay']) && $_POST['delay'] != '' && ! is_numeric($_POST['delay']) ) {
        $_SESSION['error'] = __('Delay must be numeric');
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }
    SettingsForm::handleSettingsPost();
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Remember the default file name
if ( isset($_GET['quiz']) && $USER->instructor ) {
    $_SESSION['default_quiz'] = $_GET['quiz'];
}

// Load the settings from defaults on first launch
$LAUNCH->link->settingsDefaultsFromCustom(array('tries', 'delay', 'title', 'instructions'));

$password = Settings::linkGet('password');
if ( strlen(U::get($_POST, "password", '')) > 0  ) {
    $_SESSION['assignment_password'] = U::get($_POST, "password");
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Get the settings
$max_str = Settings::linkGet('tries');
$max_tries = 0;
if ( is_numeric($max_str) ) $max_tries = $max_str+0;
if ( $max_tries < 1 ) $max_tries = 1;
$delay_str = Settings::linkGet('delay');
$delay = 0;
if ( is_numeric($delay_str) ) $delay = $delay_str+0;

$password_ok = strlen($password) < 1 || U::get($_SESSION,'assignment_password') == $password;

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

$when = 0;
$tries = 0;
if ( $tries == 0 && method_exists($RESULT, 'getAttempts') ) {
    $attempts = $RESULT->getAttempts();
    if ( is_object($attempts) ) {
        $when_raw = $attempts->attempted_at ?? $when;
        if ( is_numeric($when_raw) ) $when = $when_raw + 0;
        $tries_raw = $attempts->attempts ?? $tries;
        if ( is_numeric($tries_raw) ) $tries = $tries_raw + 0;
    }
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

    $result = array("when" => time(), "tries" => $tries+1, "submit" => $_POST);
    $RESULT->setJson(json_encode($result));
    
    // TODO: Remove this test a while after 2024-09-17
    if ( method_exists($RESULT, 'recordAttempt') ) $RESULT->recordAttempt();

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


$menu = false;
$files = get_quiz_files();
if ( $USER->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    $menu->addLeft('Student Data', 'grades.php');

    $submenu = new \Tsugi\UI\Menu();
    $submenu->addLink('Settings', '#', /* push */ false, SettingsForm::attr());
    if ( $files && count($files) >= 1 ) $submenu->addLink('Load Quiz', 'old_configure.php');
    $submenu->addLink('Edit Quiz', 'configure');
    $submenu->addLink('Export QTI', 'export.php');
    $submenu->addLink('Print', 'index?print=yes', /* push */ false, 'target="_blank" rel="noopener noreferrer" aria-label="Print quiz (opens in new tab)"');
    $submenu->addLink('Send Grade', 'sendgrade.php');

    if ( $CFG->launchactivity ) {
        $submenu->addLink('Analytics', 'analytics');
    }
    $menu->addRight('Instructor', $submenu);
}

// View
$OUTPUT->header();
?>
<link rel="stylesheet" type="text/css" href="css/feedback.css">
<link rel="stylesheet" media="screen" href="main.css" />
<link rel="stylesheet" media="print" href="print.css" />
<?php
$do_print = U::get($_GET, 'print', 'no') == 'yes';
$OUTPUT->bodyStart();
if ( ! $do_print ) {
$OUTPUT->topNav($menu);
$OUTPUT->welcomeUserCourse();

// Settings button and dialog

SettingsForm::start();
SettingsForm::text('password',__('Set a password to protect this assignment'));
SettingsForm::text('tries',__('The number of tries allowed for this quiz.  Leave blank or set to 1 for a single try.'));
SettingsForm::text('delay',__('The number of seconds between retries.  Leave blank or set to zero to allow immediate retries.'));
SettingsForm::text('title',__('Add a title for this quiz.'));
SettingsForm::textarea('instructions',__('Add any instructions for this quiz.'));
SettingsForm::dueDate();
SettingsForm::end();


$OUTPUT->flashMessages();

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

if ( ! $password_ok ) {
?>
<p>
This quiz is password protected, please enter the instructor provided password to
unlock this assignment.
</p>
<p>
<form method="post">
    <label for="quiz_password">Password:</label>
    <input type="password" name="password" id="quiz_password">
    <input type="submit" value="<?= htmlspecialchars(__('Submit')) ?>">
</form>
<?php
    $OUTPUT->footer();
    return;
}

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

}

$title = Settings::linkGet('title');
if ( is_string($title) && strlen($title) > 0 ) {
    echo("<center>\n$title\n</center>\n");
    echo("&nbsp;<br/>\n");
}

if ( $do_print ) {
?>
<p>
Name:  ___________________________________________________________
</p>
<?php
}

$instructions = Settings::linkGet('instructions');
if ( is_string($instructions) && strlen($instructions) > 0 ) {
    echo("<p>\n$instructions\n</p>\n");
}

// parse the GIFT questions
$questions = array();
$errors = array();
parse_gift($gift, $questions, $errors);

?>
<form method="post" aria-label="Quiz questions">
<ol id="quiz" role="list">
</ol>
<?php
if ( ! $do_print && ( $ok || $USER->instructor ) ) {
    echo('<input type="submit" value="'.htmlspecialchars(__('Submit quiz')).'">'."\n");
}
?>
</form>

<?php

/*
$qj = json_encode($questions);
echo("<pre>\n");
var_dump($attempt);
var_dump($errors);
echo(htmlentities(LTI::jsonIndent($qj)));
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
$OUTPUT->footerEnd();
