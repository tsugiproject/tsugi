<?php

/**
 * Remove orphan rows from blob_blob: no blob_file references the same blob_id / blob_sha256.
 *
 * Usage:
 *   php clean_blob_blob.php           dry run
 *   php clean_blob_blob.php remove    delete those blob_blob rows
 */

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

LTIX::getConnection();
$t0 = microtime(true);

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'remove' );

if ( $dryrun ) {
    echo("This is a dry run, use 'php clean_blob_blob.php remove' to actually remove the rows.\n");
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$sql = "SELECT BB.blob_id, BB.blob_sha256, BB.created_at, BB.accessed_at
    FROM {$CFG->dbprefix}blob_blob AS BB
    LEFT JOIN {$CFG->dbprefix}blob_file AS BF
        ON BB.blob_sha256 = BF.file_sha256 AND BB.blob_id = BF.blob_id
    WHERE BF.blob_id IS NULL";

$stmt = $PDOX->queryReturnError($sql);
if ( $stmt === false ) {
    die("Query failed\n");
}

$checked = 0;
$deleted = 0;

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $checked++;
    $blob_sha256 = $row['blob_sha256'];
    $blob_id = $row['blob_id'];
    if ( ! $blob_id ) {
        continue;
    }

    echo("DELETE blob_blob blob_id=$blob_id $blob_sha256\n");
    $deleted++;
    if ( ! $dryrun ) {
        $s2 = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_blob
            WHERE blob_id = :ID");
        $s2->execute(array(':ID' => $blob_id));
    }
}

echo("# orphan blob_blob rows found=$checked delete=$deleted elapsed=" .
    sprintf('%.2fs', microtime(true) - $t0) . "\n");
