<?php
require_once "../../config.php";
\Tsugi\Core\LTIX::getConnection();

use \Tsugi\Core\LTIX;

// Retrieve the launch data if present
$LAUNCH = LTIX::requireData();

// This is a very minimal index.php - just enough to launch
// chatlist.php with the PHPSESSIONID parameter
$OUTPUT->header();
$OUTPUT->bodyStart();
?>
<p>
<a style="color:grey" href="chatlist.php" target="_blank">Launch chatlist.php</a>
</p>
<?php
$OUTPUT->footer();
