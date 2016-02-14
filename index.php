<?php

use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
if ( file_exists("config.php") ) {
    include_once("config.php");
} else {
    echo("<pre>\nYou have not yet configured your instance of Tsugi.\n");
    echo("\nSee http://www.tsugi.org/ for complete installation instructions.\n");
    echo("</pre>\n");
    die();
}
require_once("sanity.php");
$PDOX = false;
try {
    define('PDO_WILL_CATCH', true);
    $PDOX = LTIX::getConnection();
} catch(PDOException $ex){
    $PDOX = false;  // sanity-db-will re-check this below
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( $PDOX !== false ) LTIX::loginSecureCookie();

$OUTPUT->header();
$OUTPUT->bodyStart();

require_once("sanity-db.php");
$OUTPUT->topNav();
?>
      <div>
<?php
$OUTPUT->flashMessages();
if ( $CFG->DEVELOPER ) {
    echo '<div class="alert alert-danger" style="margin-top: 10px;">'.
        _m('Note: Currently this server is running in developer mode.').
        "\n</div>\n";
}

?>
<p>
Hello and welcome to <b><?php echo($CFG->servicename); ?></b>.
 <?php if ( $CFG->servicedesc ) echo($CFG->servicedesc); ?>
 This service is running software that provides
cloud-hosted learning tools that are plugged
into a Learning Management systems like Sakai, Moodle, Coursera, 
Canvas, D2L or Blackboard using
IMS Learning Tools Interoperability™ (LTI)™.
<!-- Not yet supported
You can sign in to this service
and create a profile and as you use tools from various courses you can
associate those tools and courses with your profile.
-->
</p>
<p>
Other than logging in and setting up your profile, there is nothing much you can
do at this screen.  
<?php if ( $CFG->providekeys ) { ?>
Things happen when an instructor starts using the tools
hosted on this server in their LMS systems.  
</p>
<p>
If you are an instructor and would
like to experiment with these tools you can log in with
a Google account and apply for a key and 
<?php echo($CFG->ownername); ?>
 will get back with you.  You can send email questions about this service to 
<?php echo($CFG->owneremail); ?>.
<?php } else {?>
Some Tsugi servers accept key applications from instructors, but 
this server is not configured to accept applications for keys.
<?php } ?>
</p>
You can install your own instance of Tsugi from 
<a href="https://github.com/csev/tsugi" target="_blank">https://github.com/csev/tsugi</a>.
A Java version of Tsugi is being developed at
<a href="https://github.com/csev/tsugi-java-servlet" target="_blank">https://github.com/csev/tsugi-java-servlet</a>.
This software is used to provision and test the Java version of Tsugi.
</p>
<p>
Learning Tools Interoperability™ (LTI™) is a
trademark of IMS Global Learning Consortium, Inc.
in the United States and/or other countries. (www.imsglobal.org)
</p>
      </div> <!-- /container -->

<?php $OUTPUT->footer();
