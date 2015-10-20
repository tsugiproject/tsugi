<?php

require_once('data_util.php');

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Mersenne_Twister;

// Compute the stuff for the output
$code = 42;
$new = getShuffledNames($code);
$nums = getRandomNumbers($code,min(50,count($new)),100);
$sum_sample = array_sum($nums);

$code = $USER->id+$LINK->id+$CONTEXT->id;
$new = getShuffledNames($code);
$nums = getRandomNumbers($code,min(50,count($new)),100);
$sum = array_sum($nums);

$oldgrade = $RESULT->grade;
if ( isset($_POST['sum']) ) {
    if ( $_POST['sum'] != $sum ) {
        $_SESSION['error'] = "Your sum did not match";
        header('Location: '.addSession('index.php'));
        return;
    }

    LTIX::gradeSendDueDate(1.0, $oldgrade, $dueDate);
    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// echo($goodsha);
if ( $LINK->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($LINK->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
$url = curPageUrl();
$sample_url = str_replace('index.php','data/comments_42.html',$url);
$actual_url = str_replace('index.php','data/comments_'.$code.'.html',$url);
?>
<p>
<b>Scraping Numbers from HTML using BeautifulSoup</b>
<form method="post">
This assignment is from Chapter 12 - Regular Expressions in 
<a href="http://www.pythonlearn.com/book.php" target="_blank">Python for Informatics: Exploring Information</a>.
In this assignment you will write a Python program similar to 
<a href="http://www.pythonlearn.com/code/urllink2.py" target="_blank">http://www.pythonlearn.com/code/urllink2.py</a>.  
The program will use <b>urllib</b> to read the HTML from the data files below, and parse the data, 
extracting numbers and comput the sum of the numbers in the file and enter the sum below:<br/>
<input type="text" size="20" name="sum">
<input type="submit" value="Submit Sum">
</form>
</p>
<b>Data Files</b>
<p>
We provide two files for this assignment.  One is a sample file where we give you the sum for your
testing and the other is the actual data you need to process for the assignment.  
<?= $url ?>
<ul>
<li> Sample data: <a href="<?= $sample_url ?>" target="_blank"><?= $sample_url ?>.</a> 
(Sum=<?= $sum_sample ?>) </li>
<li> Actual data: <a href="<?= $actual_url ?>" target="_blank"><?= $actual_url ?></a> 
(Sum ends with <?= $sum%100 ?>)<br/> </li>
</ul>
These links open in a new window.
Make sure to save the file into the same folder as you will be writing your Python program.
<b>Note:</b> Each student will have a distinct data file for the assignment - so only use your
own data file for analysis.
</p>
<b>Data Format</b>
<p>
The file is a table of names and comment counts.   You can ignore most of the data in the
filw except for lines lke the following:
<pre>
&lt;tr>&lt;td>Modu&lt;/td>&lt;td>&lt;span class="comments">90&lt;/span>&lt;/td>&lt;/tr>
&lt;tr>&lt;td>Kenzie&lt;/td>&lt;td>&lt;span class="comments">88&lt;/span>&lt;/td>&lt;/tr>
&lt;tr>&lt;td>Hubert&lt;/td>&lt;td>&lt;span class="comments">87&lt;/span>&lt;/td>&lt;/tr>
</pre>
You are to find all the &lt;span&gt; tags in the file and pull out the numbers from the 
tag and sum the numbers.
<p>
Look at the 
<a href="http://www.pythonlearn.com/code/urllink2.py" target="_blank">sample code</a>
provided.  It shows how to find all of a certain kind of tag, loop through the tags and
extract the various aspects of the tags.
<pre>
...
# Retrieve all of the anchor tags
tags = soup('a')
for tag in tags:
   # Look at the parts of a tag
   print 'TAG:',tag
   print 'URL:',tag.get('href', None)
   print 'Contents:',tag.contents[0]
   print 'Attrs:',tag.attrs
</pre>
You need to adjust this code to look for span tags and pull out the text content of 
the span tag, convert them to integers and add them up to complete the assignment.
</p>

