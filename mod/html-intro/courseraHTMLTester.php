<?php

// require_once "../lib/header.php";
// require_once "header.php";
// use Goutte\Client;

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;

//require_once $CFG->dirroot."/core/blob/blob_util.php";


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













print("HTML5 Project\n");
$grade = 0;
echo ("\n\nYour grade is  " . $grade);


// http://symfony.com/doc/current/components/dom_crawler.html
// $client = new Client();

//Hardcoding for now...
$html = "<!DOCTYPE html><html lang=\"en\"><head><meta charset = \"UTF-8\"\><title></title></head><body><header><h1></h1> <nav> <a href=\"\">One</a><a href=\"\">Two</a><a href=\"\">Three</a><a href=\"\">Four</a></nav></header><section><section><section></section><section></section><footer></footer></body></html>";
// $html = "<!DOCTYPE html><html lang=\"en\"><head>";
//$html = file_get_contents('final.html');
$dom = new DOMDocument;
@$dom->loadHTML($html);

print("First check that major components are there.\n" . '<br>');

try {
    $nodes = $dom->getElementsByTagName('html');
    if ($nodes->length==1){
        print("Found html tag!\n" . '<br>');
        print("...making sure English language is specified!" . '<br>');

        foreach ($nodes as $p) //go to each section 1 by 1 
        {
            if ($p->getAttribute('lang')==="en"){
                print("...Found it!!\n" . '<br>');
                $grade+=1;
            }
            else{
                print("\n***Didn't find it!!\n" . '<br>');
            }
        }
        $grade+=1;
    }
    else
         print("***Did NOT find html tag!\n" . '<br>');
}catch(Exception $ex){
    error_log("***Did not find html tag!" . '<br>');
}

try {
    $nodes = $dom->getElementsByTagName('head');
    if ($nodes->length==1){
        print("Found head tag!\n");
        print("...looking for meta charset..." . '<br>');
        try {
            $nodes = $dom->getElementsByTagName('meta');
            foreach ($nodes as $p) //go to each section 1 by 1 
            {
                if ($p->getAttribute('charset')!=null){
                    print("...Found it!!\n");
                    $grade+=1;
                }
                else{
                    print("\n***Didn't find it!!\n");
                }
            }   
        } catch(Exception $ex){
            error_log("***Did not find meta tag!" . '<br>');
        }
        print("...looking for title...");
        try {
            $nodes = $dom->getElementsByTagName('title');
            if ($nodes->length==1){
                print("...Found it!!\n");
                $grade+=1;
            }
            else{
                print("\n***Didn't find it!!\n");
            }
        }catch(Exception $ex){
            error_log("***Did not find title tag!" . '<br>');
        }
    }
}catch(Exception $ex){
    error_log("***Did not find head tag!" . '<br>');
}
  
try {
    $nodes = $dom->getElementsByTagName('body');
    if ($nodes->length==1){
        print("Found body tag!\n" . '<br>');
        $grade+=1;
    }
    else
         print("***Did NOT find body tag or found more than one!\n" . '<br>');
}catch(Exception $ex){
    error_log("***Did not find body tag!");
}

try {
    $nodes = $dom->getElementsByTagName('header');
    if ($nodes->length==1){
        print("Found header tag!\n" . '<br>');
        $grade+=1;
    }
    else{
         print("***Did NOT find header tag or found more than one!\n" . '<br>');
     }
}catch(Exception $ex){
    error_log("***Did not find header tag!");
}

try {
    $nodes = $dom->getElementsByTagName('h1');
    if ($nodes->length==1){
        print("...Found h1 tag!\n" . '<br>');
        $grade+=1;
        foreach ($nodes as $node) {
            $h1 = $node;
        }
        if (trim(strtolower($h1->nodeValue) == 'colleen van lent')) {
            print(htmlentities('<h1> tag text not changed from example page') . '<br>');
        }
        else {
            print(htmlentities('<h1> tag formatted properly') . '<br>');
            $grade += 1;
        }
    }
    else{
         print("...***Did NOT find h1 tag or found more than one!\n" . '<br>');
     }
}catch(Exception $ex){
    error_log("***Did not find h1 tag!");
}

