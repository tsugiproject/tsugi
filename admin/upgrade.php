<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

/**
 * Web and shell entry for the database upgrade.
 * The admin controller calls this after the passphrase gate.
 * `php admin/upgrade.php` calls it from the shell.
 */
function tsugi_admin_upgrade_run() {
    global $CFG, $PDOX, $OUTPUT;

    if ( ! U::isCli() ) {
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

// Migrate old plugin_path entries to current lib/Services paths (one-time)
$path_migrations = array(
    'lms/announce/database.php' => 'lib/src/Services/Announcements/database.php',
    'lms/pages/database.php' => 'lib/src/Services/Pages/database.php',
    'tool/tdiscus/database.php' => 'lib/src/Services/Discussions/database.php',
    'lib/src/Controllers/database/Announcements/database.php' => 'lib/src/Services/Announcements/database.php',
    'lib/src/Controllers/database/Pages/database.php' => 'lib/src/Services/Pages/database.php',
    'lib/src/Controllers/database/Discussions/database.php' => 'lib/src/Services/Discussions/database.php',
    'tool/peer-grade/database.php' => 'lib/src/Services/PeerGrade/database.php',
    'admin/mail/database.php' => 'lib/src/Services/Mail/database.php',
    'admin/lti/database.php' => 'lib/src/Services/Lti/database.php',
    'admin/key/database.php' => 'lib/src/Services/Key/database.php',
    'admin/blob/database.php' => 'lib/src/Services/Blob/database.php',
    'admin/install/database.php' => 'lib/src/Services/Admin/database.php',
);
foreach ($path_migrations as $old_path => $new_path) {
    $sql = "SELECT plugin_id FROM {$plugins} WHERE plugin_path = :old_path";
    $q = $PDOX->queryReturnError($sql, array(':old_path' => $old_path));
    if ( ! $q->success || $q->rowCount() < 1 ) continue;

    $sql = "SELECT plugin_id FROM {$plugins} WHERE plugin_path = :new_path";
    $q = $PDOX->queryReturnError($sql, array(':new_path' => $new_path));
    if ( $q->success && $q->rowCount() > 0 ) {
        $sql = "DELETE FROM {$plugins} WHERE plugin_path = :old_path";
        $q = $PDOX->queryReturnError($sql, array(':old_path' => $old_path));
        if ( $q->success && $q->rowCount() > 0 ) {
            echo("Removed duplicate plugin_path: $old_path (already have $new_path)<br/>\n");
        }
        continue;
    }

    $sql = "UPDATE {$plugins} SET plugin_path = :new_path WHERE plugin_path = :old_path";
    $q = $PDOX->queryReturnError($sql, array(':new_path' => $new_path, ':old_path' => $old_path));
    if ($q->success && $q->rowCount() > 0) {
        echo("Migrated plugin_path: $old_path -> $new_path<br/>\n");
    }
}

echo("Checking Services Tables...<br/>\n");
// LTI tables come first. Other service schemas follow in path order.
// Fresh installs create foreign keys to lti_user, lti_context, and lti_link.
$lti_schema = 'lib/src/Services/Lti/database.php';
$svcdb = \Tsugi\Services\Admin\AdminService::searchTwoLevels("database.php", $CFG->dirroot.'/lib/src/Services');
$services = array();
foreach ( $svcdb as $tool ) {
    $services[] = U::remove_relative_path($tool);
}
usort($services, function ($a, $b) use ($CFG) {
    $left = \Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($a, $CFG->dirroot);
    $right = \Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($b, $CFG->dirroot);
    return strcmp($left, $right);
});
$tools = array();
$rest = array();
foreach ( $services as $tool ) {
    $relative = \Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($tool, $CFG->dirroot);
    if ( $relative === $lti_schema ) {
        $tools[] = $tool;
        continue;
    }
    $rest[] = $tool;
}
foreach ( $rest as $tool ) {
    $tools[] = $tool;
}

echo("Checking Installed Modules Tables...<br/>\n");
// Scan the tools folders
$moretools = \Tsugi\Services\Admin\AdminService::findToolFiles("database.php", $CFG->dirroot);
for($i=0; $i<count($moretools); $i++) {
    $moretools[$i] = U::remove_relative_path($moretools[$i]);
}
// Add database.php files, not already in the list
foreach($moretools as $tool) {
    if ( in_array($tool, $tools) ) continue;
    $tools[] = $tool;
}

// Prefer lib Discussions schema over legacy tool/tdiscus database.php stub
$discussions_lib = 'lib/src/Services/Discussions/database.php';
$legacy_discussions = array('tool/tdiscus/database.php');
$has_discussions_lib = false;
foreach ( $tools as $tool ) {
    $relative = U::remove_relative_path(\Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($tool, $CFG->dirroot));
    if ( $relative === $discussions_lib ) {
        $has_discussions_lib = true;
        break;
    }
}
if ( $has_discussions_lib ) {
    $filtered = array();
    foreach ( $tools as $tool ) {
        $relative = U::remove_relative_path(\Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($tool, $CFG->dirroot));
        $is_legacy = false;
        foreach ( $legacy_discussions as $legacy ) {
            if ( $relative === $legacy || substr($relative, -strlen($legacy)) === $legacy ) {
                $is_legacy = true;
                break;
            }
        }
        if ( $is_legacy ) continue;
        $filtered[] = $tool;
    }
    $tools = $filtered;
}

// Prefer lib PeerGrade schema over legacy tool/peer-grade/database.php stub
$peer_grade_lib = 'lib/src/Services/PeerGrade/database.php';
$legacy_peer_grade = array('tool/peer-grade/database.php');
if ( in_array($peer_grade_lib, $tools) ) {
    $filtered = array();
    foreach ( $tools as $tool ) {
        $relative = \Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($tool, $CFG->dirroot);
        if ( in_array($relative, $legacy_peer_grade) ) continue;
        $filtered[] = $tool;
    }
    $tools = $filtered;
}

if ( count($tools) < 1 ) {
    echo("No database.php files found...<br/>\n");
    return;
}


$maxversion = 0;
$maxpath = '';
foreach($tools as $tool ) {
    $path = \Tsugi\Services\Admin\AdminService::trimAsMuchAsYouCan($tool, $CFG->dirroot);
    echo("Checking $path ...<br/>\n");
    unset($DATABASE_INSTALL);
    unset($DATABASE_POST_CREATE);
    unset($DATABASE_UNINSTALL);
    unset($DATABASE_UPGRADE);
    require($tool);
    require __DIR__ . '/migrate-run.php';
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
}

if ( PHP_SAPI === 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__) ) {
    if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
    require_once __DIR__ . '/../config.php';
    tsugi_admin_upgrade_run();
}
