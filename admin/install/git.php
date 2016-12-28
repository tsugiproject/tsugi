<?php

use \Tsugi\Util\Git;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Cache;

define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
if ( ! isset($_SESSION["admin"]) ) {
    die_with_error_log('Must be admin to run git commands');
}

$retval = array();

require_once("../admin_util.php");
require_once("install_util.php");

$available = array();
$installed = array();
$paths = array();

// In case we need a setuid copy of git
if ( isset($CFG->git_command) ) {
    Git::set_bin($CFG->git_command);
}

// Figure out the git version
try {
    $repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
    $git_version = $repo->run('--version');
} catch (Exception $e) {
    $error = 'Caught exception: '.$e->getMessage(). "\n";
    die_with_error_log('Unable to execute git. '.$error);
}

if ( !isset($_REQUEST['command']) ) {
    die_with_error_log('command option required');
}

// Get all the paths including tsugi
$tsugihash = md5($CFG->dirroot);
$paths = array();
$paths[$tsugihash] = $CFG->dirroot;
if ( isset($CFG->install_folder) ) {
    $path = $CFG->removeRelativePath($CFG->install_folder);
    $folders = findAllFolders($path);
    foreach($folders as $folder){
        $git = $folder . '/.git';
        if ( !is_dir($git) ) continue;
        $paths[md5($folder)] = $folder;
    }
}

// Handle the log request
$command = $_REQUEST['command'];
if ( $command == 'log' ) {
    if ( ! isset($_REQUEST['path'])) {
        die_with_error_log('log command requires path parameter');
    }
    if ( ! isset($paths[$_REQUEST['path']]) ) {
        die_with_error_log('Unknown path');
    }
    $path = $paths[$_REQUEST['path']];

    try {
        $repo = new \Tsugi\Util\GitRepo($path);
        $origin = getRepoOrigin($repo);
        $log = $repo->run('log -20');
        echo("<p>First 20 log entries below. For more information visit the repository at\n");
        echo('<a href="'.htmlentities($origin).'" target="_blank">'.htmlentities($origin)."</a>\n");
        echo("</p>\n");
        echo("<pre>\n");
        echo(htmlentities($log));
        echo("\n</pre>\n");
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage(). "\n";
        die_with_error_log('Unable to execute git. '.$error);
    }
    return;
}

// Do sanity checking on all requests
if ( $command == 'pull' ) {
    if ( ! isset($_REQUEST['path'])) {
        die_with_error_log('pull command requires path parameter');
    }
    if ( ! isset($paths[$_REQUEST['path']]) ) {
        die_with_error_log('Unknown path');
    }
} else if ( $command == 'clone' ) {
    if ( ! isset($CFG->install_folder) ) {
        die_with_error_log('Install folder is not configured');
    }

    if ( ! isset($_REQUEST['repo'])) {
        die_with_error_log('clone command requires repo parameter');
    }
} else {
    die_with_error_log('Unknown command');
}

