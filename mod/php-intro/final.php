<?php

require_once "../../config.php";
require_once "webauto.php";
require_once "words.php";
shuffle($WORDS);
use Goutte\Client;

line_out("Grading PHP-Intro Final: ".$title_plural);

$url = getUrl($reference_implementation);
if ( $url === false ) return;
$grade = 0;

error_log($title_plural." ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();
$client->setMaxRedirects(5);

// Make up some good submit data
$wcount = 1;
$submit = array();
$fieldlist = "";
$firstint = False;
$firstintfield = False;
$firststring = False;
$firststringfield = False;
foreach($fields as $field ) {
    if ( strlen($fieldlist) > 0 ) $fieldlist .= ', ';
    $fieldlist .= $field[1];
    $v2 = isset($field[3]) ? $field[3] : false;
    if ( $field[2] == 'i' ) {
        $submit[$field[1]] = rand(5,99);
        if ( $firstintfield === False ) $firstintfield = $field[1];
        if ( $firstint === False ) $firstint = $submit[$field[1]];
    } else if ( is_numeric($v2) ) {
        $submit[$field[1]] = substr(ucwords($WORDS[$wcount]),0,$v2+0);
        if ( $firststringfield === False ) $firststringfield = $field[1];
        if ( $firststring === False ) $firststring = $submit[$field[1]];
        $wcount++;
    } else {
        $submit[$field[1]] = ucwords($WORDS[$wcount]);
        if ( $firststringfield === False ) $firststringfield = $field[1];
        if ( $firststring === False ) $firststring = $submit[$field[1]];
        $wcount++;
    }
}

// Yes, one gigantic unindented try/catch block
$passed = 0;
$titlefound = true;
try {

$crawler = $client->request('GET', $url);

$html = $crawler->html();
markTestPassed('Index retrieved');
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval !== true ) {
    error_out($retval);
    $titlefound = false;
}

line_out("Looking for  an anchor tag with text of 'Please log in' (case matters)");
$link = $crawler->selectLink('Please log in')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");
$crawler = $client->request('GET', $url);
markTestPassed('login page retrieved');
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

// Doing a log in
line_out('Looking for the form with a value="Log In" submit button');
$form = $crawler->selectButton('Log In')->form();
line_out("-- this autograder expects the log in form field names to be:");
line_out("-- who and pass");
line_out("-- if your fields do not match these, the next tests will fail.");
$form->setValues(array("who" => "umsi@umich.edu", "pass" => "php123"));
$crawler = $client->submit($form);
markTestPassed('Submit to login.php');
checkPostRedirect($client);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);


line_out("Looking for anchor tag with 'Add New Entry' as the text");
$link = $crawler->selectLink('Add New Entry')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
markTestPassed('Retrieve add.php');
$OUTPUT->togglePre("Show retrieved page",$html);

// Add new fail
line_out('Looking for the form with a value="Add" submit button in add.php');
$form = $crawler->selectButton('Add')->form();
line_out("-- this autograder expects the form field names to be:");
line_out("-- ".$fieldlist);
line_out("-- if your fields do not match these, the next tests will fail.");
line_out("Entering valid data in all the fields so Add is successful.");
$form->setValues(array());
$crawler = $client->submit($form);
markTestPassed('Form submitted');
checkPostRedirect($client);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

line_out("Expecting 'All values are required'");
if ( strpos(strtolower($html), 'are required') !== false ) {
    markTestPassed('Found error message');
} else {
    error_out("Could not find 'All values are required' in add.php");
}

// Add new success
line_out('Looking for the form with a value="Add" submit button in add.php');
$form = $crawler->selectButton('Add')->form();
line_out("-- this autograder expects the form field names to be:");
line_out("-- ".$fieldlist);
line_out("-- if your fields do not match these, the next tests will fail.");
line_out("Causing Add error, leaving all fields blank.");
$form->setValues($submit);
$crawler = $client->submit($form);
markTestPassed('Form data submitted');
checkPostRedirect($client);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

line_out("Expecting 'Record added'");
if ( strpos(strtolower($html), 'record added') !== false ) {
    markTestPassed('Found success message after add');
} else {
    error_out("Could not find 'Record added'");
}


line_out("Looking '$firststring' entry in index.php");
$pos = strpos($html, $firststring);
if ( $pos > 1 ) {
    markTestPassed("Found '$firststring' entry in index.php");
} else {
    error_out("Could not find '$firststring' entry in index.php");
}
$pos2 = strpos($html, "edit.php", $pos);

line_out("Looking for edit.php link associated with '$firststring' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
$editlink = str_replace("&amp;","&",$editlink);
line_out("Retrieving ".$editlink."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
markTestPassed("Retrieved $editlink");
$OUTPUT->togglePre("Show retrieved page",$html);

line_out('Looking for the form with a value="Save" submit button');
$form = $crawler->selectButton('Save')->form();
line_out("Changing $firstintfield to be 42");
$xsubmit = $submit;
$xsubmit[$firstintfield] = 42;
$form->setValues($xsubmit);
$crawler = $client->submit($form);
markTestPassed("edit.php submitted");
$html = $crawler->html();
checkPostRedirect($client);
$OUTPUT->togglePre("Show retrieved page",$html);

line_out("Checking edit results");
if ( strpos(strtolower($html), '42') !== false ) {
    markTestPassed("edit.php results verified");
} else {
    error_out("Record did not seem to be updated'");
}

// Delete...
line_out("Looking for delete.php link associated with '$firststring' entry");
$pos = strpos($html, $firststring);
$pos2 = strpos($html, "delete.php", $pos);
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
$editlink = str_replace("&amp;","&",$editlink);
line_out("Retrieving ".$editlink."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
markTestPassed("Retrieved delete.php");
$OUTPUT->togglePre("Show retrieved page",$html);

// Do the Delete
line_out('Looking for the form with a value="Delete" submit button');
$form = $crawler->selectButton('Delete')->form();
$crawler = $client->submit($form);
markTestPassed("Submitted form on delete.php");
$html = $crawler->html();
checkPostRedirect($client);
$OUTPUT->togglePre("Show retrieved page",$html);

line_out("Making sure '$firststring' has been deleted");
if ( strpos($html,$firststring) > 0 ) {
    error_out("Entry '$firststring' not deleted");
} else {
    markTestPassed("Entry '$firststring' deleted");
}

line_out("Cleaning up old records...");
while (True ) {
    $pos2 = strpos($html, "delete.php");
    if ( $pos2 < 1 ) break;
    $pos3 = strpos($html, '"', $pos2);
    if ( $pos3 < 1 ) break;
    $editlink = substr($html,$pos2,$pos3-$pos2);
    $editlink = str_replace("&amp;","&",$editlink);
    line_out("Retrieving ".$editlink."...");

    $crawler = $client->request('GET', $editlink);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);

    // Do the Delete
    line_out('Looking for the form with a value="Delete" submit button');
    $form = $crawler->selectButton('Delete')->form();
    $crawler = $client->submit($form);
    checkPostRedirect($client);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);

    $passed--;  // Undo post redirect
}


} catch (Exception $ex) {
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
}

// There is a maximum 25 passes for this test
$perfect = 25;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles so this grade is not official");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);
