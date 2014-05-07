<?php 
define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

require_once("../pdo.php");
require_once("../lib/lms_lib.php");

?>
<html>
<head>
<?php echo(html_toggle_preScript()); ?>
</head>
<body>
<?php

$p = $CFG->dbprefix;
echo("Checking plugins table...<br/>\n");
$plugins = "{$p}lms_plugins";
$table_fields = pdo_metadata($pdo, $plugins);

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
    $q = pdo_query($pdo, $sql);
    if ( ! $q->success ) die("Unable to create lms_plugins table: ".implode(":", $q->errorInfo) );
    echo("Created plugins table...<br/>\n");
} 

echo("Checking for any needed upgrades...<br/>\n");

// Scan the tools folders
$tools = find_files("database.php","../");
if ( count($tools) < 1 ) {
    echo("No database.php files found...<br/>\n");
    return;
}

// A simple precedence order..   Will have to improve this.
foreach($tools as $k => $tool ) {
    if ( strpos($tool,"core/lti/database.php") && $k != 0 ) {
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

    // Check to see if the tables need to be created
    if ( isset($DATABASE_INSTALL) && $DATABASE_INSTALL !== false ) {
        foreach ( $DATABASE_INSTALL as $entry ) {
            echo("-- Checking table ".$entry[0]."<br/>\n");
            $table_fields = pdo_metadata($pdo, $entry[0]);
            if ( $table_fields === false ) {
                echo("-- Creating table ".$entry[0]."<br/>\n");
                error_log("-- Creating table ".$entry[0]);
                $q = pdo_query($pdo, $entry[1]);
                if ( ! $q->success ) die("Unable to create ".$entry[1]." ".$q->errorImplode."<br/>".$entry[1] );
                html_toggle_pre("-- Created table ".$entry[0], $entry[1]);
                $sql = "INSERT INTO {$plugins} 
                    ( plugin_path, version, created_at, updated_at ) VALUES
                    ( :plugin_path, :version, NOW(), NOW() )
                    ON DUPLICATE KEY 
                    UPDATE version = :version, updated_at = NOW()";
                $values = array( ":plugin_path" => $path, 
                        ":version" => $CFG->dbversion);
                $q = pdo_query($pdo, $sql, $values);
                if ( ! $q->success ) die("Unable to set version for ".$path." ".$q->errorimplode."<br/>".$entry[1] );
                // Do the POST-Create
                if ( isset($DATABASE_POST_CREATE) && $DATABASE_POST_CREATE !== false ) {
                    $DATABASE_POST_CREATE($pdo, $entry[0]);
                }
            }
        }
    }

    // Check to see if there is any upgrading needed
    if ( isset($DATABASE_UPGRADE) && $DATABASE_UPGRADE !== false ) {
        $sql = "SELECT version FROM {$plugins} WHERE plugin_path = :plugin_path";
        $values = array( ":plugin_path" => $path);
        $q = pdo_query($pdo, $sql, $values);
        $version = $CFG->dbversion;
        if ( $q->success ) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            if ( is_array($data) && isset($data['version']) ) $version = $data['version']+0;
        }
        echo("-- Current data model version $version <br/>\n");
        $newversion = $DATABASE_UPGRADE($pdo, $version);
        if ( $newversion > $maxversion ) {
            $maxversion = $newversion;
            $maxpath = $path;
        }
        if ( $newversion > $CFG->dbversion ) {
            echo("-- WARNING: Database version=$newversion for $path higher than 
                \$CFG->dbversion=$CFG->dbversion in setup.php<br/>\n");
        }
        if ( $newversion > $version ) {
            echo("-- Upgraded to data model version $newversion <br/>\n");
            $sql = "INSERT INTO {$plugins} 
                ( plugin_path, version, created_at, updated_at ) VALUES
                ( :plugin_path, :version, NOW(), NOW() )
                ON DUPLICATE KEY 
                UPDATE version = :version, updated_at = NOW()";
            $values = array( ":version" => $newversion, ":plugin_path" => $path);
            $q = pdo_query($pdo, $sql, $values);
            if ( ! $q->success ) die("Unable to update version for ".$path." ".$q->errorimplode."<br/>".$entry[1] );
        }
    }
}

echo("\n<br/>Highest database version=$maxversion in $maxpath<br/>\n");

if ( $maxversion != $CFG->dbversion ) {
   echo("-- WARNING: You should set \$CFG->dbversion=$maxversion in setup.php 
        before distributing this version of the code.<br/>\n");
}

?>
</body>
</html>