try {
    $nodes = $dom->getElementsByTagName('h2');
    if ($nodes->length === 3) {
        print(htmlentities('Found three <h2> tags') . '<br>');
        $grade += 1;
    }
    else {
        print(htmlentities('***Found more or less than three <h2> tags') . '<br>');
    }
}
catch(Exception $ex) {
    error_log(htmlentities('***Did not find any <h2> tags'));
}

try {
    $nodes = $dom->getElementsByTagName('nav');
    if ($nodes->length==1){
        print("...found nav tag!\n" . '<br>');
        $grade+=1;
    }
    else{
         print("...***Did NOT find nav tag or found more than one!\n" . '<br>');
     }
}catch(Exception $ex){
    error_log("***Did not find nav tag!");
}

try {
    $nodes = $dom->getElementsByTagName('section');
    if ($nodes->length==3){
        print("Found three section tags!\n" . '<br>');
        $grade+=1;
    }
    else
         print("***Did NOT find three sections tags\n" . '<br>');
}catch(Exception $ex){
    error_log("***Did not find 3 section tags!");
}

print("Searching for four links in the nav..." . '<br>');

try {
    $nav = $dom->getElementsByTagName('nav');
    $nav_links_all = array();
    foreach ($nav as $navlinks) {
        $navlinks = $navlinks->childNodes;
        $count=0;
        foreach ($navlinks as $link) {
            $nav_links_all[] = $link;
            if ($link->tagName === "a") {
                $count+=1;
                print("Found ". $count . '<br>');
            }
        }
        if ($count==4){
            print("....Found them!\n" . '<br>');
            if (trim(strtolower($nav_links_all[6]->nodeValue)) !== 'four') {
                print(htmlentities("\n Fourth <a> tag's text was changed") . '<br>');
                $grade += 1;
            }
            else {
                print(htmlentities("\n***Fourth <a> tag's text was not changed") . '<br>');
            }
        }
        else
            print("\n****Did not find four links in the nav section" . '<br>');
        }
} catch(Exception $ex) {
    print("***Did not find links in the navigation");
    $navlinks = "";
}

try {
    $nodes = $dom->getElementsByTagName('ul');
    $list_items = array();
    if ($nodes->length == 1) {
        foreach ($nodes as $node) {
            $items = $node->childNodes;
            $count = 0;
            foreach ($items as $item) {
                $list_items[] = $item;
                if ($item->tagName === 'li') {
                    $count += 1;
                }
            }
        }
        if ($count == 4) {
            print('Found four list items' . '<br>');
            $lcount = 0;
            foreach ($list_items as $item) {
                echo '<p>' . $item->nodeValue . '</p>';
                if (trim(strtolower($item->nodeValue)) == 'apples' || trim(strtolower($item->nodeValue)) == 'pizza' ||
                    trim(strtolower($item->nodeValue)) == 'crab' || trim(strtolower($item->nodeValue)) == 'chocolate cake') {
                    $lcount += 1;
                }
            }
            if ($lcount) {
                print('***' . $lcount . ' list ' . ($lcount == 1 ? 'item is' : 'items are') . ' the same as the example page <br>');
            }
            else {
                print('All list items formatted properly' . '<br>');
                $grade += 1;
            }
        }
        else {
            print("\n ***Found less or more than four list items" . '<br>');
        }
    }
    else {
        print(htmlentities("\n ***Found more than one <ul> tag") . '<br>');
    }
} catch(Exception $ex) {
    error_log(htmlentities('***Did not find a <ul> tag') . '<br>');
}

try {
    $nodes = $dom->getElementsByTagName('progress');
    if ($nodes->length == 3) {
        print("\n Found three progress tags" . '<br>');
        $progress = array();
        foreach($nodes as $node) {
            $progress[] = $node;
        }
        $p = $progress[2]->parentNode;
        $p = explode('(', $p->nodeValue);
        if (substr($p[1], 3) == '67%') {
            print(htmlentities("\n ***Value of third <progress> tag not changed") . '<br>');
        }
        else {
            print(htmlentities('<progress> tags formatted properly') . '<br>');
            $grade += 1;
        }
    }
} catch(Exception $ex) {
    error_log(htmlentities('***Did not find <progress> tags'));
}

