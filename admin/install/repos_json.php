<?php

use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\Cache;

define('COOKIE_SESSION', true);
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

echo("\n<pre>\n");

$path = $CFG->removeRelativePath($CFG->install_folder);
$folders = findAllFolders($path);

$existing = array();
foreach($folders as $folder){
    $git = $folder . '/.git';
    if ( !is_dir($git) ) continue;

    try {
        $repo = new \Tsugi\Util\GitRepo($folder);
        $origin = $repo->run('remote get-url origin');
        $existing[trim($origin)] = $repo;
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
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

$available = array();
$installed = array();

foreach($repos as $repo) {
    $detail = new \stdClass();
    $detail->html_url = $repo->html_url;
    $detail->clone_url = $repo->clone_url;
    $detail->name = ucfirst($repo->name);
    $detail->description = $repo->description;
    $detail->tsugitools = true;
    if ( isset($existing[$detail->clone_url]) ) {
        $detail->existing = true;
        $repo = $existing[$detail->clone_url]; 
        $update = $repo->run('remote update');
        $detail->update_note = $update;
        $status = $repo->run('status -uno');
        $detail->status_note = $status;
        $detail->updates = strpos($status, 'Your branch is behind') !== false;
        unset($existing[$detail->clone_url]);
        $installed[] = $detail;
    } else {
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
    $installed[] = $detail;
}


// git reset --hard 5979437e27bd47637c4b562b33e861ce32b6468b

$retval['status'] = 'OK';
$retval['detail'] = $note;
$retval['available'] = $available;
$retval['installed'] = $installed;

echo(json_encode($retval, JSON_PRETTY_PRINT));
