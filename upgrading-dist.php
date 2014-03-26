<?php
require_once("config.php");
?><!DOCTYPE html>
<html>
<head>
<!-- copy this file to upgrading.php and change the $CFG-upgrading = true -->
<title><?php echo($CFG->servicename); ?> Is Being Worked On</title>
</head>
<body style="font: sans-serif;">
<center>
<h1>Sorry, we are working on the <?php echo($CFG->servicename); ?> and will be back in a bit...</h1>
<p>
In progress: 
--- Replace with a message
<p>
Work started:
--- Replace with a date like "Wed Mar 26 11:45:41 EDT 2014"
</p>
<p>
Expected duration:
--- Replace with something like "10 minutes or less"
<!-- Or just completely make up your own message -->
</p>
</center>
</body>
<html>
<?php
    exit("");
