<?php
define('COOKIE_SESSION', true);
require_once("config.php");
$OUTPUT->header();
?>
</head>
<body>
    <div class="container">
      <div class="jumbotron">
<h1>LTI Based Learning-Tool Framework</h1>
<p>
This is a framework that handles much of the low-level detail of
building multi-tenant
IMS Learning Tools Interoperability™ (LTI)™ tool.
It provides library and database code to receive and model all
of the incoming LTI data in database tables and sets up a session
with the important information about the LMS, user, and course.
</p>
<p>
Learning Tools Interoperability™ (LTI™) is a
trademark of IMS Global Learning Consortium, Inc.
in the United States and/or other countries. (www.imsglobal.org)
</p>
<a class="btn btn-primary" href="index.php" role="button">Back</a>
</div>
</div>
</body>
