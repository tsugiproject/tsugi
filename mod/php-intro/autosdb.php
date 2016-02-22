<?php

require_once "../../config.php";
require_once "webauto.php";
require_once "makes.php";
use Goutte\Client;

line_out("Grading PHP-Intro Autos Database");
?>
<p>The specification for this assignment is:
<a href="http://www.php-intro.com/assn/autosdb/" target="_blank">http://www.php-intro.com/assn/autosdb/</a></p>
<?php

$grade = 0;
$passed = 0;
$title_once = false; // Only send the error once
$titlefound = false;
$account = "umsi@umich.edu";

$url = getUrl('http://www.php-intro.com/solutions/autosdb/index.php');
if ( $url === false ) return;

error_log("AutosDb ".$url);

line_out("Initial page ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$client->setMaxRedirects(5);

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval === true ) {
    $titlefound = true;
} else {
    $titlefound  = false;
    error_out($retval);
}

line_out("Looking for  an anchor tag with text of 'Please Log In' (case matters)");
$link = $crawler->selectLink('Please Log In')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");
$crawler = $client->request('GET', $url);
markTestPassed('login.php page retrieved');
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval === true ) {
    $titlefound = true;
} else {
    $titlefound  = false;
    error_out($retval);
}

// Doing a log in
line_out('Looking for the form with a value="Log In" submit button');
$form = $crawler->selectButton('Log In')->form();
line_out("-- this autograder expects the log in form field names to be:");
line_out("-- who and pass");
line_out("-- if your fields do not match these, the next tests will fail.");



line_out("Attempting a bad login.");
$form->setValues(array("who" => $account, "pass" => "meow123"));
$crawler = $client->submit($form);
markTestPassed('Submit bad login values to login.php');
// This one does not post redirect
// checkPostRedirect($client);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval === true ) {
    $titlefound = true;
} else {
    $titlefound  = false;
    error_out($retval);
}

line_out("Searching for 'Incorrect password'");
if ( stripos($html,'Incorrect password') > 0 ) {
    markTestPassed('Incorrect password handled correctly');
} else {
    error_out("Could not find 'Incorrect password'"); 
}

line_out("Attempting a good login.");
$form->setValues(array("who" => $account, "pass" => "php123"));
$crawler = $client->submit($form);
markTestPassed('Submit good login values to login.php');
checkPostRedirect($client);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$form = webauto_get_form_button($crawler,'Add');

line_out("");
line_out("-- You may want to remove rows from the autos  after");
line_out("-- a few runs so you don't get too many records");
line_out("");

line_out("-- this autograder expects the add form field names to be:");
line_out("-- make, year, and mileage");
line_out("-- if your fields do not match these, the next tests will fail.");

$year = rand(1970,2016);
$mileage = rand(10,350000);
$make = $car_makes[0];

line_out("Entering Make=$make, mileage=$mileage, year=$year");
$form->setValues(array("make" => $make, "mileage" => $mileage, "year" => $year));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
webauto_search_for_many($html, array($make, $year, $mileage) );

$form = webauto_get_form_button($crawler,'Add');
line_out("Leaving make blank to cause an error");
$form->setValues(array("mileage" => $mileage, "year" => $year));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
webauto_search_for($html,'Make is required');

$form = webauto_get_form_button($crawler,'Add');
line_out("Making mileage non-numeric to cause an error");
$form->setValues(array("make" => $make, "mileage" => $mileage, "year" => "fourtytwo"));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
webauto_search_for($html,'Mileage and year must be numeric');

$form = webauto_get_form_button($crawler,'Add');
line_out("Attempting html injection");
$make = "<b>".$car_makes[1]." Bold</b>";
$year = rand(1970,2016);
$mileage = rand(10,350000);
$form->setValues(array("make" => $make, "mileage" => $mileage, "year" => $year));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$goodmake = htmlentities($make);
webauto_search_for_many($html, array($goodmake, $year, $mileage) );
webauto_dont_want($html,$make);
line_out("Making sure you have not called htmlentities() twice");
webauto_dont_want($html,"&amp;lt");

$form = webauto_get_form_button($crawler,'Add');
line_out("Attempting SQL injection");
line_out("https://xkcd.com/327/");
$make = $car_makes[2]."'; DROP TABLE autos;'-- ?";
$year = rand(1970,2016);
$mileage = rand(10,350000);
$form->setValues(array("make" => $make, "mileage" => $mileage, "year" => $year));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
webauto_search_for_many($html, array($car_makes[2], 'DROP Table autos', $year, $mileage) );

line_out("Preparing to log out...");
$form = webauto_get_form_button($crawler,'logout');
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
webauto_search_for($html, "Please Log In");


$perfect = 26;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles or the student name is missing");
    error_out("so no grade was sent.");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);

