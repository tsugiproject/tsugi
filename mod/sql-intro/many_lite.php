<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Mersenne_Twister;

require_once "sql_util.php";

// Compute the stuff for the output
$code = $USER->id+$LINK->id+$CONTEXT->id;
$roster = makeRoster($code);
$shas = array();
foreach($roster as $i => $student) {
    $row = $student[0].$student[1].$student[2];
    $sha = strtoupper(bin2hex($row));
    $shas[] = $sha;
}
$sorted = $shas;
sort($sorted);
$goodsha = $sorted[0];

$oldgrade = $RESULT->grade;

if ( isset($_POST['sha1']) ) {
    if ( $_POST['sha1'] != $goodsha ) {
        $_SESSION['error'] = "Your code did not match";
        header('Location: '.addSession('index.php'));
        return;
    }

    $gradetosend = 1.0;
    $scorestr = "Your answer is correct, score saved.";
    if ( $dueDate->penalty > 0 ) {
        $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
        $scorestr = "Effective Score = $gradetosend after ".$dueDate->penalty*100.0." percent late penalty";
    }
    if ( $oldgrade > $gradetosend ) {
        $scorestr = "New score of $gradetosend is < than previous grade of $oldgrade, previous grade kept";
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

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

$url = curPageUrl();
$data_url = str_replace('index.php','roster_data.php',$url);

// echo($goodsha);
if ( $LINK->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($LINK->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<p>
<form method="post">
To get credit for this assignment, perform the instructions below and 
enter the code you get here: <br/>
<input type="text" size="80" name="sha1">
<input type="submit">
</form>
(Hint: starts with <?= substr($goodsha,0,3) ?>)<br/>
</p>
<h1>Instructions</h1>
<p>
This appication will read roster data in JSON format, parse the file, and
then produce an SQLite database that contains a User, Course, and Member table
and populate the tables from the data file.
</p>
<p>
You can base your solution on this code:
<a href="http://www.pythonlearn.com/code/roster.zip" target="_blank">
http://www.pythonlearn.com/code/roster.zip</a> - this code is incomplete
as you need to add code to store the <b>role</b> column in the <b>Member</b> 
table to complete the assignment.
</p>
<p>
Here is the URL for your data file:
<a href="roster_data.php" target="_blank">Data File</a>.
</p>
<p>
Once the inserts are done, run the following SQL command:
<pre>
SELECT hex(User.name || Course.title || Member.role ) AS X FROM 
    User JOIN Member JOIN Course 
    ON User.id = Member.user_id AND Member.course_id = Course.id
    ORDER BY X
</pre>
Find the <b>first</b> row in the resulting record set and enter the long string that looks like 
<b>53656C696E613333</b>.
</p>
