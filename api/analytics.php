<?php

require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Event\Entry;
use \Tsugi\Core\Rest;
use \Tsugi\Util\U;

if ( Rest::preFlight() ) return;

header('Content-Type: application/json; charset=utf-8');

// LTI/cookieless endpoint: requires an LTI launch and uses the current $LINK.
$LAUNCH = LTIX::requireData();
$link_id = $LINK->id + 0;

$sql = "SELECT link_count, activity FROM {$CFG->dbprefix}lti_link_activity
    WHERE link_id = :link_id AND event = 0";
$values = array(':link_id' => $link_id);
$row = $PDOX->rowDie($sql, $values);

$ent = new Entry();
if ( is_array($row) ) {
    $ent->deSerialize($row['activity']);
    $ent->total = $row['link_count']+0;
}
$retval = $ent->viewModel();

echo(json_encode($retval,JSON_PRETTY_PRINT));
