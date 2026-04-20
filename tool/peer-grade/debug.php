<?php
require_once "../config.php";
require_once "peer_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Instructor only");
if ( isset($_POST['doClear']) ) {
    session_unset();
    die('session unset');
}

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back'), 'index');

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

$OUTPUT->togglePre("Session data",$OUTPUT->safe_var_dump($_SESSION));

?>
<form method="post">
<input type="submit" name="doClear" value="Clear Session (will log out out)">
</form>
<?php
flush();

$OUTPUT->footer();


