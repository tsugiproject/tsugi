<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
LTIX::getConnection();

// https://stackoverflow.com/questions/3133209/how-to-flush-output-after-each-echo-call
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);

?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<h1>Event Detail</h1>
<ul>
<li>Activity Tracking (Tsugi Internal Analytics): 
<?= $CFG->launchactivity ? "ON" : "OFF" ?>
<ul>
<li>Link Activity Records:
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_link_activity");
echo( $row ? $row['count'] : '0'  );
?>
</li>
<li>User Activity Records:
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_link_user_activity");
echo( $row ? $row['count'] : '0'  );
?>
</li>
</ul>
</li>
<li>Event Recording into FIFO queue:
<?= $CFG->eventcheck !== false ? "ON" : "OFF" ?>
</li>
<li>Events in the FIFO queue:
<ul>
<li>Events waiting to be sent: 
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}cal_event WHERE state IS NULL");
echo( $row ? $row['count'] : '0'  );
?>
</li>
<li>Events sent with error:
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}cal_event WHERE state IS NOT NULL");
echo( $row ? $row['count'] : '0'  );
?>
</li>
</ul>
</li>
<li>Event push to Learning Record Store:
<?= $CFG->eventpushcount > 0 ? "ON" : "OFF" ?>
</li>
<li>
<?php
if ( U::apcAvailable() ) {
    echo('Last event push time: ');
    $success = false;
    $timestamp = apc_fetch('last_event_push_time',$success);
    if ( ! $success ) {
        echo(" Not set");
    } else {
        $diff = time() - $timestamp;
        $date = gmdate('Y-m-d H:i:s\Z', $timestamp);
        echo($diff.' seconds ago '.$date.' ('.$timestamp.')');
    }
} else {
   echo("APC Cache is not available");
}

?>
</li>
<li>
<?php
if ( U::apcAvailable() ) {
    echo('Last event purge time: ');
    $success = false;
    $timestamp = apc_fetch('last_event_purge_time',$success);
    if ( ! $success ) {
        echo(" Not set");
    } else {
        $diff = time() - $timestamp;
        $date = gmdate('Y-m-d H:i:s\Z', $timestamp);
        echo($diff.' seconds ago '.$date.' ('.$timestamp.')');
    }
} else {
   echo("APC Cache is not available");
}

?>
</li>
</ul>
</body>
</html>

