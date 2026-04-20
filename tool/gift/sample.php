<?php

function getSampleGIFT ()
{
return
"// true/false
::Q1 T/F:: 1+1=2 {T}

// multiple choice 
::Q2 MA:: One of these are right and three are wrong 
{
=Right 
~Wrong 
~Incorrect 
~Not right 
}

// multiple choice with multiple right and wrong
::Q3 MA:: Two of these are right and two are wrong 
{ =Right =Correct ~Wrong ~Incorrect }

// fill-in-the-blank (only right answers)
::Q4 Short Answer:: Two plus {=two =2} equals four.

";
/*
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
*/
}
