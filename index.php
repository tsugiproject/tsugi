<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("config.php");
require_once("lib/lti_util.php");

header('Content-Type: text/html; charset=utf-8');
session_start();

headerContent();
?>
</head>
<body>
  <form method="post" id="actionform">
    <div class="container">
      <!-- Static navbar -->
      <div class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">TSUGI</a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li><a href="dev.php">Developer</a></li>
            <li><a href="admin/upgrade.php" target="_blank">Admin</a></li>

            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Links<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="http://developers.imsglobal.org/" target="_blank">IMS LTI Documentation</a></li>
				<li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Spec</a></li>
				<li><a href="https://vimeo.com/34168694" target="_new">IMS LTI Lecture</a></li>
                <li><a href="http://www.oauth.net/" target="_blank">OAuth Documentation</a></li>
              </ul>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="about.php">About</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo('lis_person_name_full');?><b class="caret"></b></a>
              <ul class="dropdown-menu">
				<li><a href="#" onclick="doSubmit('instructor');return false;">Jane Instructor</a></li>
				<li><a href="#" onclick="doSubmit('learner1');return false;">Sue Student</a></li>
				<li><a href="#" onclick="doSubmit('learner2');return false;">Ed Student</a></li>
              </ul>
            </li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>

      <div>
      </div> <!-- /container -->

<?php footerContent(); 
