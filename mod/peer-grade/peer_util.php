<?php

function loadSubmission($db, $LTI, $assn_id) 
{
    global $CFG;
    $submit_row = false;
    if ( $assn_id != false ) {
        $stmt = pdoQueryDie($db,
            "SELECT submit_id, json, note, reflect FROM {$CFG->dbprefix}peer_submit 
                WHERE assn_id = :AID AND user_id = :UID",
            array(":AID" => $assn_id, ":UID" => $LTI['user_id'])
        );
        $submit_row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $submit_row;
}

function loadParts($db, $LTI, $submit_row) 
{
    global $CFG;
    if ( $submit_row === false ) return false;
    $submit_id = $submit_row['submit_id'];
    $part_rows = false;
    if ( $submit_id != false ) {
        $stmt = pdoQueryDie($db,
            "SELECT partno, note, blob_id FROM {$CFG->dbprefix}peer_part 
                WHERE submit_id = :SID",
            array(":SID" => $submit_id)
        );
        if ( $stmt !== null ) $part_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $part_rows;
}


