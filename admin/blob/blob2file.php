<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

require_once("../../config.php");

if ( ! U::isCli() ) die('Must be command line');

if ( trim(shell_exec('whoami')) == 'root' ) {
    echo("Should not be run as root\n\n");
    echo("sudo -H -u www-data _command_   (or similar)\n");
    die();
}

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

$stop = 5;
$checked = 0;
$file_moved = 0;
$blob_moved = 0;
$bytes = 0;

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $checked++;
    if ( $stop > 0 && $checked > $stop ) {
        echo("\nPartial Run: Stopped after $stop blobs\n");
        break;
    }
    $file_id = $row['file_id'];
    $blob_id = $row['blob_id'];
    $file_sha256 = $row['file_sha256'];
    $blob_folder = BlobUtil::mkdirSha256($file_sha256);
    if ( ! $blob_folder ) {
        echo("Could not make blob folder, perhaps running as wrong user?\n");
    	echo("sudo -H -u www-data _command_   (or similar)\n");
        exit();
    }
    $blob_name =  $blob_folder . '/' . $file_sha256;

    $lob = false;
    if ( ! $blob_id ) {
        try {
            $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID");
            $lstmt->execute(array(":ID" => $file_id));
            $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
            $lstmt->fetch(\PDO::FETCH_BOUND);
            $file_moved++;
        } catch (\Exception $e) {
            // This is not a bad thing post 2018-02, the column is no longer there
            echo("Error: No column for legacy blob file_id=$id\n");
            exit();
        }
    } else {
        $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_blob WHERE blob_id = :ID");
        $lstmt->execute(array(":ID" => $blob_id));
        $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
        $lstmt->fetch(\PDO::FETCH_BOUND);
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
    echo("blob2file.php migrated file_id=$file_id wrote ".strlen($lob)." to $blob_name\n");
    $lstmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file 
        SET path=:PATH, blob_id=NULL, content=NULL WHERE file_id = :ID");
    $lstmt->execute(array(
        ":ID" => $file_id,
        ":PATH" => $blob_name
    ));
}

echo("# blobs=$checked file_moved=$file_moved blob_moved=$blob_moved bytes=$bytes\n");
