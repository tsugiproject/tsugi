<?php

require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Event\Entry;

$LAUNCH = LTIX::requireData();

$sql = "SELECT link_count, activity FROM {$CFG->dbprefix}lti_link_activity
    WHERE link_id = :link_id";
$values = array(':link_id' => $LINK->id);
$row = $PDOX->rowDie($sql, $values);

$ent = new Entry();
$ent->deSerialize($row['activity']);
$ent->total = $row['link_count']+0;
$retval = $ent->viewModel();

echo(json_encode($retval,JSON_PRETTY_PRINT));
