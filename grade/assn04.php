<?php

require_once "header.php";
use Goutte\Client;

line_out("Grading SI664 Assignment 4");

$url = getUrl('http://www.php-intro.com/assn/cart');
$grade = 0;

error_log("ASSN04 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

// http://api.symfony.com/2.3/Symfony/Component/BrowserKit.html
$client = new Client();

$crawler = $client->request('GET', $url);

// http://symfony.com/doc/current/components/dom_crawler.html
// http://api.symfony.com/2.3/Symfony/Component/DomCrawler/Crawler.html
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Looking for login link.");
$link = $crawler->selectLink('Log In')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Looking for the form with a 'Log In' submit button");
// http://api.symfony.com/2.3/Symfony/Component/BrowserKit
$form = $crawler->selectButton('Log In')->form();
// var_dump($form->getPhpValues());
line_out("Setting the account and pw fields in the form");
$form->setValues(array("account" => "bob", "pw" => "umsi"));
$crawler = $client->submit($form);

$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Looking for a green 'Logged in' message");
if ( strpos($html, 'Logged in') === false ) {
    error_out("Could not find 'Logged in'");
}
line_out("Looking for the form with an 'Update' submit button");
$form = $crawler->selectButton('Update')->form();
// var_dump($form->getPhpValues());
line_out("Setting the sugar, spice, and vanilla fields in the form");
$form->setValues(array("sugar" => "1", "spice" => "2", "vanilla" => "3"));
$crawler = $client->submit($form);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Checking to see if there was a POST redirect to a GET");
$method = $client->getRequest()->getMethod();
if ( $method != "get" ) {
    error_out('Expecting POST to Redirect to GET - found '.$method);
}

line_out("Looking for the absence of a green 'Logged in' message");
if ( strpos($html, 'Logged in') !== false ) {
    error_out("Should not have found 'Logged in'");
}

line_out("Looking for 'Order total: 19.05'");
if ( strpos($html, 'Order total: 19.05') === false ) {
    error_out("Could not find 'Order total: 19.05'");
}

$url = $client->getRequest()->getUri();
line_out("Doing a refresh (GET) of ".htmlentities($url));
$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Looking for the absence of a green 'Logged in' message");
if ( strpos($html, 'Logged in') !== false ) {
    error_out("Should not have found 'Logged in'");
}

line_out("Looking for 'Order total: 19.05'");
if ( strpos($html, 'Order total: 19.05') === false ) {
    error_out("Could not find 'Order total: 19.05'");
}

line_out("Looking for the form with an 'Update' submit button");
$form = $crawler->selectButton('Update')->form();
// var_dump($form->getPhpValues());
// Yes, we need an odd number...
$spice = rand(0,5) * 2 + 1;
line_out("Setting the sugar=0, spice=$spice, and vanilla=0 in the form");
$form->setValues(array("sugar" => "0", "spice" => $spice, "vanilla" => "0"));
$crawler = $client->submit($form);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Checking to see if there was a POST redirect to a GET");
$method = $client->getRequest()->getMethod();
if ( $method != "get" ) {
    error_out('Expecting POST to Redirect to GET - found '.$method);
}

line_out("Looking for the absence of a green 'Logged in' message");
if ( strpos($html, 'Logged in') !== false ) {
    error_out("Should not have found 'Logged in'");
}

line_out("Looking for 'Order total:'");
preg_match('/Order total: ([0-9.]*)/',$html,$matches);
// print_r($matches);
if ( count($matches) == 2 ) {
   $order_total = $matches[1] + 0.0;
   line_out("Found order total = ".$order_total." (".$matches[1].")");
}

$good_total = $spice * 2.25;
if ( $order_total != $good_total ) {
    error_out("Order total of $order_total does not match expected $good_total");
}

line_out("Looking for Logout link.");
$link = $crawler->selectLink('Logout')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");
$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Looking for login link.");
$link = $crawler->selectLink('Log In')->link();
$url = $link->getURI();

