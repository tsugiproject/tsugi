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
$sample_url = str_replace('index.php','data/comments_42.json',$url);
$actual_url = str_replace('index.php','data/comments_'.$code.'.json',$url);
?>
<p>
<b>Extracting Data from JSON</b>
<form method="post">
This assignment is from Chapter 13 - Using Web Services in 
<a href="http://www.pythonlearn.com/book.php" target="_blank">Python for Informatics: Exploring Information</a>.
In this assignment you will write a Python program somewhat similar to 
<a href="http://www.pythonlearn.com/code/json2.py" target="_blank">http://www.pythonlearn.com/code/json2.py</a>.  
The program will prompt for a URL, read the JSON data from that URL using 
<b>urllib</b> and then parse and extract the comment counts from the JSON data, 
compute the sum of the numbers in the file and enter the sum below:<br/>
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
You do not need to save these files to your folder since your
program will read the data directly from the URL.
<b>Note:</b> Each student will have a distinct data url for the assignment - so only use your
own data url for analysis.
</p>
<b>Data Format</b>
<p>
The data consists of a number of names and comment counts in JSON as follows:
<pre>
{
  comments: [
    {
      name: "Matthias"
      count: 97
    },
    {
      name: "Geomer"
      count: 97
    }
    ...
  ]
}
</pre>
<p>
Look at the 
The closest sample code that shows how to parse JSON is 
<a href="http://www.pythonlearn.com/code/json2.py" target="_blank">json2.py</a>.
</p>

