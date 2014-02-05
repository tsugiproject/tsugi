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
This developer screen allows you to quickly test TSUGI applications.
It allows you to change any LTI lauch parameter and switch
between a set of user account data.  This feature is intended 
ro test instances running on a developer desktop (i.e. not suitable
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
<?php } ?>
</div>
</div>
</body>
