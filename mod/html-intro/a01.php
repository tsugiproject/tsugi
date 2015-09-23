<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;

require_once $CFG->dirroot."/core/blob/blob_util.php";

$oldgrade = $RESULT->grade;
if ( isset($_FILES['html_01']) ) {

    $fdes = $_FILES['html_01'];
    $filename = isset($fdes['name']) ? basename($fdes['name']) : false;
     // Check to see if they left off a file
    if( $fdes['error'] == 4) {
        $_SESSION['error'] = 'Missing file, make sure to select all files before pressing submit';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $data = uploadFileToString($fdes, false);
    if ( $data === false ) {
        $_SESSION['error'] = 'Could not retrieve file data';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    if ( count($data) > 250000 ) {
        $_SESSION['error'] = 'Please upload a file less than 250K';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }
    
    // Put the data into session to allow us to process this in the GET request
    $_SESSION['html_data'] = $data;
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if ( $LINK->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($LINK->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<p>
<form name="myform" enctype="multipart/form-data" method="post" action="<?= addSession('index.php') ?>">
Please upload your file containing the HTML.
<p><input name="html_01" type="file"></p>
<input type="submit">
</form>
</p>
<?php

if ( ! isset($_SESSION['html_data']) ) return;

$data = $_SESSION['html_data'];
unset($_SESSION['html_data']);
echo("<pre>\n");
echo("Input HTML\n");
echo(htmlentities($data));
echo("\n");

// First validate using
// https://github.com/validator/validator/wiki/Service:-Input:-POST-body

$validator = 'https://validator.w3.org/nu/?out=json&parser=html5';
echo("Calling the validator $validator ... \n");
$return = Net::doBody($validator, "POST", $data, 'Content-type: text/html; charset=utf-8');
echo("Validator Output:\n");
echo(htmlentities(jsonIndent($return)));
/*
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
*/
