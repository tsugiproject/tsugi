<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;

function getRepoOrigin($repo) {
    $output = $repo->run('remote -v');
    $lines = explode("\n",$output);
    foreach($lines as $line) {
        $matches = array();            preg_match( '/^origin\s+([^ ]*)\s+\(fetch\)$/', $line, $matches);
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
    $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lms_tools_status 
            WHERE updated_at < (NOW() - INTERVAL 60 MINUTE)");
}

function clusterCount() {
    global $PDOX, $CFG;
    ghostBust();
    $rows = $PDOX->allRowsDie("SELECT DISTINCT ipaddr FROM {$CFG->dbprefix}lms_tools_status");

}

function updateToolStatus($tool_path, $tool_status) {
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
        ( tool_id, ipaddr, status_note, created_at, updated_at ) VALUES
        ( :tool_id, :ipaddr, :status_note, NOW(), NOW() )
        ON DUPLICATE KEY
            UPDATE status_note = :status_note, updated_at = NOW()";
    $values = array(
        ":tool_id" => $tool_id,
        ":ipaddr" => $serverIP,
        ":status_note" => $tool_status,
    );
    $q = $PDOX->queryDie($sql, $values);
    return true;
}

// Notes
// git reset --hard 5979437e27bd47637c4b562b33e861ce32b6468b

