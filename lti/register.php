<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("lti2_util.php");

// Make sure to deal with the situation where cookies
// might not be working
if (isset($_REQUEST[session_name()]) ) {
    if ( ! isset($_COOKIE[session_name()])) {
        session_id($_REQUEST[session_name()]);
    }
}
session_start();

$PDOX = \Tsugi\Core\LTIX::getConnection();

\Tsugi\Core\LTIX::loginSecureCookie();

error_log('Session in register '.session_id());

$status = check_lti2_key();

// Do post-redirect of that initial post after stashing data in the session
if ( $status === true && isset($_POST["lti_message_type"]) && 
    ( $_POST["lti_message_type"] == "ToolProxyRegistrationRequest" 
     || $_POST["lti_message_type"] == "ToolProxyReregistrationRequest" ) ) {
    $_SESSION['lti2post'] = $_POST;
    header('Location: lti2');
    return;
}

// Somehow not logged in...
$OUTPUT->header();
$OUTPUT->bodyStart(false);  // Don't die on unhandled POST
?>
<h1>LTI 2 Registration</h1>
<p>
This is a page that handles an LTI 2 registration for:
<?php echo($CFG->servicename);
    if ( strlen($CFG->servicedesc) > 0 ) {
        echo(" (".htmlentities($CFG->servicedesc).")");
    }
    echo(".\n");
?>
The steps to using LTI 2 with this site are to:
<ul>
<li>Get an account on the site and be logged in
<?php if(isset($_SESSION['id'])) echo(" (done)"); ?>
</li>
<li>Apply for an LTI 2 key <br>
Status:
<?php
    if ( $status === true ) {
        echo(" Complete");
    } else {
        echo(" ".$status);
    }
?>
<li>Launch this URL with the proper POST data required by LTI 2.
</ul>
<p>
Once those steps are completed, you can re-launch this LTI 2 
registration process.
</p>
<?php
    if ( isset($_POST['launch_presentation_return_url']) ) {
?>
<p>
<a href="<?= $_POST['launch_presentation_return_url'] ?>">Return</a>
</p>
<?php } ?>
<pre>
<?php print_r($_POST);
?>
<?php
$OUTPUT->footer();
