<?php

use \Tsugi\Util\Git;
use \Tsugi\Util\GitRepo;

define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

echo("\n<pre>\n");
$path = $CFG->removeRelativePath($CFG->install_folder);
$folders = findAllFolders($path);

foreach($folders as $folder){
    $git = $folder . '/.git';
    if ( !is_dir($git) ) continue;

    try {
        echo("Repo: $folder\n");
        $repo = new \Tsugi\Util\GitRepo($folder);
        $origin = $repo->run('remote get-url origin');
        echo("Origin: $origin\n");
        $update = $repo->run('remote update');
        echo("Update: $update\n");
        $status = $repo->run('status -uno');
        echo("Status:\n$status\n");
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}
