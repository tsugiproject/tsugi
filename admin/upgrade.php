<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

/**
 * Web and shell entry for the database upgrade.
 * The admin controller calls this after the passphrase gate.
 * `php admin/upgrade.php` calls it from the shell.
 */
function tsugi_admin_upgrade_mysql_banner($version) {
    $version = htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8');
    $bar = str_repeat('*', 64);
    echo("\n".$bar."<br/>\n");
    echo("*** ERROR: MySQL version ".$version." is older than ".TSUGI_MINIMUM_MYSQL.".<br/>\n");
    echo("*** Tsugi needs MySQL ".TSUGI_MINIMUM_MYSQL." or newer.<br/>\n");
    echo("*** Continuing the database upgrade.<br/>\n");
    echo($bar."<br/>\n\n");
}

function tsugi_admin_upgrade_run($forceBeacon = false) {
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

$db_version = '';
$db_version_stmt = $PDOX->queryReturnError("SELECT VERSION() AS version", false, false);
if ( $db_version_stmt->success ) {
    $db_version_row = $db_version_stmt->fetch(\PDO::FETCH_ASSOC);
    $db_version_stmt->closeCursor();
    $db_version = is_array($db_version_row) ? (string) ($db_version_row['version'] ?? '') : '';
    if ( tsugi_mysql_version_too_low($db_version) ) {
        tsugi_admin_upgrade_mysql_banner($db_version);
        flush();
    }
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
    // Drop the previous schema file's variables. isset(null) is false,
    // so a file that omits one of these does not inherit the last file.
    $DATABASE_INSTALL = null;
    $DATABASE_POST_CREATE = null;
    $DATABASE_UNINSTALL = null;
    $DATABASE_UPGRADE = null;
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

tsugi_admin_upgrade_report($maxversion, $maxpath, $db_version, $forceBeacon);

if( ! U::isCli() ) {
    echo("\n</body>\n</html>\n");
}
}

/**
 * Where the upgrade beacon is posted. Unauthenticated; the body is the record.
 *
 * $CFG->upgrade_report_url replaces the host. A site root is enough;
 * /api/beacon/upgrade is added when the value does not already end with it.
 * A missing setting, or a string that is not http(s), keeps the default URL.
 * False and any other non-string do not change this URL. The automatic post
 * refuses to run in that case. A hand send still uses the URL returned here.
 */
function tsugi_admin_upgrade_report_url() {
    global $CFG;

    $url = 'https://www.tsugi.org/api/beacon/upgrade';
    if ( isset($CFG->upgrade_report_url) && is_string($CFG->upgrade_report_url) ) {
        $custom = trim($CFG->upgrade_report_url);
        if ( preg_match('#^https?://#i', $custom) ) {
            $url = rtrim($custom, '/');
            if ( ! str_ends_with($url, '/api/beacon/upgrade') ) {
                $url .= '/api/beacon/upgrade';
            }
        }
    }
    return $url;
}

/**
 * Why the automatic post at the end of an upgrade will not run.
 * An empty string means the upgrade will post.
 */
function tsugi_admin_upgrade_report_skip_reason() {
    global $CFG;

    if ( isset($CFG->beacon) && $CFG->beacon === false ) {
        return '$CFG->beacon is false';
    }
    if ( isset($CFG->upgrade_report_url) && ! is_string($CFG->upgrade_report_url) ) {
        return '$CFG->upgrade_report_url is not a URL';
    }
    return '';
}

/**
 * Gather install, checkout, and database-version facts and POST them.
 *
 * Runs after migrations. Never throws, and a network failure does not
 * change the upgrade result. Skipped when $CFG->beacon is false,
 * or when $CFG->upgrade_report_url is set to false or any other non-string.
 * Null leaves the default URL in place. $force sends anyway.
 */
function tsugi_admin_upgrade_report($maxversion, $maxpath, $db_server_version, $force = false) {
    try {
        $skip = tsugi_admin_upgrade_report_skip_reason();
        if ( $skip !== '' && ! $force ) {
            echo("Beacon disabled - to manually send a beacon type php upgrade.php beacon<br/>\n");
            return;
        }
        if ( $skip !== '' ) {
            echo("Automatic upgrade reports are off (".htmlspecialchars($skip)."). Sending this beacon anyway.<br/>\n");
        }

        $sent = tsugi_admin_upgrade_beacon_send(null, $db_server_version);
        tsugi_admin_upgrade_beacon_print($sent, true);
    } catch (\Throwable $e) {
        echo("Upgrade beacon failed: ".htmlspecialchars($e->getMessage())
            .". The database upgrade itself finished.<br/>\n");
        error_log('Tsugi upgrade beacon failed: '.$e->getMessage());
    }
}

/**
 * POST one beacon. Does not look at the off switch and does not migrate.
 *
 * $comment null keeps the client id counts. A string is sent as the comment.
 * $db_server_version null reads SELECT VERSION() for this call.
 *
 * @return array{ok:bool,detail:string,payload:array,url:string}
 */
function tsugi_admin_upgrade_beacon_send($comment = null, $db_server_version = null) {
    if ( $db_server_version === null ) {
        $db_server_version = tsugi_admin_upgrade_mysql_version();
    }
    $payload = tsugi_admin_upgrade_report_payload(0, '', $db_server_version, $comment);
    $result = tsugi_admin_upgrade_report_post($payload);
    $result['payload'] = $payload;
    $result['url'] = tsugi_admin_upgrade_report_url();
    return $result;
}

/**
 * @param array{ok:bool,detail:string,payload:array,url:string} $sent
 * @param bool $upgradeFinished When true, a failed post says the upgrade itself finished.
 */
function tsugi_admin_upgrade_beacon_print($sent, $upgradeFinished) {
    $payload = $sent['payload'];
    $commit = $payload['git_commit'] ?? '';
    if ( is_string($commit) && strlen($commit) >= 12 ) {
        $commit = substr($commit, 0, 12);
    }
    if ( ! is_string($commit) || $commit === '' ) $commit = 'none';
    $line = 'host='.(string) ($payload['host'] ?? '')
        .' dbversion='.(string) ($payload['database_version'] ?? '')
        .' commit='.$commit
        .' url='.(string) ($sent['url'] ?? '');
    $br = U::isCli() ? "\n" : "<br/>\n";
    $show = function ($text) {
        return U::isCli() ? $text : htmlspecialchars($text);
    };
    if ( $sent['ok'] ) {
        echo 'Posted upgrade beacon '.$show($line).' ('.$show($sent['detail']).').'.$br;
        return;
    }
    error_log('Tsugi upgrade beacon failed: '.$sent['detail']);
    if ( $upgradeFinished ) {
        echo 'Upgrade beacon was not posted: '.$show($sent['detail'])
            .'. The database upgrade itself finished. '.$show($line).'.'.$br;
        return;
    }
    echo 'Upgrade beacon was not posted: '.$show($sent['detail']).'. '.$show($line).'.'.$br;
}

function tsugi_admin_upgrade_mysql_version() {
    try {
        $PDOX = \Tsugi\Core\LTIX::getConnection();
        if ( ! is_object($PDOX) ) return '';
        $stmt = $PDOX->queryReturnError('SELECT VERSION() AS version', false, false);
        if ( ! is_object($stmt) || empty($stmt->success) ) return '';
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $version = is_array($row) ? ($row['version'] ?? '') : '';
        return is_string($version) ? $version : '';
    } catch (\Throwable $e) {
        return '';
    }
}

/**
 * Email of the person at the keyboard. Session first, then $USER.
 */
function tsugi_admin_upgrade_session_email() {
    global $USER;

    $email = '';
    if ( session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['email']) && is_string($_SESSION['email']) ) {
        $email = trim($_SESSION['email']);
    }
    if ( $email === '' && isset($USER) && is_object($USER) && isset($USER->email) && is_string($USER->email) ) {
        $email = trim($USER->email);
    }
    if ( $email === '' ) return '';
    $email = str_replace(array("\r", "\n", "\0"), '', $email);
    if ( function_exists('mb_substr') ) return mb_substr($email, 0, 200);
    return substr($email, 0, 200);
}

