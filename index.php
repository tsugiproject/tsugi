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
and then determines if a web site passes the unit tests. 
</p>
<form action="grade/assn02.php" target="_blank">
Assignment 2 URL to grade:
<input type="text" name="url" value="http://csevumich.byethost18.com/howdy.php" size="100">
<input type="submit">
</form>
<form action="grade/assn03.php" target="_blank">
Assignment 3 URL to grade:
<input type="text" name="url" value="http://www.php-intro.com/assn/games/rps.php" size="100">
<input type="submit">
</form>
<form action="grade/assn04.php" target="_blank">
Assignment 4 URL to grade:
<input type="text" name="url" value="http://www.php-intro.com/assn/cart/" size="100">
<input type="submit">
</form>
<p>
This tool can use IMS Learning Tools Interoperability to pass the grades back to a LMS.
If you are using this particlaur page, no grades will be sent to an LMS.  To have grade routing back
to an LMS, you need to launch this software using an LMS that supports 
IMS Learning Tools Interoperability.
</p>
<p>
The source for this autograder is available at
<a href="https://github.com/csev/webauto" target="_new">GitHub</a> and is
licensed using the Apache 2 license.
</p>

<?php do_analytics(); ?>

</body>
