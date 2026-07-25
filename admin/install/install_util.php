<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;

function getRepoOrigin($repo) {
    $output = $repo->run('remote -v');
    $lines = explode("\n",$output);
    foreach($lines as $line) {
        $matches = array();
        preg_match( '/^origin\s+([^ ]*)\s+\(fetch\)$/', $line, $matches);
        if ( count($matches) < 2 ) continue;
        $origin = trim($matches[1]);
        if ( strrpos($origin, '.git') == strlen($origin)-4) return $origin;
        return $origin . '.git';
    }
    return false;
}

/** True if ref exists locally or as origin/<ref>. */
function repoHasRef($repo, $ref) {
    if ( ! is_string($ref) || ! preg_match('/^[A-Za-z0-9._\/+-]+$/', $ref) ) {
        return false;
    }
    try {
        $repo->run('rev-parse --verify --quiet '.$ref);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/** Best-effort default branch (origin/HEAD, current, then main/master). */
function getRepoDefaultBranch($repo) {
    try {
        $out = trim($repo->run('symbolic-ref --short refs/remotes/origin/HEAD'));
        if ( strpos($out, 'origin/') === 0 ) {
            return substr($out, 7);
        }
        if ( U::strlen($out) > 0 ) return $out;
    } catch (Exception $e) {
        // fall through
    }
    try {
        $out = trim($repo->run('rev-parse --abbrev-ref HEAD'));
        if ( U::strlen($out) > 0 && $out !== 'HEAD' ) return $out;
    } catch (Exception $e) {
        // fall through
    }
    foreach ( array('main', 'master') as $candidate ) {
        if ( repoHasRef($repo, $candidate) || repoHasRef($repo, 'origin/'.$candidate) ) {
            return $candidate;
        }
    }
    return 'master';
}

/**
 * Resolve checkout target. Empty or stale "master" falls back to the repo default (often main).
 */
function resolveGitCheckout($repo, $gitversion) {
    if ( U::strlen($gitversion) < 1 ) {
        return getRepoDefaultBranch($repo);
    }
    if ( repoHasRef($repo, $gitversion) || repoHasRef($repo, 'origin/'.$gitversion) ) {
        return $gitversion;
    }
    if ( $gitversion === 'master' ) {
        $default = getRepoDefaultBranch($repo);
        if ( $default !== 'master' ) return $default;
        if ( repoHasRef($repo, 'main') || repoHasRef($repo, 'origin/main') ) {
            return 'main';
        }
    }
    return $gitversion;
}

// https://stackoverflow.com/questions/3433465/mysql-delete-all-rows-older-than-10-minutes
function ghostBust() {
    global $PDOX, $CFG;
    $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lms_tools_status 
            WHERE updated_at < (NOW() - INTERVAL 55 MINUTE)");
    return;
}

function getClusterInfo() {
    global $PDOX, $CFG;
    ghostBust();
    $rows = $PDOX->allRowsDie(
        "SELECT ipaddr, name, description, commit, commit_log, clone_url, gitversion, status_note, S.updated_at
         FROM {$CFG->dbprefix}lms_tools_status AS S
         JOIN {$CFG->dbprefix}lms_tools as T ON S.tool_id = T.tool_id
         ORDER BY ipaddr"
    );
    return ( $rows ) ;
}

/**
 * Get a list of IPs of the other servers in the cluster
 */
function getClusterIPs($rows=false) {
    if ( ! $rows ) $rows = getClusterInfo();
    $retval = array();
    $serverIP = Net::serverIP();
    foreach ( $rows as $row ) {
        if ( in_array($row['ipaddr'], $retval) ) continue;
        if ( $row['ipaddr'] == $serverIP ) continue;
        $retval[] = $row['ipaddr'];
    }
    return $retval;
}

function doClone($remote, $folder) {
    global $PDOX, $CFG;

    $repo = new \Tsugi\Util\GitRepo($folder, true,  false);
    $log = $repo->clone_from($remote);
    $results = "Command: git clone $remote\n";
    $results .= "Folder: $folder\n\n";
    $results .= $log;

    // Read the files...
    $files = scandir($folder);
    if ( count($files) < 2 ) {
        $results .= "No Files Checked Out\n";
    } else {
        $results .= "Checked Out:\n";
        foreach($files as $file) {
            if ( $file == '.' || $files == '..' ) continue;
            $results .= '  '.$file."\n";
        }
        $detail = new \stdClass();
        addRepoInfo($detail, $repo);

        $sql = "INSERT INTO {$CFG->dbprefix}lms_tools
            ( toolpath, name, description, clone_url, gitversion, created_at, updated_at ) VALUES
            ( :toolpath, :name, :description, :clone_url, :gitversion, NOW(), NOW() )
            ON DUPLICATE KEY UPDATE
                name=:name, description=:description, clone_url=:clone_url,
                gitversion=:gitversion, updated_at=NOW()
        ";
        $values = array(
            ":toolpath" => $folder,
            ":name" => 'name',
            ":description" => 'description',
            ":clone_url" => $remote,
            ":gitversion" => getRepoDefaultBranch($repo)
        );
        $q = $PDOX->queryReturnError($sql, $values);

        // Update the status for this cluster
        updateToolStatus($folder, $detail);
    }
    return $results;
}

function updateToolStatus($tool_path, $detail) {
    global $PDOX, $CFG;

    $row = $PDOX->rowDie(
        "SELECT tool_id FROM {$CFG->dbprefix}lms_tools WHERE toolpath = :toolpath",
        array(":toolpath" => $tool_path)
    );

    if ( ! $row || ! U::get($row, 'tool_id')) {
        error_log("Could not find tool_id for $tool_path");
        return false;
    }

    $tool_id = $row['tool_id'];

    $serverIP = Net::serverIP();
    $sql = "INSERT INTO {$CFG->dbprefix}lms_tools_status
            ( tool_id, ipaddr, status_note, commit_log, 
                commit, created_at, updated_at ) 
        VALUES
            ( :tool_id, :ipaddr, :status_note, :commit_log, 
                :commit, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE
             status_note = :status_note, commit_log=:commit_log, 
            commit=:commit, updated_at = NOW()";
    $values = array(
        ":tool_id" => $tool_id,
        ":ipaddr" => $serverIP,
        ":status_note" => $detail->status_note,
        ":commit_log" => $detail->commit_log,
        ":commit" => $detail->commit,
    );
    $q = $PDOX->queryDie($sql, $values);
    return true;
}

// Notes
// git reset --hard 5979437e27bd47637c4b562b33e861ce32b6468b

/**
  * Load Information for a github repo
  *
  * Does not set name or description
  */
function addRepoInfo($detail, $repo) {
    // Gather the information for the repo folder
    $errors = array();
    try {
        $update = $repo->run('remote update');
        $detail->writeable = true;
    } catch (Exception $e) {
        $detail->writeable = false;
        $update = 'Caught exception: '.$e->getMessage(). "\n";
        $errors[] = 'remote update: '.$e->getMessage();
    }
    $detail->update_note = $update;
    try {
        $status = $repo->run('status -uno');
    } catch (Exception $e) {
        $status = 'Caught exception: '.$e->getMessage(). "\n";
        $errors[] = 'status: '.$e->getMessage();
    }
    $detail->status_note = $status;
    $detail->updates = strpos($status, 'Your branch is behind') !== false;
    // Use -1 so single-commit / shallow repos work (HEAD^ fails there).
    try {
        $commit_log = $repo->run('log -1 --name-status');
    } catch (Exception $e) {
        $commit_log = 'Caught exception: '.$e->getMessage(). "\n";
        $errors[] = 'log: '.$e->getMessage();
    }
    $detail->commit_log = $commit_log;
    $lines = explode("\n",$commit_log);
    $detail->commit = '';
    if ( count($lines) > 0 ) {
        $line = $lines[0];
        $matches = array();            
        preg_match( '/^commit\s+([0-9a-f]*)$/', $line, $matches);
        if ( count($matches) >= 2 ) {
            $detail->commit = trim($matches[1]);
        }
    }
    if ( count($errors) > 0 ) {
        $detail->error = implode("\n", $errors);
    }
    try {
        $detail->gitversion = getRepoDefaultBranch($repo);
    } catch (Exception $e) {
        $detail->gitversion = 'main';
    }
}
