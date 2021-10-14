<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");
require_once("expire_util.php");

if ( ! U::isCli() ) die('Must be command line');

if ( ! isset($CFG->expire_pii_days) ) {
    die('Please set $CFG->expire_pii_days before running'."\n");
}

$days = $CFG->expire_pii_days;
$check = sanity_check_days('PII', $days);
if ( is_string($check) ) die($check."\n");

LTIX::getConnection();

$count = get_pii_count($days);
if ( $count < 1 ) {
    echo("No records to expire\n");
    return;
}

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'remove');

$sql = "UPDATE {$CFG->dbprefix}lti_user
    SET displayname=NULL, email=NULL " .get_pii_where($days);

echo($sql."\n");

if ( $dryrun ) {
    echo("This is a dry run, use 'php ".$argv[0]." remove' to actually remove the data.\n");
    echo("User records with PII and have not logged in in $days days: $count \n");
    return;
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$start = time();

$stmt = $PDOX->prepare($sql);
$stmt->execute();

$count = $stmt->rowCount();
echo("Rows updated: $count\n");
$delta = time() - $start;
echo("\nEllapsed time: $delta seconds\n");

