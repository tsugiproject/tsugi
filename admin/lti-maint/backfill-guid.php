<?php

use Tsugi\Controllers\Admin\Guid;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Fill NULL guid values on lti_user, lti_context, lti_key, and lti_link.
 *
 * Not called from the web, from admin/upgrade.php, or from database setup.
 * Safe to stop and run again. A guid that is already set is left alone.
 * Each row is its own UPDATE. The guid is chosen first and stored as a literal,
 * so a replica keeps the same bytes. A commit runs after each batch.
 *
 *   php admin/lti-maint/backfill-guid.php --dry-run
 *   php admin/lti-maint/backfill-guid.php --table=lti_user --batch-size=1000
 *   php admin/lti-maint/backfill-guid.php --seconds=15
 *   php admin/lti-maint/backfill-guid.php
 */

if ( PHP_SAPI !== 'cli'
    || ! isset($_SERVER['SCRIPT_FILENAME'])
    || realpath($_SERVER['SCRIPT_FILENAME']) !== realpath(__FILE__) ) {
    fwrite(STDERR, "backfill-guid.php must be run from the CLI\n");
    exit(1);
}

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../config.php';

if ( ! U::isCli() ) {
    fwrite(STDERR, "backfill-guid.php must be run from the CLI\n");
    exit(1);
}

$tsugi_guid_tables = array(
    'lti_user' => 'user_id',
    'lti_context' => 'context_id',
    'lti_key' => 'key_id',
    'lti_link' => 'link_id',
);

function tsugi_backfill_guid_usage() {
    global $tsugi_guid_tables;
    $names = implode('|', array_keys($tsugi_guid_tables));
    fwrite(STDOUT, "Usage: php admin/lti-maint/backfill-guid.php [--table={$names}] [--batch-size=N] [--seconds=N] [--dry-run]\n");
    fwrite(STDOUT, "Default batch size is 5000. Maximum is 50000. Omit --table to process all four tables.\n");
    fwrite(STDOUT, "Each row is its own UPDATE. A commit runs after each batch. --seconds stops the run early.\n");
    fwrite(STDOUT, "Does not replace an existing guid.\n");
}

function tsugi_backfill_guid_fail($message, $PDOX = null) {
    if ( $PDOX instanceof PDO && $PDOX->inTransaction() ) {
        try {
            $PDOX->rollBack();
        } catch ( Throwable $e ) {
            fwrite(STDERR, "rollback failed: ".$e->getMessage()."\n");
        }
    }
    fwrite(STDERR, $message."\n");
    exit(1);
}

function tsugi_backfill_guid_row($PDOX, $sql, $params = array()) {
    $q = $PDOX->queryReturnError($sql, $params);
    if ( ! $q->success ) {
        tsugi_backfill_guid_fail($q->errorImplode, $PDOX);
    }
    $row = $q->fetch(PDO::FETCH_ASSOC);
    $q->closeCursor();
    if ( $row === false ) return null;
    return $row;
}

$rest_index = 0;
$opts = getopt('', array('table:', 'batch-size:', 'seconds:', 'dry-run', 'help'), $rest_index);
if ( $opts === false ) {
    tsugi_backfill_guid_usage();
    exit(1);
}
if ( isset($opts['help']) ) {
    tsugi_backfill_guid_usage();
    exit(0);
}

for ( $i = 1; $i < count($argv); $i++ ) {
    $arg = $argv[$i];
    if ( $arg === '--dry-run' || $arg === '--help' ) continue;
    if ( strpos($arg, '--table=') === 0 || strpos($arg, '--batch-size=') === 0 || strpos($arg, '--seconds=') === 0 ) continue;
    if ( $arg === '--table' || $arg === '--batch-size' || $arg === '--seconds' ) {
        $i++;
        continue;
    }
    fwrite(STDERR, "Unknown argument: {$arg}\n");
    tsugi_backfill_guid_usage();
    exit(1);
}

