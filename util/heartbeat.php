<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

$cookie = defined('COOKIE_SESSION');

$retval = \Tsugi\UI\Output::handleHeartBeat($cookie);

if ( isset($CFG->eventpushtime) && isset($CFG->eventpushcount) && $CFG->eventpushcount > 0 ) {
    $events = \Tsugi\Core\Activity::pushCaliperEvents($CFG->eventpushtime, $CFG->eventpushcount, false);
    if ( isset($events['count']) && $events['count'] > 0 ) {
        error_log("Heartbeat events count=".$events['count']." time=".$events['seconds']);
    }
    $retval['events'] = $events;
}
echo(\Tsugi\Util\LTI::jsonIndent(json_encode($retval)));