/**
 * Comment for a hand send. A blank entry becomes "Sent by" plus the logged-in email.
 */
function tsugi_admin_upgrade_manual_comment($entered) {
    $text = is_string($entered) ? trim($entered) : '';
    $text = str_replace("\0", '', $text);
    if ( $text !== '' ) {
        if ( function_exists('mb_substr') ) return mb_substr($text, 0, 4000);
        return substr($text, 0, 4000);
    }
    $email = tsugi_admin_upgrade_session_email();
    if ( $email !== '' ) return 'Sent by '.$email;
    return 'Sent from the admin console';
}

/**
 * Facts worth keeping about who ran an upgrade.
 *
 * comment is the client id check as counts, with no row detail, unless
 * $comment is a string. A hand send passes that string in.
 * $maxversion and $maxpath are unused. The caller still has them from the migration loop.
 */
function tsugi_admin_upgrade_report_payload($maxversion, $maxpath, $db_server_version, $comment = null) {
    global $CFG;

    $dirroot = is_string($CFG->dirroot ?? null) ? $CFG->dirroot : '';
    $real = $dirroot !== '' ? realpath($dirroot) : false;
    if ( is_string($real) && $real !== '' ) $dirroot = $real;

    $host = tsugi_admin_upgrade_host($CFG->wwwroot ?? null, $CFG->apphome ?? null);
    $owneremail = tsugi_admin_upgrade_text($CFG->owneremail ?? null, 200);
    $servicename = tsugi_admin_upgrade_text($CFG->servicename ?? null, 200);
    $ownername = tsugi_admin_upgrade_text($CFG->ownername ?? null, 200);
    $mysql = trim((string) $db_server_version);
    if ( $mysql === '' ) $mysql = null;
    $timezone = tsugi_admin_upgrade_text($CFG->timezone ?? null, 64);
    if ( $timezone === null ) {
        $timezone = tsugi_admin_upgrade_text(date_default_timezone_get(), 64);
    }
    $git = tsugi_admin_upgrade_git($dirroot);

    $payload = array(
        'report_version' => 1,
        'marker' => 'tsugi-upgrade-beacon',
        'reported_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'comment' => is_string($comment) ? $comment : tsugi_admin_upgrade_client_id_comment(),
        'host' => $host,
        'install_id' => tsugi_admin_upgrade_install_id($host === null ? '' : $host, $owneremail),
        'tsugi_version' => defined('TSUGI_VERSION') ? TSUGI_VERSION : null,
        'database_version' => isset($CFG->dbversion) ? (int) $CFG->dbversion : null,
        'php_version' => PHP_VERSION,
        'mysql_version' => $mysql,
        'timezone' => $timezone,
        'git_commit' => $git['commit'],
        'git_branch' => $git['branch'],
    );
    if ( $servicename !== null ) $payload['servicename'] = $servicename;
    if ( $ownername !== null ) $payload['ownername'] = $ownername;
    if ( $owneremail !== null ) $payload['owneremail'] = $owneremail;
    return $payload;
}

