<?php
require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\ContentItem;
use \Tsugi\Core\DeepLinkResponse;
use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Util\LTI13;
use \Tsugi\UI\Lessons;
use \Tsugi\Util\LTIConstants;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$message_type = $LAUNCH->ltiMessageType();
error_log("Store launch message_type=".$message_type);

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<h1>Coming soon in Tsugi....</h1>
<?php
$OUTPUT->footer();
