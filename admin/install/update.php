<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../admin_util.php");
require_once("install_util.php");

if ( ! U::isCli() ) {
    session_start();
    require_once("gate.php");
    if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

    // https://stackoverflow.com/questions/3133209/how-to-flush-output-after-each-echo-call
    @ini_set('zlib.output_compression',0);
    @ini_set('implicit_flush',1);
    @ob_end_clean();
    set_time_limit(0);
}

LTIX::getConnection();


if ( ! U::isCli() ) {
?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<?php
}
$tools = $PDOX->allRowsDie("SELECT * FROM {$CFG->dbprefix}lms_tools ORDER BY created_at");
foreach($tools as $tool) {
    echo("\n======\n");
    echo("Name: ".htmlentities($tool['name'])."\n");
    echo("Description: ".htmlentities($tool['description'])."\n");
    $path = $tool['toolpath'];
    $path = \Tsugi\Util\U::remove_relative_path($path);
    echo("Path: ".htmlentities($path)."\n");
    $remote = $tool['clone_url'];
    echo("URL: ".htmlentities($remote)."\n");
    echo("Version: ".htmlentities($tool['gitversion'])."\n");
    if ( $tool['deleted'] == 1 ) {
        echo(" Tool is deleted...\n");
        continue;
    }


    // Check to see if we need to clone
    if ( ! file_exists($path ) ) {
        echo("\nNeed to Checkout ".htmlentities($remote)."\n");
        if (! U::conservativeUrl($remote) ) {
            echo('Badly formatted git URL: '.$remote."\n");
            continue;
        }
        $parent = dirname($path);
        if ( strlen($parent) < 1 || ! file_exists($parent) ) {
            echo('Bad parent path: '.$parent."\n");
            continue;
        }

        if ( ! is_writeable($parent) ) {
            echo('Parent path is not writeable: '.$parent."\n");
            continue;
        }

        echo("Parent: $parent\n");
        doClone($remote, $path);
        continue;
    }

    try {
        $repo = new \Tsugi\Util\GitRepo($path);
        $origin = getRepoOrigin($repo);
    } catch (Exception $e) {
        echo("\n**** ERROR\n".$e->getMessage()."\n");
        continue;
    }

    echo("Origin: ".htmlentities($origin)."\n");
    echo("Pull----\n");
    try {
        $pull_output = $repo->run('pull');
    } catch(Exception $e) {
        echo("Error: ".htmlentities($e->getMessage())."\n");
        continue;
    }
    echo(htmlentities($pull_output));
    $command = 'checkout '.$tool['gitversion'];
    echo("Checkout: git ".$command."\n");
    $check_output = $repo->run($command);
    echo(htmlentities($check_output));

    $detail = new \stdClass();
    addRepoInfo($detail, $repo);
    print_r($detail);

    updateToolStatus($path, $detail);

}


if( ! U::isCli() ) {
    echo("\n</body>\n</html>\n");
}
