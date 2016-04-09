<?php
define('COOKIE_SESSION', true);
require_once("config.php");
$OUTPUT->header();
$OUTPUT->bodyStart();
?>
      <div class="jumbotron">
<center style="padding-bottom: 20px;">
<a href="http://www.tsugi.org" target="_new">
<img style="width: 80%; max-width:360px;" src="<?= $CFG->staticroot . '/static/img/logos/tsugi-logo-incubating.png' ?>">
</a>
</center>
<h1>LTI Based LMS</h1>
<p>
This developer screen allows you to quickly test TSUGI applications.
It allows you to change any LTI lauch parameter and switch
between a set of user account data.  This feature is intended
to test instances running on a developer desktop (i.e. not suitable
for production) and so to enable this, you need to have the
<pre>
$CFG->DEVELOPER flag set to true
</pre>
in the config.php file for this application.
</p>
<?php if ( $CFG->DEVELOPER ) { ?>
<a class="btn btn-primary" href="dev.php" role="button">Back</a>
<?php } else { ?>
<a class="btn btn-primary" href="index.php" role="button">Back</a>
<?php } 
$OUTPUT->footer();
