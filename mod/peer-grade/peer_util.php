<?php

function loadSubmission($db, $assn_id, $user_id) 
{
    global $CFG;
    $submit_row = false;
    if ( $assn_id != false ) {
        $stmt = pdoQueryDie($db,
            "SELECT submit_id, json, note, reflect FROM {$CFG->dbprefix}peer_submit 
                WHERE assn_id = :AID AND user_id = :UID",
            array(":AID" => $assn_id, ":UID" => $user_id)
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

// Check for ungraded submissions
function loadUngraded($db, $LTI, $assn_id)
{
    global $CFG;
    $stmt = pdoQueryDie($db,
        "SELECT S.submit_id, S.user_id, S.created_at, count(G.user_id) AS submit_count 
            FROM {$CFG->dbprefix}peer_submit AS S LEFT JOIN {$CFG->dbprefix}peer_grade AS G 
            ON S.submit_id = G.submit_id 
            WHERE S.assn_id = :AID AND S.user_id != :UID AND 
            S.submit_id NOT IN 
                ( SELECT DISTINCT submit_id from {$CFG->dbprefix}peer_grade WHERE user_id = :UID)
            GROUP BY S.submit_id, S.created_at 
            ORDER BY submit_count ASC, S.created_at ASC
            LIMIT 10",
        array(":AID" => $assn_id, ":UID" => $LTI['user_id'])
    );
    return $stmt->fetchAll();
}

function showSubmission($assn_json, $submit_json)
{
    $blob_ids = $submit_json->blob_ids;
    $partno = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == "image" ) {
            $blob_id = $blob_ids[$partno++];
            $url = getAccessUrlForBlob($blob_id);
            echo ('<a href="'.sessionize($url).'" target="_blank">');
            echo ('<img src="'.sessionize($url).'" width="120"></a>'."\n");
        }
    }

    if ( strlen($submit_json->notes) > 1 ) {
        echo("<p>Notes: ".htmlent_utf8($submit_json->notes)."</p>\n");
    }
}
