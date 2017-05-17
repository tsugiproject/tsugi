<?php

use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
if ( file_exists("config.php") ) {
    include_once("config.php");
} else {
    echo("<pre>\nYou have not yet configured your instance of Tsugi.\n");
    echo("Copy config-dist.php to config.php and edit to setup configuration.\n");
    echo("\nSee http://www.tsugi.org/ for complete installation instructions.\n");
    echo("</pre>\n");
    die();
}
require_once("admin/sanity.php");
$PDOX = false;
try {
    define('PDO_WILL_CATCH', true);
    $PDOX = LTIX::getConnection();
} catch(\PDOException $ex){
    $PDOX = false;  // sanity-db-will re-check this below
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( $PDOX !== false ) LTIX::loginSecureCookie();

$OUTPUT->header();
$OUTPUT->bodyStart();

require_once("admin/sanity-db.php");
$OUTPUT->topNav();
$OUTPUT->flashMessages();
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
You can install your own copy of the Tsugi Administration, Developer, and Management console from 
<a href="https://github.com/tsugiproject/tsugi" target="_blank">https://github.com/tsugiproject/tsugi</a>.  
This makes use of the Tsugi PHP runtime library
<a href="https://github.com/tsugiproject/tsugi-php" target="_blank">https://github.com/tsugiproject/tsugi-php</a>.  
</p>
<p>
You can install any of the following PHP Tsugi tools / toolsets:
<ul>
<li>A set of core modules (Map, Peer-Grade, etc):
<a href="https://github.com/tsugitools/" target="_blank">https://github.com/tsugitools/</a>
</li>
<li>A starting point to build your own module:
<a href="https://github.com/tsugiproject/tsugi-php-module" target="_blank">https://github.com/tsugiproject/tsugi-php-module</a>
</li>
<li>A starting point to use Tsugi in a stand-alone application:
<a href="https://github.com/tsugiproject/tsugi-php-standalone" target="_blank">https://github.com/tsugiproject/tsugi-php-standalone</a>
</li>
<li>A set of self-paced Tsugi exercises (for a workshop or a class):
<a href="https://github.com/tsugiproject/tsugi-php-exercises" target="_blank">https://github.com/tsugiproject/tsugi-php-exercises</a>
- You can also access the exercise
<a href="https://lti-tools.dr-chuck.com/exercises/" target="_blank">specifications</a> 
and 
<a href="https://lti-tools.dr-chuck.com/tsugi/dev" target="_blank">working solutions</a> (secret)
</li>
</ul>
</p>
<p>
A Java version of Tsugi is being developed at
<a href="https://github.com/tsugiproject/tsugi-java-servlet" target="_blank">https://github.com/tsugiproject/tsugi-java-servlet</a>
and a Node version of Tsugi is being developed at
<a href="https://github.com/tsugiproject/tsugi-node-sample" target="_blank">https://github.com/tsugiproject/tsugi-node-sample</a>.
Currently all Tsugi languages use this software as their administration and management console.
</p>
<p>
Learning Tools Interoperability™ (LTI™) is a
trademark of IMS Global Learning Consortium, Inc.
in the United States and/or other countries. (www.imsglobal.org)
</p>
<?php $OUTPUT->footer();
