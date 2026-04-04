<?php

/**
 * List blob_file rows whose stored path differs from the file Tsugi actually serves
 * (legacy absolute prefix under an old dataroot; serve remaps via last-three segments).
 * Optionally rewrite path to the resolved absolute path under the current dataroot.
 *
 * Usage (from tsugi/admin/blob):
 *   php fix_blob_file_prefix.php              dry run: report mismatches
 *   php fix_blob_file_prefix.php -v           also echo each row OK (on disk, path matches literal)
 *   php fix_blob_file_prefix.php fix          UPDATE path to match resolved on-disk file
 *   php fix_blob_file_prefix.php -v fix       fix + verbose (order of flags free)
 *
 * Requires $CFG->dataroot. Rows with no resolvable file are skipped (see clean_blob_file.php).
 * On fix, file_sha256 must match hash_file() of the resolved path or the row is skipped.
 */

use \Tsugi\Blob\BlobUtil;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

require_once '../../config.php';

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

LTIX::getConnection();
$t0 = microtime(true);

if ( ! isset($CFG->dataroot) || U::strlen((string) $CFG->dataroot) < 1 ) {
    fwrite(STDERR, "CFG->dataroot is not set; nothing to do.\n");
    exit(1);
}

$args = array_slice($argv, 1);
$do_fix = false;
$verbose = false;
foreach ( $args as $a ) {
    if ( $a === 'fix' ) {
        $do_fix = true;
        continue;
    }
    if ( $a === '-v' || $a === '--verbose' || $a === 'verbose' ) {
        $verbose = true;
        continue;
    }
    fwrite(STDERR, "Unknown argument: {$a}\n");
    fwrite(STDERR, "Usage: php fix_blob_file_prefix.php [-v|--verbose|verbose] [fix]\n");
    exit(1);
}

if ( $do_fix ) {
    echo("This IS NOT A DRILL! Updating blob_file.path to resolved absolute paths.\n");
    sleep(3);
    echo("...\n");
    sleep(3);
} else {
    echo("Dry run: mismatches listed below. Run: php fix_blob_file_prefix.php fix\n");
    if ( $verbose ) {
        echo("Verbose: OK lines list rows whose file exists and stored path matches (no prefix fix needed).\n");
    }
}

$sql = "SELECT file_id, file_sha256, path
    FROM {$CFG->dbprefix}blob_file
    WHERE path IS NOT NULL AND path <> ''";

$stmt = $PDOX->queryReturnError($sql);
if ( $stmt === false ) {
    die("Query failed\n");
}

$checked = 0;
$mismatch = 0;
$updated = 0;
$skipped_hash = 0;

while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
    $checked++;
    $file_id = (int) $row['file_id'];
    $stored = $row['path'];
    $sha = $row['file_sha256'];

    $direct = BlobUtil::absoluteBlobPathFromStored($stored);
    if ( $direct === false ) {
        continue;
    }

    $resolved = BlobUtil::resolveDiskBlobPath($stored, false);
    if ( $resolved === false ) {
        continue;
    }

    $same = ($resolved === $direct);
    if ( ! $same && file_exists($direct) ) {
        $rd = @realpath($direct);
        $rr = @realpath($resolved);
        if ( $rd !== false && $rr !== false && $rd === $rr ) {
            $same = true;
        }
    }

    if ( $same ) {
        if ( $verbose ) {
            echo("OK file_id={$file_id} sha256={$sha}\n");
            echo("  stored_path: {$stored}\n");
            echo("  on_disk: {$resolved} (literal path matches; no remap needed)\n");
        }
        continue;
    }

    $mismatch++;
    $literal_status = file_exists($direct) ? 'exists' : 'missing';
    echo("MISMATCH file_id={$file_id} sha256={$sha}\n");
    echo("  stored_path: {$stored}\n");
    echo("  literal_absolute: {$direct} ({$literal_status})\n");
    echo("  resolves_to: {$resolved}\n");

    if ( ! $do_fix ) {
        continue;
    }

    $disk_sha = @hash_file('sha256', $resolved);
    if ( $disk_sha === false || $disk_sha !== $sha ) {
        $skipped_hash++;
        echo("  SKIP fix: sha256 mismatch or unreadable file (db={$sha} disk=" .
            ($disk_sha === false ? 'n/a' : $disk_sha) . ")\n");
        continue;
    }

    $upd = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file SET path = :P WHERE file_id = :FID");
    $upd->execute(array(':P' => $resolved, ':FID' => $file_id));
    $updated++;
    echo("  UPDATED path -> {$resolved}\n");
}

echo('# blob_file rows checked=' . $checked .
    ' prefix_mismatch=' . $mismatch .
    ($do_fix ? " updated={$updated} skipped_bad_hash={$skipped_hash}" : '') .
    ' elapsed=' . sprintf('%.2fs', microtime(true) - $t0) . "\n");
