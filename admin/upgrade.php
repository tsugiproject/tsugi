<?php 

require_once("../config.php");
require_once("../db.php");

$p = $CFG->dbprefix;

echo("Checking plugins table...<br/>\n");
$plugins = "{$p}lms_plugins";
$table_fields = pdoMetadata($db, $plugins);

if ( $table_fields === false ) {
    echo("Creating plugins table...<br/>\n");
    $sql = "
create table {$plugins} (
    plugin_id        MEDIUMINT NOT NULL AUTO_INCREMENT,
    plugin_path      VARCHAR(255) NOT NULL,

    version          BIGINT NOT NULL,

    title            VARCHAR(2048) NULL,

    json             TEXT NULL,
    created_at       DATETIME NOT NULL,
    updated_at       DATETIME NOT NULL,

    UNIQUE(plugin_path),
    PRIMARY KEY (plugin_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;";
    $q = pdoQuery($db, $sql);
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

foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    echo("Checking $path ...<br/>\n");
    unset($DATABASE_INSTALL);
    unset($DATABASE_UNINSTALL);
    unset($DATABASE_UPGRADE);
    require($tool);

    // Check to see if the tables need to be created
    if ( isset($DATABASE_INSTALL) && $DATABASE_INSTALL !== false ) {
        foreach ( $DATABASE_INSTALL as $entry ) {
            echo("-- Checking table ".$entry[0]."<br/>\n");
            $table_fields = pdoMetadata($db, $entry[0]);
            if ( $table_fields === false ) {
                echo("-- Creating table ".$entry[0]."<br/>\n");
                $q = pdoQuery($db, $entry[1]);
                if ( ! $q->success ) die("Unable to create ".$entry[1]." ".$q->errorImplode."<br/>".$entry[1] );
                echo("-- Created table ".$entry[0]."<br/>\n");
                $sql = "INSERT INTO {$plugins} 
                    ( plugin_path, version, created_at, updated_at ) VALUES
                    ( :plugin_path, 1, NOW(), NOW() )
                    ON DUPLICATE KEY 
                    UPDATE version = 1, updated_at = NOW()";
                $values = array( ":plugin_path" => $path);
                $q = pdoQuery($db, $sql, $values);
                if ( ! $q->success ) die("Unable to set version for ".$path." ".$q->errorimplode."<br/>".$entry[1] );
            }
        }
    }

    // Check to see if there is any upgrading needed
    if ( isset($DATABASE_UPGRADE) && $DATABASE_UPGRADE !== false ) {
        $sql = "SELECT version FROM {$plugins} WHERE plugin_path = :plugin_path";
        $values = array( ":plugin_path" => $path);
        $q = pdoQuery($db, $sql, $values);
        $version = 0;
        if ( $q->success ) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            if ( is_array($data) && isset($data['version']) ) $version = $data['version']+0;
        }
        echo("-- Current data model version $version <br/>\n");
        $newversion = $DATABASE_UPGRADE($version);
        if ( $newversion != $version ) {
            echo("-- Upgraded to data model version $newversion <br/>\n");
            $sql = "UPDATE {$plugins} SET version = :version WHERE plugin_path = :plugin_path";
            $values = array( ":version" => $newversion, ":plugin_path" => $path);
            $q = pdoQuery($db, $sql, $values);
            if ( ! $q->success ) die("Unable to update version for ".$path." ".$q->errorimplode."<br/>".$entry[1] );
        }
    }

}