/*
// Gather the information for the tsugi folder
$origin = getRepoOrigin($repo);
$tsugi = new \stdClass();
$tsugi->clone_url = $origin; // Yes, it works..
$tsugi->html_url = $origin; // Yes, it works..
$tsugi->name = "Tsugi Admin";
$tsugi->description = "Tsugi Adminstration, Management, and Development Console.";
try {
    $update = $repo->run('remote update');
    $tsugi->writeable = true;
    $install_writeable = true;
} catch (Exception $e) {
    $tsugi->writeable = false;
    $install_writeable = false;
    $update = 'Caught exception: '.$e->getMessage(). "\n";
}
$tsugi->update_note = $update;
$status = $repo->run('status -uno');
$tsugi->status_note = $status;
$tsugi->updates = strpos($status, 'Your branch is behind') !== false;
$tsugi->tsugitools = false;
$tsugi->index = count($installed) + 1;
$tsugi->path = $CFG->dirroot;
$tsugi->guid = md5($CFG->dirroot);
$installed[] = $tsugi;
$paths[$origin] = $CFG->dirroot;

$path = $CFG->removeRelativePath($CFG->install_folder);
$folders = findAllFolders($path);

$existing = array();
foreach($folders as $folder){
    $git = $folder . '/.git';
    if ( !is_dir($git) ) continue;

    try {
        $repo = new \Tsugi\Util\GitRepo($folder);
        // $origin = $repo->run('remote get-url origin'); // In git 2.0
        $origin = getRepoOrigin($repo);
        $existing[$origin] = $repo;
        $paths[$origin] = $folder;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage(). "\n";
        $retval['error'] = $error;
        echo(json_encode($retval));
        die_with_error_log($error);
    }
}

// Only retrieve fresh every 600 seconds unless forced
$repos = Cache::check('repos',1);
if ( (! isset($_GET['force']) ) && $repos !== false ) {
    $expires = Cache::expires('repos',1);
    $note = 'Retrieved from session. Cached for '.$expires.' more seconds to avoid rate limit. Add ?force=yes to force pull from github before cache expires.';
} else {
    $url = 'https://api.github.com/users/tsugitools/repos?language=PHP';
    $headers = 'User-Agent: TsugiProject';
    $expiresec = 600;
    $repos_str = Net::doGet($url, $headers);
    $note = 'Retrieved from github API. Data is cached for '.$expiresec.' seconds to avoit github limit. Add ?force=yes to force pull from github before cache expires.';
    if ( strlen($repos_str) < 1 ) {
        $error = 'No data retrieved from '.$url;
        $retval['error'] = $error;
        echo(json_encode($retval));
        die_with_error_log($error);
    }
    // echo(LTI::jsonIndent($repos_str));
    $repos = json_decode($repos_str);
    if ( $repos === null ) {
        $error = 'Unable to decode '.$url;
        $retval['error'] = $error;
        $retval['error_detail'] = $repos_str;
        echo(json_encode($retval));
        error_log(LTI::jsonIndent($repos_str));
        die_with_error_log($error);
    }

    if ( is_object($repos) ) {
        $error = 'Did not get list of repositories'.$url;
        $retval['error'] = $error;
        $retval['error_detail'] = $repos_str;
        echo(json_encode($retval));
        error_log(LTI::jsonIndent($repos_str));
        die_with_error_log($error);
    }
    
    Cache::set('repos',1,$repos,$expiresec);
}

foreach($repos as $repo) {
    $detail = new \stdClass();
    $detail->html_url = $repo->html_url;
    $detail->clone_url = $repo->clone_url;
    $detail->name = ucfirst($repo->name);
    $detail->description = $repo->description;
    $detail->tsugitools = true;
    if ( isset($existing[$detail->clone_url]) ) {
        $detail->existing = true;
        $detail->path = $paths[$detail->clone_url];
        $detail->guid = md5($paths[$detail->clone_url]);
        $repo = $existing[$detail->clone_url]; 
        try {
            $update = $repo->run('remote update');
            $detail->writeable = true;
        } catch (Exception $e) {
            $detail->writeable = false;
            $update = 'Caught exception: '.$e->getMessage(). "\n";
        }   
        $detail->update_note = $update;
        $status = $repo->run('status -uno');
        $detail->status_note = $status;
        $detail->updates = strpos($status, 'Your branch is behind') !== false;
        unset($existing[$detail->clone_url]);
        $detail->index = count($installed) + 1;
        $installed[] = $detail;
    } else {
        $detail->writeable = $install_writeable; // Assume if we cannot update tsugi..
        $detail->index = count($available) + 1;
        $available[] = $detail;
    }
}

// The ones we have that are not from tsugitools
foreach($existing as $clone_url => $repo) {
    $detail = new \stdClass();
    $detail->clone_url = $clone_url; // Yes, it works..
    $detail->html_url = $clone_url; // Yes, it works..
    $detail->name = "TBD";
    $detail->description = "";
    $update = $repo->run('remote update');
    $detail->update_note = $update;
    $status = $repo->run('status -uno');
    $detail->status_note = $status;
    $detail->updates = strpos($status, 'Your branch is behind') !== false;
    $detail->tsugitools = false;
    $detail->writeable = $install_writeable; // Assume if we cannot update tsugi..
    $detail->index = count($installed) + 1;
    $installed[] = $detail;
}


$retval['status'] = 'OK';
$retval['version'] = trim($git_version);
$retval['detail'] = $note;
$retval['available'] = $available;
$retval['installed'] = $installed;

echo(json_encode($retval, JSON_PRETTY_PRINT));
*/
