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
        "SELECT tool_id, clone_url, deleted FROM {$CFG->dbprefix}lms_tools WHERE toolpath = :toolpath",
        array(':toolpath' => $new_path)
    );
    if ( $existing && (int) $existing['tool_id'] !== (int) $tool['tool_id'] ) {
        if ( ! tsugi_admin_install_same_repo($existing['clone_url'], $remote) ) {
            echo('Fallback path belongs to a different tool: '.htmlentities($new_path)."\n");
            return false;
        }
        // Keep the row that is still installed. A deleted row at the
        // computed folder must not block the live record from moving there.
        $drop_id = $tool['tool_id'];
        if ( (int) $existing['deleted'] === 1 && (int) $tool['deleted'] !== 1 ) {
            $drop_id = $existing['tool_id'];
        }
        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}lms_tools WHERE tool_id = :tool_id",
            array(':tool_id' => $drop_id)
        );
        if ( ! $q->success ) {
            echo('Could not drop stale tool path: '.htmlentities($q->errorImplode)."\n");
            return false;
        }
        if ( (int) $drop_id === (int) $tool['tool_id'] ) {
            echo('Dropped stale tool path '.htmlentities($old_path)."\n");
            echo('Checkout is already recorded at '.htmlentities($new_path)."\n");
            return false;
        }
        echo('Dropped deleted tool already recorded at '.htmlentities($new_path)."\n");
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
    echo('Tool path '.htmlentities($old_path).' -> '.htmlentities($new_path)."\n");
    return true;
}

/**
 * The checkout this process is running from.
 */
function tsugi_admin_install_dirroot() {
    global $CFG;

    $dirroot = isset($CFG->dirroot) ? (string) $CFG->dirroot : '';
    if ( $dirroot === '' ) return '';
    $resolved = realpath($dirroot);
    if ( is_string($resolved) && $resolved !== '' ) $dirroot = $resolved;
    return rtrim(str_replace('\\', '/', $dirroot), '/');
}

/**
 * True when this row is the checkout this process is running from.
 */
function tsugi_admin_install_is_runtime($path) {
    $dirroot = tsugi_admin_install_dirroot();
    if ( $dirroot === '' ) return false;

    $path = rtrim(str_replace('\\', '/', (string) $path), '/');
    if ( $path === $dirroot ) return true;

    $path_real = realpath($path);
    if ( is_string($path_real) && $path_real !== '' ) {
        $path_real = rtrim(str_replace('\\', '/', $path_real), '/');
        if ( $path_real === $dirroot ) return true;
    }
    return false;
}

/**
 * Repo name from a clone URL, without a trailing .git.
 */
function tsugi_admin_install_repo_name($remote) {
    $name = basename(rtrim(str_replace('\\', '/', (string) $remote), '/'), '.git');
    if ( $name === '' || $name === '.' || $name === '..' ) return '';
    return $name;
}

/**
 * Where a module is checked out: install_folder plus the repo name.
 */
function tsugi_admin_install_repo_folder($remote) {
    global $CFG;

    $install = isset($CFG->install_folder) ? $CFG->install_folder : '';
    if ( ! is_string($install) || $install === '' ) return '';
    $name = tsugi_admin_install_repo_name($remote);
    if ( $name === '' ) return '';
    $folder = rtrim(str_replace('\\', '/', $install), '/').'/'.$name;
    return U::remove_relative_path($folder);
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
$runtime_remote = '';
try {
    $runtime_repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
    $runtime_origin = \Tsugi\Services\Admin\AdminService::getRepoOrigin($runtime_repo);
    if ( is_string($runtime_origin) ) $runtime_remote = $runtime_origin;
} catch (Exception $e) {
    echo('Could not read this checkout origin: '.htmlentities($e->getMessage())."\n");
}
foreach($tools as $tool) {
    echo("\n======\n");
    echo("Name: ".htmlentities($tool['name'])."\n");
    echo("Description: ".htmlentities($tool['description'])."\n");
    $stored_path = \Tsugi\Util\U::remove_relative_path($tool['toolpath']);
    $stored_path = rtrim(str_replace('\\', '/', $stored_path), '/');
    $remote = $tool['clone_url'];
    echo("URL: ".htmlentities($remote)."\n");
    if ( $tool['name'] === 'name' ) {
        $repo_name = tsugi_admin_install_repo_name($remote);
        if ( $repo_name !== '' && $repo_name !== 'name' ) {
            $named = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lms_tools
                 SET name = :name, updated_at = NOW()
                 WHERE tool_id = :tool_id AND name = 'name'",
                array(
                    ':name' => $repo_name,
                    ':tool_id' => $tool['tool_id'],
                )
            );
            if ( $named->success ) {
                echo('Name '.htmlentities($tool['name']).' -> '.htmlentities($repo_name)."\n");
                $tool['name'] = $repo_name;
            } else {
                echo('Could not update tool name: '.htmlentities($named->errorImplode)."\n");
            }
        }
    }
    $configured_version = $tool['gitversion'];
    if ( isset($CFG->branch_override) && is_array($CFG->branch_override) && U::get($CFG->branch_override, $remote) ) {
        $configured_version = U::get($CFG->branch_override, $remote);
    }
    echo("Version: ".htmlentities(U::strlen($configured_version) ? $configured_version : '(default)')."\n");
    $runtime = ( $tool['name'] === 'Tsugi Admin'
        || tsugi_admin_install_is_runtime($stored_path)
        || tsugi_admin_install_same_repo($remote, $runtime_remote) );
    if ( $runtime ) {
        $path = tsugi_admin_install_dirroot();
    } else {
        if (! U::conservativeUrl($remote) ) {
            echo('Badly formatted git URL: '.htmlentities($remote)."\n");
            continue;
        }
        // The stored path is a note from whichever server last wrote it.
        // This server checks the repo out under its own install folder.
        $path = tsugi_admin_install_repo_folder($remote);
        if ( $path === '' ) {
            echo("Install folder is not configured.\n");
            continue;
        }
    }
    if ( $path === '' ) {
        echo("Skipping this checkout. The site update already pulls it.\n");
        continue;
    }
    echo("Path: ".htmlentities($path)."\n");
    if ( $stored_path !== $path ) {
        if ( ! tsugi_admin_install_retarget_tool($tool, $stored_path, $path, $remote) ) {
            continue;
        }
    }
    if ( $runtime ) {
        echo("Skipping this checkout. The site update already pulls it.\n");
        continue;
    }
    if ( $tool['deleted'] == 1 ) {
        echo(" Tool is deleted...\n");
        continue;
    }

    // Check to see if we need to clone
    if ( ! file_exists($path) ) {
        echo("\nNeed to Checkout ".htmlentities($remote)." to ".htmlentities($path)."\n");
        $parent = dirname($path);
        if ( ! is_dir($parent) ) {
            echo('Bad parent path: '.htmlentities($parent)."\n");
            continue;
        }
        if ( ! is_writeable($parent) ) {
            echo('Parent path is not writeable: '.htmlentities($parent)."\n");
            continue;
        }

        echo("doClone: $remote $parent\n");
        // The row already records this tool. doClone must not upsert it,
        // or it replaces name, description, and gitversion with placeholders.
        \Tsugi\Services\Admin\AdminService::doClone($remote, $path, false);
        continue;
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
             WHERE tool_id=:tool_id",
            array(
                ":gitversion" => $gitversion,
                ":tool_id" => $tool['tool_id']
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