try {
    $nodes = $dom->getElementsByTagName('details');
    if ($nodes->length == 1) {
        $details = array();
        foreach ($nodes as $node) {
            $children = $node->childNodes;
            foreach ($children as $child) {
                $details[] = $child;
            }
        }
        if ($details[0]->tagName !== 'summary') {
            print(htmlentities("\n ***Missing <summary> tag") . '<br>');
        }
        if ($details[1]->tagName !== 'p') {
            print(htmlentities("\n ***Missing <p> tag in <details>") . '<br>');
        }
        elseif (trim(strtolower($details[1]->nodeValue)) == 'i grew up in ashtabula ohio. i lived near
            lake erie and i really miss the sunsets over the water.') {
            print(htmlentities('***Content of <p> tag in <details> was not changed') . '<br>');
        }
        else {
            print(htmlentities('<details> tag properly formatted.') . '<br>');
            $grade += 1;
        }
    }
    else {
        print(htmlentities('***Did not find <details> tag or found more than one') . '<br>');
    }
} catch(Exception $ex) {
    error_log(htmlentities('Did not find <details> tag.'));
}

try {
    $nodes = $dom->getElementsByTagName('footer');
    if ($nodes->length==1){
        print("Found footer tag!\n" . '<br>');
        $grade += 1;
        echo ("\n\nYour grade is  " . $grade);

        $footer = array();
        foreach ($nodes as $node) {
            $items = $node->childNodes;
            foreach ($items as $item) {
                $footer[] = $item;
            }
        }
        if ($footer[0]->tagName == 'p') {
            $footer_p = array();
            $children = $footer[0]->childNodes;
            foreach ($children as $child) {
                $footer_p[] = $child;
            }
            if ($footer_p[1]->tagName == 'img') {
                print(htmlentities('Found <img> tag in footer <p> tag') . '<br>');
                if ($footer_p[1]->getAttribute('src') !== 'http://www.intro-webdesign.com/images/newlogo.png') {
                    print(htmlentities('***<img> tag  has incorrect src attribute') . '<br>');
                }
                elseif (!$footer_p[1]->getAttribute('alt')) {
                    print(htmlentities('<img> tag is missing alt attribute') . '<br>');
                }
                else {
                    print(htmlentities('<img> tag properly formatted') . '<br>');
                    $grade += 1;
                }
            }
            else {
                print(htmlentities('***<img> tag is not first element within <p> tag of footer') . '<br>');
            }
            if ($footer_p[2]->wholeText) {
                $text = explode('by ', $footer_p[2]->wholeText);
                $text = explode(' ', $text[1]);
                if (strtolower($text[1]) == 'name') {
                    print('***Name in footer not changed from example page' . '<br>');
                }
            }
            else {
                print(htmlentities('<p> tag text missing from footer'));
            }
            if ($footer_p[3]->tagName == 'a') {
                print(htmlentities('Found <a> tag in footer <p> tag') . '<br>');
                if ($footer_p[3]->getAttribute('href') !== 'http://www.intro-webdesign.com') {
                    print(htmlentities('***Wrong href attribute for <a> tag in the <p> tag of the footer') . '<br>');
                }
                else {
                    print(htmlentities('<a> tag in <p> tag of footer properly formatted') . '<br>');
                    $grade += 1;
                }
            }
            else {
                print(htmlentities('***No <a> tag found in <p> tag of footer') . '<br>');
            }
        }
        else {
            print(htmlentities('Missing <p> tag from footer') . '<br>');
        }
    }
    else
         print("***Did NOT find footer tag or found more than one!\n" . '<br>');
} catch(Exception $ex){
    error_log("***Did not find footer tag!");
}

echo ("\nYour grade is  " . $grade . "\n\n");
