<?php

/**
 * Scan peer_submit rows for blob_ids / pdf_ids pointing at blob_file.file_id.
 * If any referenced file_id has no row in blob_file, the submission is broken.
 * Rows with invalid JSON in the json column are also removed (blobs found via
 * backref peer_submit::submit_id::<submit_id> when possible).
 * Optionally delete those submissions (and related peer_text), and remove any
 * blob_file rows still present for referenced or backref-linked file_ids.
 *
 * Usage (from mod/peer-grade):
 *   php cleanup_peer_submit_missing_blobs.php              dry run (default)
 *   php cleanup_peer_submit_missing_blobs.php -v           dry run, echo each row as scanned
 *   php cleanup_peer_submit_missing_blobs.php remove       apply deletions
 *   php cleanup_peer_submit_missing_blobs.php -v remove    verbose + remove
 *
 * Verbose: -v, --verbose, or verbose. The word "remove" must be the last argument.
 */

use Tsugi\Blob\BlobUtil;
use Tsugi\Core\Cache;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

require_once __DIR__ . '/../config.php';

if ( ! U::isCli() ) {
    die("Must be run from the command line.\n");
}

$args = array_slice($argv, 1);
$do_remove = false;
if ( count($args) > 0 && end($args) === 'remove' ) {
    $do_remove = true;
    array_pop($args);
}
$verbose = false;
$unknown = array();
foreach ( $args as $a ) {
    if ( $a === '-v' || $a === '--verbose' || $a === 'verbose' ) {
        $verbose = true;
        continue;
    }
    $unknown[] = $a;
}
if ( count($unknown) > 0 ) {
    fwrite(STDERR, "Unknown arguments: " . implode(' ', $unknown) . "\n");
    fwrite(STDERR, "Usage: php cleanup_peer_submit_missing_blobs.php [-v|--verbose] [remove]\n");
    exit(1);
}

LTIX::getConnection();
global $CFG, $PDOX;

$p = $CFG->dbprefix;
$t0 = microtime(true);

if ( $do_remove ) {
    echo("This IS NOT A DRILL! Deleting peer_submit rows (missing blobs and/or bad JSON).\n");
    echo("...\n");
    sleep(5);
} else {
    echo("Dry run: orphan / bad_json submissions listed below would be deleted. Run: php cleanup_peer_submit_missing_blobs.php remove\n");
}

/**
 * @param object|null $json Decoded submission json
 * @return int[] Unique positive file_ids from blob_ids and pdf_ids
 */
function peer_submit_referenced_file_ids($json) {
    $ids = array();
    if ( ! is_object($json) ) {
        return $ids;
    }
    foreach ( array('blob_ids', 'pdf_ids') as $key ) {
        if ( ! isset($json->$key) || ! is_array($json->$key) ) {
            continue;
        }
        foreach ( $json->$key as $entry ) {
            $id = $entry;
            if ( is_array($entry) ) {
                $id = isset($entry[0]) ? $entry[0] : null;
            }
            if ( $id === null || $id === '' ) {
                continue;
            }
            $id = (int) $id;
            if ( $id > 0 ) {
                $ids[$id] = true;
            }
        }
    }
    return array_map('intval', array_keys($ids));
}

/**
 * Blob rows tagged for this submission after a successful save (see index.php setBackref).
 *
 * @return int[]
 */
function peer_cleanup_backref_blob_file_ids($PDOX, $p, $submit_id) {
    $backref = $p . 'peer_submit::submit_id::' . (int) $submit_id;
    $rows = $PDOX->allRowsDie(
        "SELECT file_id FROM {$p}blob_file WHERE backref = :BR",
        array(':BR' => $backref)
    );
    $ids = array();
    foreach ( $rows as $r ) {
        $fid = (int) U::get($r, 'file_id', 0);
        if ( $fid > 0 ) {
            $ids[$fid] = true;
        }
    }
    return array_map('intval', array_keys($ids));
}

/**
 * Delete blob files, peer_submit row, and peer_text for this user/assignment.
 *
 * @param int[] $blob_file_ids
 */
function peer_cleanup_purge_submission($PDOX, $p, $submit_id, $assn_id, $user_id, array $blob_file_ids) {
    foreach ( array_unique($blob_file_ids) as $fid ) {
        if ( $fid < 1 ) {
            continue;
        }
        BlobUtil::deleteBlob($fid, 'admin_bypass');
    }
    $PDOX->queryDie(
        "DELETE FROM {$p}peer_submit WHERE submit_id = :SID",
        array(':SID' => $submit_id)
    );
    $PDOX->queryDie(
        "DELETE FROM {$p}peer_text WHERE assn_id = :AID AND user_id = :UID",
        array(':AID' => $assn_id, ':UID' => $user_id)
    );
    Cache::clear('peer_grade');
    Cache::clear('peer_submit');
}