/**
 * POST the beacon. Short timeouts so a quiet tsugi.org cannot stall the upgrade.
 * Net::doBody() is not used here because it has no timeout.
 *
 * @return array{ok:bool,detail:string}
 */
function tsugi_admin_upgrade_report_post($payload) {
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ( ! is_string($body) || $body === '' ) {
        return array('ok' => false, 'detail' => 'Could not encode the beacon as JSON');
    }
    if ( ! function_exists('curl_init') ) {
        return array('ok' => false, 'detail' => 'PHP curl extension is not loaded');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, tsugi_admin_upgrade_report_url());
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, tsugi_admin_upgrade_user_agent($payload['host'] ?? null));
    if ( \Tsugi\Util\Net::$VERIFY_PEER ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $result = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( $result === false || $errno ) {
        $detail = is_string($error) && $error !== '' ? $error : 'curl failed';
        return array('ok' => false, 'detail' => $detail);
    }
    if ( $code >= 200 && $code < 300 ) {
        return array('ok' => true, 'detail' => 'HTTP '.$code);
    }
    if ( $code === 0 ) {
        return array('ok' => false, 'detail' => 'No HTTP response');
    }
    return array('ok' => false, 'detail' => 'HTTP '.$code);
}

/**
 * Commit hash, and the branch name when HEAD is on a branch.
 * A missing git binary or a zip deploy leaves both null. Does not fetch.
 *
 * @return array{commit:?string,branch:?string}
 */
function tsugi_admin_upgrade_git($dirroot) {
    global $CFG;

    $git = array(
        'commit' => null,
        'branch' => null,
    );
    if ( ! is_string($dirroot) || $dirroot === '' || ! is_dir($dirroot.'/.git') ) {
        return $git;
    }

    try {
        if ( isset($CFG->git_command) && is_string($CFG->git_command) && $CFG->git_command !== '' ) {
            \Tsugi\Util\Git::set_bin($CFG->git_command);
        }
        $repo = new \Tsugi\Util\GitRepo($dirroot);
        $commit = tsugi_admin_upgrade_git_line($repo, 'rev-parse HEAD');
        if ( ! is_string($commit) || ! preg_match('/^[0-9a-f]{7,64}$/', $commit) ) {
            return $git;
        }
        $git['commit'] = $commit;
        $branch = tsugi_admin_upgrade_git_line($repo, 'rev-parse --abbrev-ref HEAD');
        if ( is_string($branch) && $branch !== '' && $branch !== 'HEAD' ) {
            $git['branch'] = $branch;
        }
    } catch (\Throwable) {
        return $git;
    }
    return $git;
}

function tsugi_admin_upgrade_git_line($repo, $command) {
    $line = tsugi_admin_upgrade_git_raw($repo, $command);
    if ( ! is_string($line) ) return null;
    $line = trim($line);
    return $line === '' ? null : $line;
}

