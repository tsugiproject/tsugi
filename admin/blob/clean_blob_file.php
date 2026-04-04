<?php

/**
 * Reconcile blob_file disk paths: report or fix legacy absolute prefixes, and remove rows
 * with no resolvable file (same resolution rules as blob serve — relative under dataroot;
 * old absolute prefix missing → try current dataroot + last three path segments).
 *
 * Complement to clean_dataroot_blobs.php (disk orphans). Rows stored only in blob_blob
 * (empty path) are skipped. After deletes, consider clean_blob_blob.php for orphan blob_blob.
 *
 * Usage (from tsugi/admin/blob):
 *   php clean_blob_file.php                    dry run: MISSING, MISMATCH (+ OK if -v)
 *   php clean_blob_file.php -v                 verbose: echo rows whose path already matches disk
 *   php clean_blob_file.php apply              UPDATE legacy paths; DELETE unresolvable rows
 *   php clean_blob_file.php remove             same as apply (backward compatible)
 *   php clean_blob_file.php fix                same as apply (same as former fix_blob_file_prefix.php)
 *
 * Path updates require file_sha256 to match hash_file() of the resolved path or the row is skipped.
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
$apply = false;
$verbose = false;
foreach ( $args as $a ) {
    if ( $a === 'apply' || $a === 'remove' || $a === 'fix' ) {
        $apply = true;
        continue;
    }
    if ( $a === '-v' || $a === '--verbose' || $a === 'verbose' ) {
        $verbose = true;
        continue;
    }
    fwrite(STDERR, "Unknown argument: {$a}\n");
    fwrite(STDERR, "Usage: php clean_blob_file.php [-v|--verbose|verbose] [apply|remove|fix]\n");
    exit(1);
}

if ( $apply ) {
    echo("This IS NOT A DRILL! Updating legacy blob_file.path values and deleting rows with no file on disk.\n");
    sleep(3);
    echo("...\n");
    sleep(3);
} else {
    echo("Dry run: MISSING = no file on disk (would delete on apply); MISMATCH = legacy path (would update on apply).\n");
    echo("Run: php clean_blob_file.php apply   (remove/fix are aliases for apply)\n");
    if ( $verbose ) {
        echo("Verbose: OK lines = path already matches disk.\n");
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
$missing = 0;
$mismatch = 0;
$deleted = 0;
$updated = 0;
$skipped_hash = 0;

while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
    $checked++;
    if ( $checked % 1000 === 0 ) {
        echo("# progress: {$checked} blob_file rows processed...\n");
    }
    $file_id = (int) $row['file_id'];
    $stored = $row['path'];
    $sha = $row['file_sha256'];

    $direct = BlobUtil::absoluteBlobPathFromStored($stored);
    if ( $direct === false ) {
        $missing++;
        echo("MISSING file_id={$file_id} sha256={$sha}\n");
        echo("  stored_path: {$stored}\n");
        echo("  (invalid or empty path after normalize)\n");
        echo("DELETE blob_file file_id={$file_id} sha256={$sha} path={$stored}\n");
        if ( $apply ) {
            $del = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :FID");
            $del->execute(array(':FID' => $file_id));
            $deleted++;
        }
        continue;
    }

    $resolved = BlobUtil::resolveDiskBlobPath($stored, false);
    if ( $resolved === false ) {
        $missing++;
        $lit = file_exists($direct) ? 'exists' : 'missing';
        echo("MISSING file_id={$file_id} sha256={$sha}\n");
        echo("  stored_path: {$stored}\n");
        echo("  literal_absolute: {$direct} ({$lit}); could not resolve on disk (no file at path or dataroot/last-3 fallback)\n");
        echo("DELETE blob_file file_id={$file_id} sha256={$sha} path={$stored}\n");
        if ( $apply ) {
            $del = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :FID");
            $del->execute(array(':FID' => $file_id));
            $deleted++;
        }
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

    if ( ! $apply ) {
        continue;
    }

    $disk_sha = @hash_file('sha256', $resolved);
    if ( $disk_sha === false || $disk_sha !== $sha ) {
        $skipped_hash++;
        echo("  SKIP path update: sha256 mismatch or unreadable file (db={$sha} disk=" .
            ($disk_sha === false ? 'n/a' : $disk_sha) . ")\n");
        continue;
    }

    $upd = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file SET path = :P WHERE file_id = :FID");
    $upd->execute(array(':P' => $resolved, ':FID' => $file_id));
    $updated++;
    echo("  UPDATED path -> {$resolved}\n");
}

echo('# blob_file rows checked=' . $checked .
    ' missing_unresolvable=' . $missing .
    ' prefix_mismatch=' . $mismatch .
    ($apply
        ? " deleted={$deleted} updated={$updated} skipped_bad_hash={$skipped_hash}"
        : " would_delete={$missing} would_update={$mismatch}") .
    ' elapsed=' . sprintf('%.2fs', microtime(true) - $t0) . "\n");