$existsStmt = $PDOX->prepare(
    "SELECT 1 FROM {$p}blob_file WHERE file_id = :FID LIMIT 1"
);

$submitStmt = $PDOX->queryReturnError(
    "SELECT submit_id, assn_id, user_id, json FROM {$p}peer_submit"
);
if ( $submitStmt === false ) {
    die("Query failed: peer_submit\n");
}

$scanned = 0;
$skipped_no_refs = 0;
$bad_json = 0;
$orphan_candidates = 0;
$deleted = 0;
$removed_bad_json = 0;
$removed_orphans = 0;

while ( $row = $submitStmt->fetch(PDO::FETCH_ASSOC) ) {
    $scanned++;
    $submit_id = (int) $row['submit_id'];
    $assn_id = (int) $row['assn_id'];
    $user_id = (int) $row['user_id'];
    $json_str = $row['json'];

    if ( U::isEmpty(trim((string) $json_str)) ) {
        $skipped_no_refs++;
        if ( $verbose ) {
            echo("SCAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} status=empty_json\n");
        }
        continue;
    }

    $json = json_decode($json_str);
    if ( $json === null && json_last_error() !== JSON_ERROR_NONE ) {
        $bad_json++;
        $jerr = json_last_error_msg();
        if ( $verbose ) {
            echo("SCAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} status=bad_json error={$jerr}\n");
        } else {
            echo("BAD_JSON submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} error={$jerr}\n");
        }
        if ( ! $do_remove ) {
            continue;
        }
        $blob_ids = peer_cleanup_backref_blob_file_ids($PDOX, $p, $submit_id);
        peer_cleanup_purge_submission($PDOX, $p, $submit_id, $assn_id, $user_id, $blob_ids);
        $deleted++;
        $removed_bad_json++;
        continue;
    }

    $file_ids = peer_submit_referenced_file_ids($json);
    if ( count($file_ids) < 1 ) {
        $skipped_no_refs++;
        if ( $verbose ) {
            echo("SCAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} status=no_blob_refs\n");
        }
        continue;
    }

    $missing = array();
    foreach ( $file_ids as $fid ) {
        $existsStmt->execute(array(':FID' => $fid));
        $ok = $existsStmt->fetch(PDO::FETCH_ASSOC);
        $existsStmt->closeCursor();
        if ( $ok === false ) {
            $missing[] = $fid;
        }
    }

    if ( count($missing) < 1 ) {
        if ( $verbose ) {
            $refs = implode(',', $file_ids);
            echo("SCAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} status=ok file_ids={$refs}\n");
        }
        continue;
    }

    $orphan_candidates++;
    $missing_list = implode(',', $missing);
    if ( $verbose ) {
        $refs = implode(',', $file_ids);
        echo("SCAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} status=orphan file_ids={$refs} missing_file_ids={$missing_list}\n");
    } else {
        echo("ORPHAN submit_id={$submit_id} assn_id={$assn_id} user_id={$user_id} missing_file_id(s)={$missing_list}\n");
    }

    if ( ! $do_remove ) {
        continue;
    }

    $blob_ids_to_delete = array();
    foreach ( $file_ids as $fid ) {
        $existsStmt->execute(array(':FID' => $fid));
        $still = $existsStmt->fetch(PDO::FETCH_ASSOC);
        $existsStmt->closeCursor();
        if ( $still !== false ) {
            $blob_ids_to_delete[] = $fid;
        }
    }
    peer_cleanup_purge_submission($PDOX, $p, $submit_id, $assn_id, $user_id, $blob_ids_to_delete);
    $deleted++;
    $removed_orphans++;
}

$would_total = $orphan_candidates + $bad_json;
echo(
    "# peer_submit scanned={$scanned} no_blob_refs={$skipped_no_refs} " .
    "bad_json={$bad_json} orphans={$orphan_candidates} " .
    ( $do_remove
        ? "removed_total={$deleted} (orphans={$removed_orphans} bad_json={$removed_bad_json})"
        : "would_remove_total={$would_total} (orphans={$orphan_candidates} bad_json={$bad_json})"
    ) .
    ' elapsed=' . sprintf('%.2fs', microtime(true) - $t0) . "\n"
);
