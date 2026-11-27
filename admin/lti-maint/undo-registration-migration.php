<?php

/**
 * Drop the inbound lti_registration table and the lti_key columns added with it.
 *
 * Not called from the web, from admin/upgrade.php, or from database setup.
 * A dry run is the default. apply drops the foreign key, then the columns,
 * then the table. Each statement auto-commits. A second apply finds nothing
 * left and exits.
 *
 * The script stops when lti_registration still has rows, when a key still
 * points at a registration, or when one of the added columns holds a value.
 * Copy those values back onto lti_key and delete the registration rows first.
 *
 * deployment_status and deployment_approved_at are inbound-client columns on
 * lti_key now. platform_guid is the observational platform guid. This script
 * does not drop them.
 *
 *   php admin/lti-maint/undo-registration-migration.php
 *   php admin/lti-maint/undo-registration-migration.php apply
 */

use Tsugi\Core\LTIX;
use Tsugi\Util\U;

if ( PHP_SAPI !== 'cli'
    || ! isset($_SERVER['SCRIPT_FILENAME'])
    || realpath($_SERVER['SCRIPT_FILENAME']) !== realpath(__FILE__) ) {
    fwrite(STDERR, "undo-registration-migration.php must be run from the CLI\n");
    exit(1);
}

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../config.php';

if ( ! U::isCli() ) {
    fwrite(STDERR, "undo-registration-migration.php must be run from the CLI\n");
    exit(1);
}

if ( realpath($CFG->dirroot) !== realpath(__DIR__ . '/../..') ) {
    fwrite(STDERR, "dirroot is {$CFG->dirroot}, expected this checkout.\n");
    exit(1);
}

$apply = false;
foreach ( array_slice($argv, 1) as $arg ) {
    if ( $arg === 'apply' ) {
        $apply = true;
        continue;
    }
    if ( $arg === 'dry' || $arg === 'dry-run' || $arg === '--dry-run' ) {
        continue;
    }
    fwrite(STDERR, "Unknown argument: {$arg}\n");
    fwrite(STDERR, "Usage: php undo-registration-migration.php [apply]\n");
    exit(1);
}

LTIX::getConnection();

if ( $PDOX->inTransaction() ) {
    fwrite(STDERR, "Refusing to run inside a transaction.\n");
    exit(1);
}

$p = $CFG->dbprefix;
$keyTable = $p.'lti_key';
$regTable = $p.'lti_registration';
$fkName = $p.'lti_key_ibfk_1';
$columns = array(
    'lti_registration_id',
    'dynamic_registration_unlocked_at',
);

/**
 * @param string $line
 */
function undo_registration_out($line)
{
    echo $line."\n";
    flush();
}

/**
 * @param string $sql
 * @param array<string, mixed> $params
 * @return array<string, mixed>|null
 */
function undo_registration_row($PDOX, $sql, $params = array())
{
    $q = $PDOX->queryReturnError($sql, $params);
    if ( ! $q->success ) {
        fwrite(STDERR, $q->errorImplode."\n");
        exit(1);
    }
    $row = $q->fetch(\PDO::FETCH_ASSOC);
    $q->closeCursor();
    if ( $row === false ) return null;
    return $row;
}

/**
 * @param string $sql
 */
function undo_registration_exec($PDOX, $sql)
{
    undo_registration_out($sql);
    $q = $PDOX->queryReturnError($sql);
    if ( ! $q->success ) {
        fwrite(STDERR, $q->errorImplode."\n");
        exit(1);
    }
}

$regExists = $PDOX->metadata($regTable) !== false;
$keyExists = $PDOX->metadata($keyTable) !== false;
if ( ! $keyExists ) {
    fwrite(STDERR, "{$keyTable} is missing.\n");
    exit(1);
}

$present = array();
foreach ( $columns as $column ) {
    if ( $PDOX->columnExists($column, $keyTable) ) {
        $present[] = $column;
    }
}

$fk = undo_registration_row(
    $PDOX,
    "SELECT CONSTRAINT_NAME
     FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = :TABLE_NAME
       AND CONSTRAINT_NAME = :CONSTRAINT_NAME
       AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
    array(':TABLE_NAME' => $keyTable, ':CONSTRAINT_NAME' => $fkName)
);
$fkExists = is_array($fk);

$blockers = array();
if ( $regExists ) {
    $countRow = undo_registration_row($PDOX, "SELECT COUNT(*) AS n FROM {$regTable}");
    $regCount = (int) ($countRow['n'] ?? 0);
    undo_registration_out("{$regTable} rows: {$regCount}");
    if ( $regCount > 0 ) {
        $blockers[] = "{$regTable} has {$regCount} row(s). Copy issuer, client id, and URLs back onto {$keyTable}, delete those rows, then run this again.";
    }
} else {
    undo_registration_out("{$regTable} is already absent");
}

foreach ( $present as $column ) {
    $countRow = undo_registration_row(
        $PDOX,
        "SELECT COUNT(*) AS n FROM {$keyTable} WHERE {$column} IS NOT NULL"
    );
    $filled = (int) ($countRow['n'] ?? 0);
    undo_registration_out("{$keyTable}.{$column}: present, {$filled} non-null");
    if ( $filled > 0 ) {
        $blockers[] = "{$keyTable}.{$column} is set on {$filled} key(s). This script does not keep that value.";
    }
}

foreach ( $columns as $column ) {
    if ( ! in_array($column, $present, true) ) {
        undo_registration_out("{$keyTable}.{$column} is already absent");
    }
}

if ( $fkExists ) {
    undo_registration_out("foreign key {$fkName} is present");
} else {
    undo_registration_out("foreign key {$fkName} is already absent");
}

if ( count($blockers) > 0 ) {
    foreach ( $blockers as $blocker ) {
        fwrite(STDERR, $blocker."\n");
    }
    exit(1);
}

$work = $fkExists || count($present) > 0 || $regExists;
if ( ! $work ) {
    undo_registration_out("Nothing to drop.");
    exit(0);
}

if ( ! $apply ) {
    if ( $fkExists ) undo_registration_out("Would drop foreign key {$fkName}");
    foreach ( $present as $column ) {
        undo_registration_out("Would drop {$keyTable}.{$column}");
    }
    if ( $regExists ) undo_registration_out("Would drop table {$regTable}");
    undo_registration_out("Dry run. php admin/lti-maint/undo-registration-migration.php apply");
    exit(0);
}

undo_registration_out("This IS NOT A DRILL. Dropping the registration table and columns in 10 seconds.");
sleep(10);

if ( $fkExists ) {
    undo_registration_exec($PDOX, "ALTER TABLE `{$keyTable}` DROP FOREIGN KEY `{$fkName}`");
}
foreach ( $present as $column ) {
    undo_registration_exec($PDOX, "ALTER TABLE `{$keyTable}` DROP COLUMN `{$column}`");
}
if ( $regExists ) {
    undo_registration_exec($PDOX, "DROP TABLE `{$regTable}`");
}

undo_registration_out("Done.");