function tsugi_admin_upgrade_git_raw($repo, $command) {
    try {
        $out = $repo->run($command);
        return is_string($out) ? $out : null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * User-Agent for the beacon. The parenthetical is the site host only,
 * so a password embedded in $CFG->wwwroot is not sent in the header.
 */
function tsugi_admin_upgrade_user_agent($host) {
    if ( ! is_string($host) || $host === '' ) {
        $host = 'unknown';
    }
    $host = str_replace(array("\r", "\n"), '', $host);
    $version = defined('TSUGI_VERSION') ? TSUGI_VERSION : 'dev';
    return 'Tsugi/'.$version.' ('.$host.') PHP/'.phpversion();
}

/**
 * Counts from the client id check. No key ids, URLs, or value samples.
 */
function tsugi_admin_upgrade_client_id_comment() {
    try {
        $duplicates = count(\Tsugi\Services\Admin\AdminService::issuerClientDuplicateRows());
        $configRows = 0;
        foreach ( \Tsugi\Services\Admin\AdminService::clientConfigConflictGroups() as $group ) {
            $keys = $group['keys'] ?? null;
            if ( is_array($keys) ) $configRows += count($keys);
        }
        $deployments = count(\Tsugi\Services\Admin\AdminService::keyDeploymentConflictGroups());
        $long = count(\Tsugi\Services\Admin\AdminService::valuesLongerThan255());
        $nonAscii = count(\Tsugi\Services\Admin\AdminService::nonAsciiValues());
    } catch (\Throwable $e) {
        return 'client id check failed';
    }

    return 'duplicate (client id, issuer, deployment id) rows: '.$duplicates
        .', rows with mismatched client configuration urls: '.$configRows
        .', keys with more than one deployment id: '.$deployments
        .', fields too long: '.$long
        .', non-ascii fields: '.$nonAscii;
}

/**
 * Checkout id.
 *
 * Preimage is the site host, then a newline and owner email when that email
 * is a non-empty string, then a newline and the literal 42. A missing host
 * is an empty string. The receiver hashes the same bytes.
 */
function tsugi_admin_upgrade_install_id($host, $owneremail = null) {
    $preimage = (string) $host;
    if ( is_string($owneremail) && $owneremail !== '' ) {
        $preimage .= "\n".$owneremail;
    }
    return hash('sha256', $preimage."\n42");
}

/**
 * Hostname from wwwroot. apphome is used when wwwroot has no host.
 */
function tsugi_admin_upgrade_host($wwwroot, $apphome) {
    $host = tsugi_admin_upgrade_host_from($wwwroot);
    if ( $host === null ) $host = tsugi_admin_upgrade_host_from($apphome);
    return $host;
}

function tsugi_admin_upgrade_host_from($url) {
    $url = tsugi_admin_upgrade_public_url($url);
    if ( ! is_string($url) || $url === '' ) return null;
    $host = parse_url($url, PHP_URL_HOST);
    if ( ! is_string($host) || $host === '' ) {
        $host = parse_url('http://'.$url, PHP_URL_HOST);
    }
    if ( ! is_string($host) || $host === '' ) return null;
    $host = strtolower($host);
    if ( strlen($host) >= 2 && $host[0] === '[' && substr($host, -1) === ']' ) {
        $host = substr($host, 1, -1);
    }
    return $host;
}

function tsugi_admin_upgrade_text($value, $limit) {
    if ( ! is_string($value) ) return null;
    $value = trim($value);
    if ( $value === '' ) return null;
    if ( function_exists('mb_substr') ) return mb_substr($value, 0, $limit);
    return substr($value, 0, $limit);
}

/**
 * Drop userinfo from an http(s) URL so a credential stuffed in a remote cannot ride along.
 * scp-style git remotes (git@host:path) are left as-is; the user there is not a password.
 */
function tsugi_admin_upgrade_public_url($url) {
    if ( ! is_string($url) ) return null;
    $url = trim($url);
    if ( $url === '' ) return null;
    if ( preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) ) {
        $parts = parse_url($url);
        if ( ! is_array($parts) || empty($parts['host']) ) return null;
        $clean = $parts['scheme'].'://'.$parts['host'];
        if ( isset($parts['port']) ) $clean .= ':'.$parts['port'];
        if ( isset($parts['path']) ) $clean .= $parts['path'];
        return $clean;
    }
    return $url;
}

if ( PHP_SAPI === 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__) ) {
    if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
    require_once __DIR__ . '/../config.php';
    $tsugiUpgradeArg = $_SERVER['argv'][1] ?? '';
    if ( $tsugiUpgradeArg !== '' && $tsugiUpgradeArg !== 'beacon' ) {
        fwrite(STDERR, "Usage: php upgrade.php [beacon]\n");
        exit(1);
    }
    if ( isset($_SERVER['argv'][2]) ) {
        fwrite(STDERR, "Usage: php upgrade.php [beacon]\n");
        exit(1);
    }
    tsugi_admin_upgrade_run($tsugiUpgradeArg === 'beacon');
}
