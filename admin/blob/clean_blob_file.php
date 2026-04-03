<?php

/**
 * Remove blob_file rows whose path has no backing file on disk (after files were removed).
 *
 * Complement to clean_dataroot_blobs.php: that script removes disk files with no blob_file row; this script
 * removes blob_file rows when the stored path does not exist (e.g. after age-based deletion).
 *
 * Usage:
 *   php clean_blob_file.php           dry run: list rows that would be deleted
 *   php clean_blob_file.php remove    delete those rows from blob_file
 *
 * Only considers rows with a non-empty path (disk-backed blobs). Rows stored only
 * in blob_blob (path empty) are skipped. After removing rows, consider running
 * clean_blob_blob.php for orphan blob_blob rows.
 */

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

LTIX::getConnection();

$dryrun = ! ( isset($argv[1]) && $argv[1] === 'remove' );

if ( $dryrun ) {
    echo("Dry run: rows listed below would be deleted. Run: php clean_blob_file.php remove\n");
} else {
    echo("This IS NOT A DRILL! Deleting blob_file rows with missing files.\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$sql = "SELECT file_id, file_sha256, path
    FROM {$CFG->dbprefix}blob_file
    WHERE path IS NOT NULL AND path <> ''";

$stmt = $PDOX->queryReturnError($sql);
if ( $stmt === false ) {
    die("Query failed\n");
}

$checked = 0;
$missing = 0;
$deleted = 0;

while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
    $checked++;
    $file_id = (int) $row['file_id'];
    $path = $row['path'];
    $sha = $row['file_sha256'];

    if ( file_exists($path) ) {
        continue;
    }

    $missing++;
    echo("DELETE blob_file file_id={$file_id} sha256={$sha} path={$path}\n");
    $deleted++;

    if ( ! $dryrun ) {
        $del = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :FID");
        $del->execute(array(':FID' => $file_id));
    }
}

echo("# blob_file rows checked={$checked} missing_backing_file={$missing} " .
    ($dryrun ? "would_delete={$deleted}\n" : "deleted={$deleted}\n"));
