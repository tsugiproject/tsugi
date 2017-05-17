<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("config.php");
$OUTPUT->header();
$OUTPUT->bodyStart();
?>
      <div class="jumbotron">
<center style="padding-bottom: 20px;">
<a href="http://www.tsugi.org" target="_new">
<img style="width: 80%; max-width:360px;" src="<?= $CFG->staticroot . '/img/logos/tsugi-logo-incubating.png' ?>">
</a>
</center>
<h1>LTI Based LMS</h1>
<p>
This developer screen allows you to quickly test TSUGI applications.
It allows you to change any LTI lauch parameter and switch
between a sets of fake user account data.  
</p>
<?php if ( false && $CFG->DEVELOPER ) { ?>
<a class="btn btn-primary" href="dev" role="button">Back</a>
<?php } else { ?>
<a class="btn btn-primary" href="<?= $CFG->apphome ?>" role="button">Back</a>
<?php } 
$OUTPUT->footer();
