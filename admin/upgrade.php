<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("admin_util.php");

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

$p = $CFG->dbprefix;
echo("Checking plugins table...<br/>\n");
$plugins = "{$p}lms_plugins";
$table_fields = $PDOX->metadata($plugins);

if ( $table_fields === false ) {
    echo("Creating plugins table...<br/>\n");
    $sql = "
create table {$plugins} (
    plugin_id        INTEGER NOT NULL AUTO_INCREMENT,
    plugin_path      VARCHAR(255) NOT NULL,

    version          BIGINT NOT NULL,

    title            VARCHAR(2048) NULL,

    json             TEXT NULL,
    created_at       DATETIME NOT NULL,
    updated_at       DATETIME NOT NULL,

    UNIQUE(plugin_path),
    PRIMARY KEY (plugin_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;";
    $q = $PDOX->queryReturnError($sql);
    if ( ! $q->success ) die("Unable to create lms_plugins table: ".implode(":", $q->errorInfo) );
    echo("Created plugins table...<br/>\n");
}

echo("Checking for any needed upgrades...<br/>\n");

// Scan the tools folders
$tools = findFiles("database.php","../");
if ( count($tools) < 1 ) {
    echo("No database.php files found...<br/>\n");
    return;
}

// A simple precedence order..   Will have to improve this.
foreach($tools as $k => $tool ) {
    if ( strpos($tool,"admin/lti/database.php") && $k != 0 ) {
        $tmp = $tools[0];
        $tools[0] = $tools[$k];
        $tools[$k] = $tmp;
        break;
    }
}

$maxversion = 0;
$maxpath = '';
foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    echo("Checking $path ...<br/>\n");
    unset($DATABASE_INSTALL);
    unset($DATABASE_POST_CREATE);
    unset($DATABASE_UNINSTALL);
    unset($DATABASE_UPGRADE);
    require($tool);
    require('migrate-run.php');
    flush();
}

echo("\n<br/>Highest database version=$maxversion in $maxpath<br/>\n");

if ( $maxversion > $CFG->dbversion ) {
   echo("-- WARNING: You should set \$CFG->dbversion=$maxversion in setup.php
        before distributing this version of the code.<br/>\n");
} else if ( $maxversion < $CFG->dbversion ) {
     echo("-- Updating overall data model version to $CFG->dbversion per setup.php<br/>\n");
     $sql = "INSERT INTO {$plugins}
        ( plugin_path, version, created_at, updated_at ) VALUES
        ( :plugin_path, :version, NOW(), NOW() )
        ON DUPLICATE KEY
        UPDATE version = :version, updated_at = NOW()";
    $values = array( ":version" => $CFG->dbversion, ":plugin_path" => "overall-version");
    $q = $PDOX->queryReturnError($sql, $values);
    if ( ! $q->success ) die("Unable to update overall version ".$q->errorimplode."<br/>".$entry[1] );
}

if( ! U::isCli() ) {
    echo("\n</body>\n</html>\n");
}
