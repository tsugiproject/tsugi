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

// https://stackoverflow.com/questions/3433465/mysql-delete-all-rows-older-than-10-minutes
function ghostBust() {
    global $PDOX, $CFG;
    // TODO: Remove after testing
    return;
    $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lms_tools_status 
            WHERE updated_at < (NOW() - INTERVAL 60 MINUTE)");
}

function getClusterInfo() {
    global $PDOX, $CFG;
    ghostBust();
    $rows = $PDOX->allRowsDie(
        "SELECT ipaddr, name, description, status_note, S.updated_at
         FROM {$CFG->dbprefix}lms_tools_status AS S
         JOIN {$CFG->dbprefix}lms_tools as T ON S.tool_id = T.tool_id
         ORDER BY tool_id"
    );
    return ( $rows ) ;
}

/**
 * Get a list of IPs of the other servers in the cluster
 */
function getClusterIPs($rows) {
    $retval = array();
    $serverIP = Net::serverIP();
    foreach ( $rows as $row ) {
        if ( in_array($row['ipaddr'], $retval) ) continue;
        if ( $row['ipaddr'] == $serverIP ) continue;
    }
    return $retval;
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
    try {
        $update = $repo->run('remote update');
        $detail->writeable = true;
    } catch (Exception $e) {
        $detail->writeable = false;
        $update = 'Caught exception: '.$e->getMessage(). "\n";
    }
    $detail->update_note = $update;
    $status = $repo->run('status -uno');
    $detail->status_note = $status;
    $detail->updates = strpos($status, 'Your branch is behind') !== false;
    // https://stackoverflow.com/questions/2231546/git-see-my-last-commit
    $commit_log = $repo->run('log --name-status HEAD^..HEAD');
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
}
