<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("config.php");
$OUTPUT->header();
?>
</head>
<body>
    <div class="container">
      <div class="jumbotron">
<center style="padding-bottom: 20px;">
<a href="http://www.tsugi.org" target="_new">
<img style="width: 80%; max-width:360px;" src="<?= $CFG->staticroot . '/img/logos/tsugi-logo-incubating.png' ?>">
</a>
</center>
<p>
Tsugi is a framework that handles much of the low-level detail of
building multi-tenant tool that makes use of the 
IMS Learning Tools Interoperability™ (LTI)™ and other learning
tool interoperability standards.
<a href="http://www.tsugi.org" target="_blank">The Tsugi Framework</a> 
provides library and database code to receive and model all
of the incoming LTI data in database tables and sets up a session
with the important information about the LMS, user, and course.
</p>
<p>
Tsugi is currently an 
<a href="https://www.apereo.org/incubation" target="_blank">incubation project</a> in the 
<a href="http://www.apereo.org/" target="_new">Apereo Foundation</a>.
<p>
Learning Tools Interoperability™ (LTI™) is a
trademark of <a href="http://www.imsglobal.org/" target="_blank">IMS Global Learning Consortium, Inc.</a>
in the United States and/or other countries.
</p>
<a class="btn btn-primary" href="<?= $CFG->wwwroot ?>" role="button">Back</a>
</div>
</div>
</body>
