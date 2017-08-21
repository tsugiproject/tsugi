<?php

if ( isset($_GET[session_name()]) ) {
    $cookie = false;
} else {
    define('COOKIE_SESSION', true);
    $cookie = true;
}

require_once "../../../../config.php";

$retval = \Tsugi\UI\Output::handleHeartBeat($cookie);

if ( isset($CFG->eventpushtime) && isset($CFG->eventpushcount) ) {
    $events = \Tsugi\Core\Activity::pushCaliperEvents($CFG->eventpushtime, $CFG->eventpushcount, false);
    error_log("Heartbeat events count=".$events['count']." time=".$events['seconds'].' Yada');
    $retval['events'] = $events;
}
echo(\Tsugi\Util\LTI::jsonIndent(json_encode($retval)));
