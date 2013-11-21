<?php 
define('COOKIE_SESSION', true);
require_once("config.php");
headerContent();
?>
</head>
<body>
    <div class="container">
      <div class="jumbotron">
<h1>LTI Based LMS</h1>
<p>
This is a framework that handles much of the low-level detail of supporting IMS
Learning Tools Interoperability tools.   It provides library and database code 
to receive and model all of the incoming LTI data in database tables and sets up 
a session with the important information about the LMS, user, and course.
</p>
<a class="btn btn-primary" href="index.php" role="button">Back</a>
</div>
</div>
</body>
