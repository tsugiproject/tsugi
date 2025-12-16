<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");
require_once("expire_util.php");

if ( ! U::isCli() ) die('Must be command line');

if ( ! isset($argv[1]) ) die("user / context / tenant required\n");

$base = $argv[1];
if ( $base == 'user' ) {
    $table = 'lti_user';
    $limit = 12000; // Takes about 10 seconds
    $where = '';
} else if ( $base == 'context' ) {
    $table = 'lti_context';
    $limit = 100;
    $where = '';
} else if ( $base == 'tenant' ) {
    $table = 'lti_key';
    $limit = 1;
    $where = " AND ".get_safe_key_where().' ';
} else {
    die('Invalid data value');
}

$dryrun = ! ( isset($argv[2]) && $argv[2] == 'remove');

$cfg = 'expire_'.$base.'_days';
if ( ! isset($CFG->{$cfg}) ) {
    die('Please set $CFG->'.$cfg.' before running'."\n");
}
$days = $CFG->{$cfg} + 0;

$check = sanity_check_days('PII', $days);
if ( is_string($check) ) die($check."\n");

LTIX::getConnection();

$count = get_expirable_records($table, $days);
if ( $count < 1 ) {
    echo("No records to expire\n");
    return;
}

// Validate limit is a safe integer (MySQL LIMIT doesn't support parameters)
if ( !is_numeric($limit) || $limit < 1 ) die('Invalid limit value');
$limit = (int)$limit;

$where_data = get_expirable_where($days);
$sql = "DELETE FROM {$CFG->dbprefix}{$table}\n".
    $where_data['sql']."\n".$where.
    "ORDER BY login_at LIMIT " . $limit;
$params = $where_data['params'];

// Create display version of SQL with actual values substituted (for display only)
$sql_display = \Tsugi\Util\PDOX::sqlDisplay($sql, $params);
echo($sql_display."\n");

if ( $dryrun ) {
    echo("This is a dry run, use 'php ".$argv[0]." $base remove' to actually remove the data.\n");
    echo(htmlentities(ucfirst($base))." records have not logged in in $days days: $count \n");
    return;
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$start = time();

$stmt = $PDOX->prepare($sql);
$stmt->execute($params);

$count = $stmt->rowCount();
echo("Rows updated: $count\n");
$delta = time() - $start;
echo("\nEllapsed time: $delta seconds\n");

