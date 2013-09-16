<?php
require_once "setup.php";
require_once "config.php";
?>
<html>
<head>
</head>
<body style="font: sans-serif;">
<h1>Welcome to SI664 Prototype Web Autograder</h1>
<p>
This is a simple autograder that runs a set of unit tests on a web site
and then determines if a web site passes the unit tests.  This tool can 
use IMS Learning Tools Interoperability to pass the grades back to a LMS.
</p>
<form action="grade/assn01.php" target="_blank">
URL to grade:
<input type="text" name="url" value="http://drchuck.byethost18.com/" size="100">
<input type="submit">
</form>
<p>
If you are using this - no grades will be sent to an LMS.  To have grade routing back
to an LMS, you need to launch this software using IMS Learning Tools Interoperability.
</p>
<p>
The source for this autograder is available at
<a href="https://github.com/csev/webauto" target="_new">GitHub</a> and is
licensed using the Apache 2 license.
</p>

<?php do_analytics(); ?>

</body>
