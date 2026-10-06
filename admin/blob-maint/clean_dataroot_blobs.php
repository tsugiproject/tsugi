<?php

/**
 * Remove on-disk blobs under $CFG->dataroot that have no row in blob_file (disk orphans).
 *
 * ============================================================================
 * IMPORTANT — ORDER OF OPERATIONS (read before running, especially `remove`)
 * ============================================================================
 * Do NOT run this script (especially with `remove`) until **clean_blob_file.php**
 * has been run and you have a clean bill of health (no unexpected MISSING rows,
 * legacy paths reconciled with `apply` as needed).
 *
 * This tool decides “orphan” by matching **file_sha256** on disk to **blob_file**.
 * Rows whose **path** still points at an old dataroot prefix (while the bytes live
 * under the current tree) are invisible to that lookup until **clean_blob_file.php**
 * fixes or removes them. Running **clean_dataroot_blobs.php remove** first can
 * **delete real blob files** that are still logically in use — data loss.
 *
 * The script **refuses to run** if any **blob_file.path** is an absolute path that
 * does not lie under the current **$CFG->dataroot** (relative paths are allowed).
 * Run **clean_blob_file.php** until those rows are fixed or removed.
 * ============================================================================
 *
 * Walks the two-level SHA-256 layout; optional verbose: php clean_dataroot_blobs.php verbose
 *
 * Usage:
 *   php clean_dataroot_blobs.php           dry run
 *   php clean_dataroot_blobs.php remove    unlink orphan files and empty leaf dirs
 */

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

LTIX::getConnection();

if ( ! $CFG->dataroot ) {
    die("\$CFG->dataroot not configured\n");
}
$directory = $CFG->dataroot;
if ( ! file_exists($directory) ) {
    die("\$CFG->dataroot does not exist\n");
}

$dataroot_norm = rtrim((string) $CFG->dataroot, '/');
$p = $CFG->dbprefix;
$sql_legacy = "SELECT COUNT(*) AS c FROM {$p}blob_file
    WHERE path IS NOT NULL AND path <> ''
    AND path LIKE '/%'
    AND path != :DR
    AND path NOT LIKE :DRSLASH";
$stmt = $PDOX->prepare($sql_legacy);
$ok = $stmt->execute(array(
    ':DR' => $dataroot_norm,
    ':DRSLASH' => $dataroot_norm . '/%',
));
if ( $ok === false ) {
    die("Could not check blob_file paths for dataroot prefix\n");
}
$legacy_row = $stmt->fetch(\PDO::FETCH_ASSOC);
$legacy_count = $legacy_row ? (int) $legacy_row['c'] : 0;
if ( $legacy_count > 0 ) {
    fwrite(STDERR, "Refusing to run: {$legacy_count} blob_file row(s) have an absolute path not under \$CFG->dataroot.\n");
    fwrite(STDERR, "dataroot: {$dataroot_norm}\n");
    fwrite(STDERR, "Run clean_blob_file.php (dry run, then apply) to fix or remove those rows, then retry.\n");
    $sample = $PDOX->prepare("SELECT file_id, path FROM {$p}blob_file
        WHERE path IS NOT NULL AND path <> ''
        AND path LIKE '/%'
        AND path != :DR
        AND path NOT LIKE :DRSLASH
        LIMIT 8");
    $sample->execute(array(':DR' => $dataroot_norm, ':DRSLASH' => $dataroot_norm . '/%'));
    while ( $s = $sample->fetch(\PDO::FETCH_ASSOC) ) {
        fwrite(STDERR, "  file_id={$s['file_id']} path={$s['path']}\n");
    }
    exit(1);
}

$t0 = microtime(true);

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'remove' );
$verbose = isset($argv[1]) && $argv[1] == 'verbose';

echo("\n");
echo("================================================================================\n");
echo("NOTE: Run clean_blob_file.php first (dry run, then apply if needed) until the\n");
echo("      DB paths match disk. Otherwise legacy blob_file.path values can hide\n");
echo("      references and this script may DELETE FILES THAT ARE STILL IN USE.\n");
echo("================================================================================\n");
echo("\n");

if ( $dryrun ) {
    echo("This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.\n");
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$dscan = 0;
$dskip = 0;
$ddel = 0;
$fscan = 0;
$fskip = 0;
$fgood = 0;
$fdel = 0;
$walked = 0;

function scanFolder($directory, $top) {
    global $CFG, $PDOX, $verbose, $dryrun;
    global $fscan, $dscan, $ddel, $fdel, $dskip, $fskip, $fgood, $walked;

    $fcount = 0;
    foreach ( glob("{$directory}/*") as $file ) {
        $walked++;
        if ( $walked % 1000 === 0 ) {
            echo("# progress: {$walked} dataroot tree entries processed...\n");
        }
        if ( is_dir($file) ) {
            $fcount++;
            $dscan++;
            if ( preg_match('/^[0-9][0-9]/', basename($file)) ) {
                if ( $verbose ) {
                    echo("Recurse folder $file\n");
                }
                $fcount += scanFolder($file, false);
            } elseif ( preg_match('/^[0-9a-f][0-9a-f]/', basename($file)) ) {
                if ( $verbose ) {
                    echo("Recurse folder $file\n");
                }
                $fcount += scanFolder($file, false);
            } else {
                $dskip++;
                echo("Skipping folder $file\n");
            }
        } else {
            $fcount++;
            $fscan++;
            if ( ! preg_match('/^[0-9a-f]+$/', basename($file)) ) {
                echo("Skipping file $file\n");
                $fskip++;
                continue;
            }
            $sha_256 = basename($file);
            $stmt = $PDOX->prepare("SELECT file_sha256 FROM {$CFG->dbprefix}blob_file
                    WHERE file_sha256 = :SHA LIMIT 1");
            $stmt->execute(array(":SHA" => $sha_256));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ( $row ) {
                if ( $verbose ) {
                    echo("OK $file\n");
                }
                $fgood++;
                continue;
            }
            echo("rm $file\n");
            $fdel++;
            if ( ! $dryrun ) {
                unlink($file);
                $fcount--;
            }
        }
    }
    if ( ! $top && $fcount == 0 ) {
        $ddel++;
        echo("rmdir $directory\n");
        if ( ! $dryrun ) {
            rmdir($directory);
        }
        return -1;
    }
    return $fcount;
}

scanFolder($directory, true);

if ( $dryrun ) {
    echo("This is a dry run, use 'php clean_dataroot_blobs.php remove' to actually remove the files.\n");
}
echo("# folders scan=$dscan skip=$dskip rm=$ddel\n");
echo("# files scan=$fscan skip=$fskip good=$fgood rm=$fdel elapsed=" .
    sprintf('%.2fs', microtime(true) - $t0) . "\n");
