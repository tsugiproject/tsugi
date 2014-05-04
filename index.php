<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
include_once("config.php");
require_once("sanity.php");
require_once("lib/lti_util.php");
try {
    define('PDO_WILL_CATCH', true);
    require_once("pdo.php");
} catch(PDOException $ex){
    $pdo = false;  // sanity-db-well re-check this below
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( $pdo !== false ) login_secure_cookie($pdo);

headerContent();
?>
</head>
<body>
<?php
require_once("sanity-db.php");
?>
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
            <?php if ( $CFG->DEVELOPER ) { ?>
            <li><a href="dev.php">Developer</a></li>
            <?php } ?>
            <?php if ( isset($_SESSION['id']) || $CFG->DEVELOPER ) { ?>
            <li><a href="admin/upgrade.php" target="_blank">Admin</a></li>
            <?php } ?>

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
            <?php if ( isset($_SESSION['id']) ) { ?>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo($_SESSION['displayname']);?><b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
              </ul>
            </li>
            <?php } else { ?>
            <li><a href="login.php">Login</a></li>
            <?php } ?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>

      <div>
<?php
flashMessages();
if ( $CFG->DEVELOPER ) {
    echo '<div class="alert alert-danger" style="margin-top: 10px;">'.
        'Note: Currently this server is running in developer mode.'.
        "\n</div>\n";
}

?>
<p>
Hello and welcome to <b><?php echo($CFG->servicename); ?></b>.
Generally this system is used to provide cloud-hosted learning tools that are plugged
into a Learning Management systems like Sakai, Coursera, or Blackboard using 
IMS Learning Tools Interoperability.  You can sign in to this system 
and create a profile and as you use tools from various courses you can 
associate those tools and courses with your profile.
</p>
<p>
Other than logging in and setting up your profile, there is nothing much you can 
do at this screen.  Things happen when your instructor starts using the tools
hosted on this server in their LMS systems.  If you are an instructor and would
like to experiment with these tools (it is early days) send a note to Dr. Chuck.
You can look at the source code for this software at 
<a href="https://github.com/csev/tsugi" target="_blank">https://github.com/csev/tsugi</a>.
</p>
      </div> <!-- /container -->

<?php footerContent(); 