$batch_size = 5000;
if ( isset($opts['batch-size']) ) {
    if ( is_array($opts['batch-size']) ) {
        fwrite(STDERR, "Pass --batch-size once\n");
        exit(1);
    }
    $raw_batch = (string) $opts['batch-size'];
    if ( ! preg_match('/^[1-9][0-9]*$/', $raw_batch) ) {
        fwrite(STDERR, "batch-size must be an integer from 1 to 50000\n");
        exit(1);
    }
    $batch_size = (int) $raw_batch;
    if ( $batch_size > 50000 ) {
        fwrite(STDERR, "batch-size must be an integer from 1 to 50000\n");
        exit(1);
    }
}

$seconds = null;
if ( isset($opts['seconds']) ) {
    if ( is_array($opts['seconds']) ) {
        fwrite(STDERR, "Pass --seconds once\n");
        exit(1);
    }
    $raw_seconds = (string) $opts['seconds'];
    if ( ! preg_match('/^[1-9][0-9]*$/', $raw_seconds) ) {
        fwrite(STDERR, "seconds must be an integer from 1 to 86400\n");
        exit(1);
    }
    $seconds = (int) $raw_seconds;
    if ( $seconds > 86400 ) {
        fwrite(STDERR, "seconds must be an integer from 1 to 86400\n");
        exit(1);
    }
}

$selected = array_keys($tsugi_guid_tables);
if ( isset($opts['table']) ) {
    if ( is_array($opts['table']) ) {
        fwrite(STDERR, "Pass --table once, or omit it to process all four tables\n");
        exit(1);
    }
    $table_arg = (string) $opts['table'];
    if ( ! isset($tsugi_guid_tables[$table_arg]) ) {
        fwrite(STDERR, "Unknown table: {$table_arg}\n");
        tsugi_backfill_guid_usage();
        exit(1);
    }
    $selected = array($table_arg);
}

$dry_run = isset($opts['dry-run']);

LTIX::getConnection();
global $CFG, $PDOX;

if ( ! isset($CFG->dirroot) || realpath($CFG->dirroot) !== realpath(__DIR__ . '/../..') ) {
    tsugi_backfill_guid_fail('Refusing to run: database dirroot is not this Tsugi checkout');
}

$prefix = (string) $CFG->dbprefix;
if ( ! preg_match('/^[A-Za-z0-9_]*$/', $prefix) ) {
    tsugi_backfill_guid_fail('Refusing to run: dbprefix is not a plain identifier');
}

$server = $PDOX->serverVersion();
$version = $server['raw'];
if ( ! $server['modern'] ) {
    $bar = str_repeat('*', 64);
    fwrite(STDERR, "\n".$bar."\n");
    fwrite(STDERR, "*** WARNING: MySQL version {$version} is older than ".TSUGI_MINIMUM_MYSQL.".\n");
    fwrite(STDERR, "*** Tsugi recommends MySQL ".TSUGI_MINIMUM_MYSQL." or newer.\n");
    fwrite(STDERR, "*** Continuing the GUID backfill.\n");
    fwrite(STDERR, $bar."\n\n");
}
echo("server VERSION={$version} one UPDATE per row, commit every {$batch_size}\n");
if ( $seconds !== null ) {
    echo("stop after {$seconds} seconds\n");
}

if ( $dry_run ) {
    echo("dry-run: no rows will be updated\n");
}

$started = microtime(true);
$deadline = $seconds === null ? null : $started + $seconds;
$total_updated = 0;
$total_remaining = 0;
$time_up = false;

