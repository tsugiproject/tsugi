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

if ( isset($_SESSION['git_results']) ) {
    echo("<p>Results of command:</p>\n");
    echo("<pre>\n");
    echo(htmlentities($_SESSION['git_results']));
    echo("\n</pre>\n");
    unset($_SESSION['git_results']);
    return;
}

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
$remote = false;
$repo = false;
$origin = false;
$log = false;
if ( $command == 'pull' ) {
    if ( ! isset($_REQUEST['path'])) {
        die_with_error_log('pull command requires path parameter');
    }
    if ( ! isset($paths[$_REQUEST['path']]) ) {
        die_with_error_log('Unknown path');
    }

    $path = $paths[$_REQUEST['path']];

    try {
        $repo = new \Tsugi\Util\GitRepo($path);
        $origin = getRepoOrigin($repo);
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage(). "\n";
        die_with_error_log('Unable to execute git. '.$error);
    }
} else if ( $command == 'clone' ) {
    if ( ! isset($CFG->install_folder) ) {
        die_with_error_log('Install folder is not configured');
    }

    if ( ! isset($_REQUEST['remote'])) {
        die_with_error_log('clone command requires remote parameter');
    }
    // TODO: Demand no special characters
    $remote = $_REQUEST['remote'];
} else {
    die_with_error_log('Unknown git command');
}

// Handle the pull POST - do the actual work
if ( isset($_POST['command']) && $command == "pull" ) {
    $path = $paths[$_REQUEST['path']];

    try {
        $repo = new \Tsugi\Util\GitRepo($path);
        $origin = getRepoOrigin($repo);
        $log = $repo->run('pull');
        $results = "Repository: $origin \n";
        $results .= "Command: git pull\n\n";
        $results .= $log;
        $_SESSION['git_results'] = $results;
        header('Location: '.addSession('git.php'));
        return;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage(). "\n";
        die_with_error_log('Unable to execute git. '.$error);
    }
}

// Handle the clone POST - do the actual work
if ( isset($_POST['command']) && $command == "clone" ) {

    try {
        $repo = \Tsugi\Util\GitRepo::create_new('/tmp/x/bob', $remote, false);
        $results = "Command: git clone $remote\n\n";
        $results .= $log;
        $_SESSION['git_results'] = $results;
        header('Location: '.addSession('git.php'));
        return;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage(). "\n";
        die_with_error_log('Unable to execute git. '.$error);
    }
}

// Fall through, set up form and POST
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

if ( $command == 'pull' ) {
?>
<form method="POST">
<p>
Git Command: <?= htmlentities($command) ?>
</p><p>
Git Repository: <?= htmlentities($origin) ?>
</p><p>
<input type="hidden" name="command" value="<?= htmlentities($command) ?>">
<input type="hidden" name="path" value="<?= htmlentities($_REQUEST['path']) ?>">
<input type="submit" value="execute">
</form>
<?php
} else if ( $command == 'clone' ) {
?>
<form method="POST">
<p>
Git Command: <?= htmlentities($command) ?>
</p><p>
Git Repository: <?= htmlentities($_REQUEST['remote']) ?>
</p><p>
<input type="hidden" name="command" value="<?= htmlentities($command) ?>">
<input type="hidden" name="remote" value="<?= htmlentities($_REQUEST['remote']) ?>">
<input type="submit" value="execute">
</form>
<?php
} else {
    die_with_error_log('Unknown git command');
}

$OUTPUT->bodyStart();
