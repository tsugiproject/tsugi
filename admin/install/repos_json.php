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

echo("\n<pre>\n");

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

$repo_list = array();

foreach($repos as $repo) {
    $detail = new \stdClass();
    $detail->html_url = $repo->html_url;
    $detail->clone_url = $repo->clone_url;
    $detail->name = ucfirst($repo->name);
    $detail->description = $repo->description;
    $repo_list[] = $detail;
}

$retval['status'] = 'OK';
$retval['detail'] = $note;
$retval['repos'] = $repo_list;

echo(json_encode($retval, JSON_PRETTY_PRINT));
