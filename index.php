<?php 
require_once "config.php";
?>
<html>
<head>
</head>
<body style="font: sans-serif;">
<h1>Welcome to 
<?php echo($CFG->servicename); ?> LTI Tool Hosting Framework</h1>
<p>
This is a framework that handles much of the low-level detail of supporting IMS
Learning Tools Interoperability tools.   It provides library and database code 
to receive and model all of the incoming LTI data in database tables and sets up 
a session with the important information about the LMS, user, and course.
<p>
This framework is designed to host many tools.  Here are the tools that are 
currently present in this instance:
<ul>
<li><a href="mod/php-intro">An autograder that runs unit tests on web sites</a></li>
<li>An attendance taking application</li>
</ul>
<p>
This tool can use IMS Learning Tools Interoperability to pass the grades back to a LMS.
If you are using this particlaur page, no grades will be sent to an LMS.  To have grade routing back
to an LMS, you need to launch this software using an LMS that supports 
IMS Learning Tools Interoperability.
<p></p>
You can simulate an IMS LTI launch to this tool using 
<a href="lms.php">this link</a>.
To use this feature, you will need to set up the database connection and 
run the database commands in the file <strong>setup.sql</strong>
script.
<p>
For more information on IMS LTI, see these links:
<ul>
<li><a href="http://developers.imsglobal.org/" target="_new">IMS LTI Developers Site</a></li>
<li><a href="https://vimeo.com/34168694" target="_new">Video Introduction to IMS LTI</a></li>
<li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Specification</a></li>
<li><a href="http://www.oauth.net"/ target="_new">A Link to the OAuth (message signing) web site</a></li>
<li><a href="http://www.imsglobal.org/" target="_new">IMS Global Learning Consortium Web Site</a></li>
</ul>
<p>
The source for this autograder is available at
<a href="https://github.com/csev/webauto" target="_new">GitHub</a> and is
licensed using the Apache 2 license.
</p>

<?php do_analytics(); ?>

</body>
