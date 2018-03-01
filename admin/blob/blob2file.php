<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

require_once("../../config.php");

if ( ! U::isCli() ) die('Must be command line');

if ( !isset($CFG->dataroot) || strlen($CFG->dataroot) < 1 ) die('Must set $CFG->dataroot');

LTIX::getConnection();

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'move');

if ( $dryrun ) {
    echo("This is a dry run, use 'php blob2file.php move' to actually move the blobs.\n");
} else {
    echo("This IS NOT A DRILL!\n");
    sleep(5);
    echo("...\n");
    sleep(5);
}

$stmt = $PDOX->query("SELECT BF.file_id, BF.file_sha256, BF.blob_id
    FROM {$CFG->dbprefix}blob_file AS BF
    WHERE BF.path IS NULL");

$stmt->execute();

$checked = 0;
$file_moved = 0;
$blob_moved = 0;
$bytes = 0;

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $checked++;
    $file_id = $row['file_id'];
    $blob_id = $row['blob_id'];
    $file_sha256 = $row['file_sha256'];
    $blob_folder = BlobUtil::mkdirSha256($file_sha256);
    if ( $blob_folder ) {
        $blob_name =  $blob_folder . '/' . $file_sha256;
    }
    if ( ! $blob_name ) {
        echo("Error: Could not make file name fi=$file_id bi=$blob_id\n");
        continue;
    } 

    echo("fi=$file_id bi=$blob_id file=$blob_name\n");

    $lob = false;
    if ( ! $blob_id ) {
        $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID");
        $lstmt->execute(array(":ID" => $file_id));
        $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
        $lstmt->fetch(\PDO::FETCH_BOUND);
        echo("file_blob=".strlen($lob)."\n");
        $file_moved++;
    } else {
        $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_blob WHERE blob_id = :ID");
        $lstmt->execute(array(":ID" => $blob_id));
        $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
        $lstmt->fetch(\PDO::FETCH_BOUND);
        echo("blob_blob=".strlen($lob)."\n");
        $blob_moved++;
    }

    if ( ! is_string($lob) ) {
        echo("Lob is not a string. fi=$file_id bi=$blob_id\n");
        exit();
    }

    $bytes += strlen($lob);

    if ( $dryrun ) {
        echo("Would write ".strlen($lob)." from $file_id to $blob_name\n");
        continue;
    }

    $retval = file_put_contents($blob_name, $lob);
    if ( $retval != strlen($lob) ) {
        echo("Failed retval=$retval len=".strlen($lob)." to $blob_name\n");
        continue;
    }
    echo("retval=$retval wrote ".strlen($lob)." to $blob_name\n");
    $lstmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file 
        SET path=:PATH, blob_id=NULL, content=NULL WHERE file_id = :ID");
    $lstmt->execute(array(
        ":ID" => $file_id,
        ":PATH" => $blob_name
    ));
}

echo("# blobs=$checked file_moved=$file_moved blob_moved=$blob_moved bytes=$bytes\n");
