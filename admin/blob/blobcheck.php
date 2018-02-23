<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) die('Must be command line');

LTIX::getConnection();

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'remove');

if ( $dryrun ) {
    echo("This is a dry run, use 'php blobcheck.php remove' to actually remove the blobs.\n");
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$stmt = $PDOX->query("SELECT BB.blob_id, BB.blob_sha256, BB.created_at, BB.accessed_at
    FROM {$CFG->dbprefix}blob_blob AS BB 
    LEFT JOIN {$CFG->dbprefix}blob_file AS BF 
        ON BB.blob_sha256 = BF.file_sha256 AND BB.blob_id = BF.blob_id
    WHERE BF.blob_id IS NULL");
$stmt->execute();

$checked = 0;
$deleted = 0;

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $checked++;
    $blob_sha256 = $row['blob_sha256'];
    $blob_id = $row['blob_id'];
    if ( ! $blob_id ) continue;

    echo("DELETE $blob_id $blob_sha256\n");
    $deleted++;
    if ( ! $dryrun ) {
        $s2 = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_blob
            WHERE blob_id = :ID");
        $s2->execute(array(':ID' => $blob_id));
    }
}

echo("# unreferenced blobs found=$checked delete=$deleted\n");
