<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

/**
 * Compare clone URLs ignoring a trailing .git and slash.
 */
function tsugi_admin_install_same_repo($left, $right) {
    $key = function ($url) {
        $url = strtolower(trim((string) $url));
        $url = preg_replace('/\.git$/', '', $url);
        return rtrim($url, '/');
    };
    $left = $key($left);
    $right = $key($right);
    return $left !== '' && $left === $right;
}

/**
 * Point an lms_tools row at the checkout we actually found.
 * toolpath is unique, so a second row for that checkout is dropped.
 * Returns true when this row should still be pulled or cloned.
 */
function tsugi_admin_install_retarget_tool($tool, $old_path, $new_path, $remote) {
    global $CFG, $PDOX;

    if ( $old_path === $new_path ) return true;
    if ( strlen($new_path) > 128 ) {
        echo('Tool path is longer than lms_tools.toolpath allows: '.htmlentities($new_path)."\n");
        return false;
    }

    $existing = $PDOX->rowDie(
        "SELECT tool_id, clone_url FROM {$CFG->dbprefix}lms_tools WHERE toolpath = :toolpath",
        array(':toolpath' => $new_path)
    );
    if ( $existing && (int) $existing['tool_id'] !== (int) $tool['tool_id'] ) {
        if ( ! tsugi_admin_install_same_repo($existing['clone_url'], $remote) ) {
            echo('Fallback path belongs to a different tool: '.htmlentities($new_path)."\n");
            return false;
        }
        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}lms_tools WHERE tool_id = :tool_id",
            array(':tool_id' => $tool['tool_id'])
        );
        if ( ! $q->success ) {
            echo('Could not drop stale tool path: '.htmlentities($q->errorImplode)."\n");
            return false;
        }
        echo('Dropped stale tool path '.htmlentities($old_path)."\n");
        echo('Checkout is already recorded at '.htmlentities($new_path)."\n");
        return false;
    }

    $q = $PDOX->queryReturnError(
        "UPDATE {$CFG->dbprefix}lms_tools
         SET toolpath = :toolpath, updated_at = NOW()
         WHERE tool_id = :tool_id",
        array(
            ':toolpath' => $new_path,
            ':tool_id' => $tool['tool_id'],
        )
    );
    if ( ! $q->success ) {
        echo('Could not update tool path: '.htmlentities($q->errorImplode)."\n");
        return false;
    }
    echo('Updated tool path to '.htmlentities($new_path)."\n");
    return true;
}

/**
 * Web and shell entry for pulling installed modules.
 * The admin controller calls this after the passphrase gate.
 * `php admin/install/update.php` calls it from the shell.
 */
function tsugi_admin_install_update_run() {
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
$tools = $PDOX->allRowsDie("SELECT * FROM {$CFG->dbprefix}lms_tools ORDER BY created_at");
if ( count($tools) < 1 ) {
    echo("<p>No installed tools found in lms_tools.</p>\n");
    return;
}
foreach($tools as $tool) {
    echo("\n======\n");
    echo("Name: ".htmlentities($tool['name'])."\n");
    echo("Description: ".htmlentities($tool['description'])."\n");
    $path = $tool['toolpath'];
    $path = \Tsugi\Util\U::remove_relative_path($path);
    $stored_path = $path;
    echo("Path: ".htmlentities($path)."\n");
    $remote = $tool['clone_url'];
    echo("URL: ".htmlentities($remote)."\n");
    $configured_version = $tool['gitversion'];
    if ( isset($CFG->branch_override) && is_array($CFG->branch_override) && U::get($CFG->branch_override, $remote) ) {
        $configured_version = U::get($CFG->branch_override, $remote);
    }
    echo("Version: ".htmlentities(U::strlen($configured_version) ? $configured_version : '(default)')."\n");
    if ( $tool['deleted'] == 1 ) {
        echo(" Tool is deleted...\n");
        continue;
    }

    // Check to see if we need to clone
    if ( ! file_exists($path) ) {
        echo("\nNeed to Checkout ".htmlentities($remote)." to ".htmlentities($path)."\n");
        if (! U::conservativeUrl($remote) ) {
            echo('Badly formatted git URL: '.$remote."\n");
            continue;
        }
        $parent = dirname($path);
        if ( U::strlen($parent) < 1 || ! file_exists($parent) ) {
            echo('Bad parent path: '.$parent."\n");
            // Perhaps this is checked out to a new location in the /var area
            $parent = U::remove_relative_path($CFG->install_folder);
            $path = $parent . '/' . basename($path);
            echo('Attempting path of '.$path."\n");
            if ( U::strlen($parent) < 1 || ! file_exists($parent) ) {
                echo('Bad parent path: '.$parent."\n");
                continue;
            }
            // Remember the path that worked so the next run does not reprint this.
            if ( ! tsugi_admin_install_retarget_tool($tool, $stored_path, $path, $remote) ) {
                continue;
            }
        }

        if ( ! file_exists($path) ) {
            if ( ! is_writeable($parent) ) {
                echo('Parent path is not writeable: '.$parent."\n");
                continue;
            }

            echo("doClone: $remote $parent\n");
            // This row came from lms_tools and already points at $path.
            // doClone must not upsert it, or it replaces name, description,
            // and gitversion with placeholders.
            \Tsugi\Services\Admin\AdminService::doClone($remote, $path, false);
            continue;
        }
    }

    try {
        $repo = new \Tsugi\Util\GitRepo($path);
        $origin = \Tsugi\Services\Admin\AdminService::getRepoOrigin($repo);
    } catch (Exception $e) {
        echo("\n**** ERROR\n".$e->getMessage()."\n");
        continue;
    }

    echo("Origin: ".htmlentities($origin)."\n");
    echo("Pull----\n");
    try {
        $pull_output = $repo->run('pull -s recursive -X theirs');
    } catch(Exception $e) {
        echo("Error: ".htmlentities($e->getMessage())."\n");
        continue;
    }
    echo(htmlentities($pull_output));

    // After pull, map stale "master" (or empty) to the repo's real default (often main).
    $gitversion = \Tsugi\Services\Admin\AdminService::resolveGitCheckout($repo, $configured_version);
    if ( $gitversion !== $configured_version && U::strlen($configured_version) > 0 ) {
        echo("Resolved: ".htmlentities($configured_version)." -> ".htmlentities($gitversion)."\n");
    }
    $command = 'checkout '.$gitversion;
    echo("Checkout: git ".$command."\n");
    try {
        $check_output = $repo->run($command);
        echo(htmlentities($check_output));
    } catch (Exception $e) {
        echo("Error: ".htmlentities($e->getMessage())."\n");
        continue;
    }

    // Persist remapped branch so the next batch does not keep trying master.
    if ( $gitversion !== $tool['gitversion'] ) {
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lms_tools SET gitversion=:gitversion, updated_at=NOW()
             WHERE toolpath=:toolpath",
            array(
                ":gitversion" => $gitversion,
                ":toolpath" => $path
            )
        );
    }

    $detail = new \stdClass();
    \Tsugi\Services\Admin\AdminService::addRepoInfo($detail, $repo);
    print_r($detail);

    \Tsugi\Services\Admin\AdminService::updateToolStatus($path, $detail);

}


if( ! U::isCli() ) {
    echo("\n</body>\n</html>\n");
}
}

if ( PHP_SAPI === 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__) ) {
    if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
    require_once __DIR__ . '/../../config.php';
    tsugi_admin_install_update_run();
}
