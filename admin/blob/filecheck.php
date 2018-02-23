<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) die('Must be command line');

LTIX::getConnection();

if ( ! $CFG->dataroot ) die ('$CFG->dataroot not configured');
$directory = $CFG->dataroot;
if ( ! file_exists($directory) ) die ('$CFG->dataroot does not exist');

$dryrun = ! ( isset($argv[1]) && $argv[1] == 'remove');
$verbose = isset($argv[1]) && $argv[1] == 'verbose';

if ( $dryrun ) {
    echo("This is a dry run, use 'php filecheck.php remove' to actually remove the files.\n");
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

function scanFolder($directory, $top) {
    global $CFG, $PDOX, $verbose, $dryrun;
    global $fscan, $dscan, $ddel, $fdel, $dskip, $fskip, $fgood;

    $fcount = 0;
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) {
            $fcount++;
            $dscan++;
            if ( preg_match('/^[0-9][0-9]/', basename($file)) ){
                if ( $verbose) echo("Recurse folder $file\n");
                $fcount += scanFolder($file, false);
            } else if ( preg_match('/^[0-9a-f][0-9a-f]/', basename($file)) ){
                if ( $verbose) echo("Recurse folder $file\n");
                $fcount += scanFolder($file, false);
            } else {
                $dskip++;
                echo("Skipping folder $file\n");
            }
        } else {
            $fcount++;
            $fscan++;
            if ( ! preg_match('/^[0-9a-f]+$/', basename($file)) ){
                echo("Skipping file $file\n");
                $fskip++;
                continue;
            }
            $sha_256 =  basename($file);
            $stmt = $PDOX->prepare("SELECT file_sha256 FROM {$CFG->dbprefix}blob_file
                    WHERE file_sha256 = :SHA LIMIT 1");
            $stmt->execute(array(":SHA" => $sha_256));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ( $row ) {
                if ( $verbose ) echo("OK $file\n");
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
    echo("This is a dry run, use 'php filecheck.php remove' to actually remove the files.\n");
}
echo("# folders scan=$dscan skip=$dskip rm=$ddel\n");
echo("# files scan=$fscan skip=$fskip good=$fgood rm=$fdel\n");
