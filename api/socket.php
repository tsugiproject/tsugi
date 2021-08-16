<?php
require_once "../config.php";

/** Approximate web sockets if not present
 */

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Rest;

if ( Rest::preFlight() ) return;

$max_seconds = 60;

$LAUNCH = LTIX::requireData();

$path = U::rest_path();
$room = 0;
if ( is_numeric($path->action) ) {
    $room = $path->action+0;
}

// Get microsecond time as double
$micro_now = microtime(true);

$message = U::get($_POST, 'message');
if ( is_string($message) && strlen($message) > 0 ) {

    $sql = "INSERT INTO {$CFG->dbprefix}lti_message
        (link_id, room_id, message, micro_time ) VALUES
        (:link_id, :room_id, :message, :micro_now )
        ON DUPLICATE KEY UPDATE message=:message
    ";
    $values = array(
        ':link_id' => $LINK->id,
        ':room_id' => $room,
        ':message' => $message,
        ':micro_now' => $micro_now
    );
    $retval = $PDOX->queryDie($sql, $values);
    return;
}

$since = U::get($_GET, 'since', 0);
if ( ! is_numeric($since) ) $since = 0;

$sql = "SELECT message, created_at, micro_time
    FROM {$CFG->dbprefix}lti_message
    WHERE link_id = :link_id AND room_id = :room_id
      AND micro_time > :since AND
      created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
    ORDER BY micro_time ASC
";

$values = array(
    ':link_id' => $LINK->id,
    ':room_id' => $room,
    ':max' => $max_seconds,
    ':since' => $since
);

$rows = $PDOX->allRowsDie($sql, $values);

// Cleanup
$debug = false;
if ( $debug || (time() % 100) < 5 ) {
    $sql = "DELETE FROM {$CFG->dbprefix}lti_message
        WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :max SECOND)
    ";

    $values = array(
        ':max' => $max_seconds,
    );

    $PDOX->queryDie($sql, $values);
    error_log("Cleanup..");
}

echo(json_encode($rows,JSON_PRETTY_PRINT));

