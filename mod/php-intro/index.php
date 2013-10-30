<?php 
require_once "../../config.php";
?>
<html>
<head>
</head>
<body style="font: sans-serif;">
<h1>Welcome to 
<?php echo($CFG->servicename); ?> Autograder</h1>
<p>
This is a simple autograder that runs a set of unit tests on a web site
and then determines if a web site passes the unit tests and sends a grade back to the
LMS.
</p>
<form action="assn02.php" target="_blank">
Assignment 2 URL to grade:
<input type="text" name="url" value="http://csevumich.byethost18.com/howdy.php" size="100">
<input type="submit">
</form>
<form action="assn03.php" target="_blank">
Assignment 3 URL to grade:
<input type="text" name="url" value="http://www.php-intro.com/assn/games/rps.php" size="100">
<input type="submit">
</form>
<form action="assn04.php" target="_blank">
Assignment 4 URL to grade:
<input type="text" name="url" value="http://www.php-intro.com/assn/cart/" size="100">
<input type="submit">
</form>
<form action="assn05.php" target="_blank">
Assignment 5 URL to grade:
<input type="text" name="url" value="http://www.php-intro.com/assn/tracks" size="100">
<input type="submit">
</form>
<!--
<form action="assn06.php" target="_blank">
Assignment 6 URL to grade:
<input type="text" name="url" value="http://localhost/~csev/webauto/lms.php" size="100">
<input type="submit">
</form>
-->
<p>
Here is some documentation on the software used to build these unit tests:
<ul>
<li><a href="http://symfony.com/doc/current/components/dom_crawler.html" target="_new">
http://symfony.com/doc/current/components/dom_crawler.html
</a></li>
<li><a href="http://api.symfony.com/2.3/Symfony/Component/BrowserKit.html" target="_new">
http://api.symfony.com/2.3/Symfony/Component/BrowserKit.html
</a></li>
<li><a href="http://api.symfony.com/2.3/Symfony/Component/DomCrawler/Crawler.html" target="_new">
http://api.symfony.com/2.3/Symfony/Component/DomCrawler/Crawler.html
</a></li>
</ul>
</p>
<p>
This tool can use IMS Learning Tools Interoperability to pass the grades back to a LMS.
If you are using this particlaur page, no grades will be sent to an LMS.  To have grade routing back
to an LMS, you need to launch this software using an LMS that supports 
IMS Learning Tools Interoperability.
</p>

<?php do_analytics(); ?>

</body>
