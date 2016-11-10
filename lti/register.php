<?php
define('COOKIE_SESSION', true);
require_once("../config.php");

// Make sure to deal with the situation where cookies
// might not be working
if (isset($_REQUEST[session_name()]) ) {
    if ( ! isset($_COOKIE[session_name()])) {
        session_id($_REQUEST[session_name()]);
    }
}
session_start();

if ( isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] == session_id() ) {
    $popup = 'register.php';
    $register = 'lti2.php';
} else { // Add the session id for insurance
    $popup = 'register.php?'.session_name().'='.session_id();
    $register = 'lti2.php?'.session_name().'='.session_id();
}

error_log('Session in register.php '.session_id());

// Always do post-redirect of that initial post after stashing data in the session
if ( isset($_POST["lti_message_type"]) && 
    ( $_POST["lti_message_type"] == "ToolProxyRegistrationRequest" || $_POST["lti_message_type"] == "ToolProxyReregistrationRequest" ) ) {
    $_SESSION['lti2post'] = $_POST;
    header('Location: '.$popup);
    return;
}

// Now lets make sure we are in the top window...
$OUTPUT->header();
$OUTPUT->bodyStart();
echo('<img src="'.$OUTPUT->getSpinnerUrl().'" id="spinner">');
?>
<div id="popup" style="display:none">
<p>Please click
<a href="<?php echo($popup); ?>" target=_blank">here</a>
to continue the registration process in a new window.
</p>
</div>
<?php
$OUTPUT->footerStart();
?>
<script type="text/javascript">
topframe = false;
try {
    topframe = window.self === window.top ? true : false;
} catch (e) {
    topframe = false;
}
if ( topframe ) {
    window.location.href='<?php echo($register); ?>';
} else {
    $("#spinner").hide();
    $("#popup").show();
}
</script>
<?php
$OUTPUT->footerEnd();
