<?php

require_once "util.php";
session_start();
header('Content-Type: text/html; charset=utf-8');

$text = 
"// true/false
::Q1 T/F:: 1+1=2 \\{yada\\} {T}

// multiple choice with specified feedback for right and wrong answers
::Q2 MC:: Plaintext test < > & ; ' \" &lt; &gt; <b> (answer is &lt;)
{ =< # right; good! ~> # wrong, it's < ~& # wrong, it's < }

// multiple choice with multiple right and wrong
::Q3 MA:: Two of these are \\{right\\} and two are wrong 
and GIFT escape character test
{ =Right \{1\} =Right \= 2 ~Wrong \~ 1 ~Wrong \ 2}

// fill-in-the-blank (only right answers)
::Q4 Short Answer:: Two plus {=two =2} equals four.

// matching (All right answers with -> )
::Q5 Matching:: Which animal eats which food? 
{ =cat -> cat food =dog -> dog food }

// math range question
::Q6 Range colon:: What is a number from 1 to 5? {#3:2}

// math range specified with interval end points
::Q7 Range ..:: What is a number from 1 to 5? {#1..5}

// multiple numeric answers with partial credit and feedback
::Q8 Partial Credit Numeric:: When was Ulysses S. Grant born? {#
         =1822:0      # Correct! Full credit.
         =%50%1822:2  # He was born in 1822. Half credit for being close.
}

// essay
::Q9 Essay:: How are you? {}

// HTML with pre tags html code style
::Q10 HTML::[html]The next two lines are in a pre tag.<br/>
<pre>
   Here is a less-than &lt;
   and an ampersand &amp;
</pre>
An some non-pre text after the pre section is done.
{ =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

// HTML with pre tags python code style
::Q11 HTML Python::[html]Some code in a pre block<br/>
<pre>
    if x &lt; 10 :
        print \"too low\"
    else : 
        print \"just right\"
</pre>
An some HTML after the end of the pre block
{ =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

// Make sure < and > make it through in plaintext questions
::Q12 Plaintext:: In a plaintext question, does the <b> bold tag 'show' 
\"with\" less than's and greater than's instead of turning stuff bold.
{ =Do we see a < less than ~Do we see a > greater than 
~Do we see a ' single quote ~Do we see a \" double quote}

// HTML with formatting
::Q11 HTML with formatting::[html]In an HTML question, <b>bold</b> should simply appear as bold?
{ =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

::Q12::[html] What is true about the following HMTL?
<pre>
&lt;a href=\"http://www.dr-chuck.com/page2.htm\"&gt;Second Page&lt;/a&gt;
</pre>
{
=The reference is an absolute reference
~The reference is a relative reference
~The HTML is improperly formed and will be a syntax error
~The text \"Second Page\" is improperly placed and will not be seen
}

::Q13::[html] For the following HTML, what does the \"style=\" attribute
achieve?
<pre>
&lt;p style=\"color: red;\"&gt;
</pre>
{
=It allows the application of CSS rules to the contents of the tag
~It is an HTML syntax error and will be ignored
~It changes the background color of the paragreaph to red
~It contains JavaScript to be executed when the ofer hovers over the paragraph
~It changes the color of the tab for this page in the borwser to be red
}

";

unset($_SESSION['content_item_return_url']);
if ( isset($_POST['ext_content_return_url']) ) $_SESSION['content_item_return_url'] = $_POST['ext_content_return_url'];

$config_url = str_replace("index.php", "lti_config.php", curPageUrl());

?>
<!DOCTYPE html>
<html>
<head>
<title>GIFT2QTI - Quiz format convertor</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">

</head>
<body style="margin: 5px;">
<p>Please enter your <a href="https://docs.moodle.org/28/en/GIFT_format" target="_blank">GIFT</a> 
formatted quiz text below so it can be converted to 
<a href="http://www.imsglobal.org/question/" target="_blank">QTI 1.2.1</a>.
</p><p>
This is still a <a href="https://github.com/csev/gift2qti" target="_blank">work in progress</a>
and currently only supports single-answer multiple-choice, true/false, and essay questions.
The sample text below has some GIFT formats that this tool does not yet support so some of the questions
below will not be converted.  Feel free to send me a Pull request on gitHub :).
</p>
<form method="post" action="convert.php" target="working" style="margin:20px;">
<p style="float:right">
<input type="submit" name="submit" class="btn btn-primary" value="Convert GIFT to QTI"
onclick="$('#myModal').modal('show');"></p>
<p>Quiz Title: <input type="text" name="title" size="60" value="Converted using GIFT2QTI"/></p>
<p>Quiz File Name (no suffix): <input type="text" name="name" size="30"/> (optional)</p>
<textarea rows="30" style="width: 98%" name="text">
<?= htmlent_utf8($text); ?>
</textarea>
<p><input type="checkbox" name="bypass" value="bypass">
Do not validate the XML</p>
</form>
<p>If you want to add this tool to the <b>Settings -&gt; Import Content</b>
in the Canvas LMS use this URL:
<a href="<?= $config_url ?>" target="_blank"><?= $config_url ?></a>
</p>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:80%">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" 
            onclick="$('#working').attr('src', 'waiting.php');" ><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Converting to QTI...</h4>
      </div>
      <div class="modal-body">
        <iframe id="working" name="working" src="waiting.php" style="width:90%; height: 400px"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Latest compiled and minified JavaScript -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" ></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
</body>
</html>