foreach ( $selected as $logical ) {
    $pk = $tsugi_guid_tables[$logical];
    $table_name = $prefix.$logical;
    $physical = '`'.$table_name.'`';

    $column = tsugi_backfill_guid_row($PDOX,
        "SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = 'guid'",
        array(':table_name' => $table_name));
    if ( $column === null ) {
        tsugi_backfill_guid_fail("{$table_name}.guid is missing. Run php admin/upgrade.php first.");
    }
    $column_type = strtolower((string) $column['column_type']);
    $nullable = strtoupper((string) $column['is_nullable']);
    if ( $column_type !== 'binary(16)' || $nullable !== 'YES' ) {
        tsugi_backfill_guid_fail("{$table_name}.guid is {$column_type} nullable={$nullable}; expected binary(16) YES.");
    }

    $count_row = tsugi_backfill_guid_row($PDOX,
        "SELECT COUNT(*) AS n FROM {$physical} WHERE guid IS NULL");
    $null_count = (int) $count_row['n'];

    if ( $dry_run ) {
        $batches = $null_count === 0 ? 0 : (int) ceil($null_count / $batch_size);
        echo("{$logical} ({$pk}): {$null_count} NULL guid rows, one UPDATE per row, {$batches} commit(s) of {$batch_size}\n");
        $total_remaining += $null_count;
        continue;
    }

    if ( $deadline !== null && microtime(true) >= $deadline ) {
        echo("{$logical} ({$pk}): time limit reached, {$null_count} NULL guid rows left on this table\n");
        $total_remaining += $null_count;
        $time_up = true;
        continue;
    }

    echo("{$logical} ({$pk}): starting, {$null_count} NULL guid rows, one UPDATE per row, commit every {$batch_size}\n");
    fflush(STDOUT);

    $cumulative = 0;
    $table_started = microtime(true);
    $run = Guid::fillUntil($table_name, $pk, PHP_INT_MAX, $deadline, $batch_size, function ($info) use ($logical, &$cumulative) {
        $cumulative = (int) $info['cumulative'];
        $updated = (int) $info['updated'];
        $elapsed = (float) $info['elapsed'];
        $rate = $elapsed > 0.0 ? ($updated / $elapsed) : 0.0;
        printf(
            "%s batch %d: updated %d in %.3fs (%.1f rows/s) cumulative %d, id span %d-%d\n",
            $logical,
            (int) $info['batch'],
            $updated,
            $elapsed,
            $rate,
            $cumulative,
            (int) $info['lo'],
            (int) $info['hi']
        );
        fflush(STDOUT);
    });
    if ( ! $run['ok'] ) {
        tsugi_backfill_guid_fail("{$logical}: ".$run['text'], $PDOX);
    }
    if ( $run['stopped'] === 'time' ) {
        $time_up = true;
    }

    $remaining_row = tsugi_backfill_guid_row($PDOX,
        "SELECT COUNT(*) AS n FROM {$physical} WHERE guid IS NULL");
    $remaining = (int) $remaining_row['n'];
    $table_elapsed = microtime(true) - $table_started;
    $table_rate = $table_elapsed > 0.0 ? ($cumulative / $table_elapsed) : 0.0;
    printf(
        "%s done: updated %d in %.3fs (%.1f rows/s), remaining NULL %d\n",
        $logical,
        $cumulative,
        $table_elapsed,
        $table_rate,
        $remaining
    );
    fflush(STDOUT);
    $total_updated += $cumulative;
    $total_remaining += $remaining;
}

$total_elapsed = microtime(true) - $started;
if ( $dry_run ) {
    printf("dry-run total: %.3fs, NULL guid rows %d\n", $total_elapsed, $total_remaining);
    exit(0);
}

printf("total: updated %d in %.3fs, remaining NULL %d\n", $total_updated, $total_elapsed, $total_remaining);
if ( $total_remaining > 0 ) {
    if ( $time_up ) {
        fwrite(STDERR, "Stopped at the time limit. NULL guid rows remain. The run is safe to repeat.\n");
    } else {
        fwrite(STDERR, "NULL guid rows remain. The run is safe to repeat.\n");
    }
    exit(1);
}
exit(0);
