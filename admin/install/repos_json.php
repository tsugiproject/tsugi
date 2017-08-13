<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Git;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Cache;
use \Tsugi\UI\Lessons;

if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
if ( ! isset($_SESSION["admin"]) ) {
    Net::send403();
    die_with_error_log('Must be admin to list repositories');
}

$retval = array();

if ( ! isset($CFG->install_folder) ) {
    Net::send403();
    die_with_error_log('Install folder is not configured');
}

require_once("../admin_util.php");
require_once("install_util.php");

// Load the Lesson
$l = false;
if ( isset($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
}

$available = array();
$required = array();
$installed = array();
$paths = array();

// In case we need a setuid or other copy of git
if ( isset($CFG->git_command) ) {
    Git::set_bin($CFG->git_command);
}

// Figure out the git version
$repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
$git_version = $repo->run('--version');

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

$path = U::remove_relative_path($CFG->install_folder);
$folders = findAllFolders($path);

// Load the existing modules
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

// Check to see if there are any required repos we don't already have
if ( $l && isset($l->lessons->required_modules) ) foreach($l->lessons->required_modules as $needed) {
    if ( isset($existing[$needed]) || isset($existing[$needed.'.git'])) continue;
    $detail = new \stdClass();
    $detail->html_url = $needed;
    $detail->clone_url = $needed;
    $detail->name = $needed;
    $detail->description = '';
    $detail->index = count($required) + 1;
    $detail->writeable = $install_writeable; // Assume if we cannot update tsugi..
    $required[] = $detail;
}

// Check the tsugitools project in github
// Only retrieve fresh every 600 seconds unless forced
$fail = false;
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
        $retval['available_error'] = $error;
        $retval['available_error_detail'] = '';
        $fail = true;
    }
    // echo(LTI::jsonIndent($repos_str));
    $repos = json_decode($repos_str);
    if ( $repos === null ) {
        $error = 'Unable to decode '.$url;
        $retval['available_error'] = $error;
        $retval['available_error_detail'] = $repos_str;
        $fail = true;
    }

    if ( is_object($repos) ) {
        $error = 'Did not get list of repositories: '.$url;
        $retval['available_error'] = $error;
        $retval['available_error_detail'] = json_encode($repos,JSON_PRETTY_PRINT);
        $fail = true;
    }

    if ( ! $fail ) Cache::set('repos',1,$repos,$expiresec);
}

// Loop through the tsugitools repos and get detail
if ( ! $fail ) foreach($repos as $repo) {
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
    $detail->name = "";
    preg_match( '/([^\/]+)\.git/', $clone_url, $match );

    if(count($match) == 2) {
        $detail->name = ucwords(preg_replace("/[^0-9a-zA-Z]/", " ", $match[1]));
    }
    $detail->description = "";
    $update = $repo->run('remote update');
    $detail->update_note = $update;
    $status = $repo->run('status -uno');
    $detail->status_note = $status;
    $detail->updates = strpos($status, 'Your branch is behind') !== false;
    $detail->tsugitools = false;
    $detail->writeable = $install_writeable; // Assume if we cannot update tsugi..
    $detail->index = count($installed) + 1;
    if ( isset($paths[$clone_url]) ) {
        $detail->path = $paths[$clone_url];
        $detail->guid = md5($paths[$clone_url]);
    }
    $installed[] = $detail;
}


$retval['status'] = 'OK';
$retval['version'] = trim($git_version);
$retval['detail'] = $note;
$retval['available'] = $available;
$retval['installed'] = $installed;
$retval['required'] = $required;

echo(json_encode($retval, JSON_PRETTY_PRINT));
