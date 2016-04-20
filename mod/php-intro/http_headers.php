<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;

function mapdown($input) {
    return preg_replace('/[^a-z0-9]/i','',strtolower($input));
}

// Compute the stuff for the output
$getUrl = 'http://www.pythonlearn.com/code/intro-short.txt';

$data = Net::doGet($getUrl);
$response = Net::getLastHttpResponse();
if ( $response != 200 ) {
    die("Response=$response url=$getUrl");
}
$headers = Net::parseHeaders();

$fields = array(
    'Last-Modified',
    'ETag',
    'Content-Length',
    'Cache-Control',
    'Content-Type'
);

$oldgrade = $RESULT->grade;
// If we have a POST, pass to the GET to be put in the fields
if ( count($_POST) > 0 ) {
    $_SESSION['postdata'] = $_POST;

    $count = 0;
    $good = 0;
    foreach($headers as $key => $val ) {
        if ( ! in_array($key,$fields) ) continue;
        $postkey = mapdown($key);
        $count++;
        if ( isset($_POST[$postkey])&& mapdown($_POST[$postkey]) == mapdown($val) ) {
            $good++;
        }
    } 
    if ( $count == 0 ) {
        die("No expected fields found");
    }

    $gradetosend = (1.0 * $good) / $count;
    LTIX::gradeSendDueDate($gradetosend, $oldgrade, $dueDate);

    header('Location: '.addSession('index.php'));
    return;
}

if ( $RESULT->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($RESULT->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

$postdata = isset($_SESSION['postdata']) ? $_SESSION['postdata'] : array();
unset($_SESSION['postdata']);

?>
<p>
<b>Exploring the HyperText Transport Protocol</b>
<p>
You are to retrieve the following document in a browser tab with 
"Developer Mode" enabled so you can examine the HTTP Response headers.
You may need to open the URL in a new tab, turn on dveloper mode, 
and then press refresh to get the proper data.
<ul>
<li><a href="<?= $getUrl ?>" target="_blank"><?= $getUrl ?></a></li>
</ul>
</p>
<p>
Enter the header values in each of the fields below and press "Submit".
<form method="post">
<?php
    $count = 0;
    foreach($headers as $key => $val ) {
        if ( ! in_array($key,$fields) ) continue;
        $postkey = mapdown($key);
        $count++;
        echo(htmlentities($key).':<br/>');
        echo('<input type="text" size="60" name="'.$postkey.'" ');
        if ( isset($postdata[$postkey]) ) {
            echo('value="'.htmlentities($postdata[$postkey]).'" /> ');
            if ( mapdown($postdata[$postkey]) == mapdown($val) ) {
                echo('<i class="fa fa-check text-success"></i>');
            } else {
                echo('<i class="fa fa-times text-danger"></i>');
            }
        } else {
            echo("/> ");
        }
        echo("<br/>\n");
    } 
?>
<input type="submit">
</form>
</p>
<p>
<b>Note:</b> If you look at the headers and not all of the headers are present, 
it may be that your browser is caching the request.  Look for the 
<a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes" taget="_blank">HTTP
Response Code</a> in the developer console. Normally you should see a "200" code indicating a 
normal document retrieval.  If you see a "304" error code, it means that your browser
is likely using a cached copy of the file.</p>
<p clear="all">
To convince yor browser to actually retrieve the
document, clear your browser cache and re-retrieve the document, or add a key-value pair to 
the URL like:
<pre>
<a href="<?= $getUrl ?>?x=12345" target="_blank"><?= $getUrl ?>?x=12345</a>
</pre>
And then retrieve that URL.  To force a fresh retrieval, simply change the value 
for <code>x=</code>
to any new value and re-retrieve the page until you get a 200 status code.
</p>
<center>
<a href="http_headers-01.png" target="_blank"><img src="http_headers-01.png" 
style="width: 40%; min-width: 400px;"></a>
</center>
<?php
if ( $USER->instructor ) {
echo("\n<hr/>");
echo("\n<pre>\n");
echo("Retrieved information:\n");
print_r($headers);
echo("\n");
echo($data);
echo("\n</pre>\n");
}
