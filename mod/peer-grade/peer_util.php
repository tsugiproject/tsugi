<?php

function loadUserInfo($db, $user_id) 
{
    global $CFG;
    $cacheloc = 'lti_user';
    $row = cacheCheck($cacheloc, $user_id);
    if ( $row != null ) return $row;
    $stmt = pdoQueryDie($db,
        "SELECT displayname, email FROM {$CFG->dbprefix}lti_user
            WHERE user_id = :UID",
        array(":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $user_id, $row);
    return $row;
}

// Loads the assignment associated with this link
function loadAssignment($db, $LTI)
{
    global $CFG;
    $cacheloc = 'peer_assn';
    $row = cacheCheck($cacheloc, $LTI['link_id']);
    if ( $row != null ) return $row;
    $stmt = pdoQueryDie($db,
        "SELECT assn_id, json FROM {$CFG->dbprefix}peer_assn WHERE link_id = :ID",
        array(":ID" => $LTI['link_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $LTI['link_id'], $row);
    return $row;
}

function loadSubmission($db, $assn_id, $user_id) 
{
    global $CFG;
    $cacheloc = 'peer_submit';
    $cachekey = $assn_id + "::" + $user_id;
    $submit_row = cacheCheck($cacheloc, $cachekey);
    if ( $submit_row != null ) return $submit_row;
    $submit_row = false;

    $stmt = pdoQueryDie($db,
        "SELECT submit_id, json, note, reflect FROM {$CFG->dbprefix}peer_submit 
            WHERE assn_id = :AID AND user_id = :UID",
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $submit_row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $cachekey, $submit_row);
    return $submit_row;
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
            if ( is_array($blob_id) ) $blob_id = $blob_id[0];
            $url = getAccessUrlForBlob($blob_id);
            echo ('<a href="'.sessionize($url).'" target="_blank">');
            echo ('<img src="'.sessionize($url).'" width="120"></a>'."\n");
        }
    }

    if ( strlen($submit_json->notes) > 1 ) {
        echo("<p>Notes: ".htmlent_utf8($submit_json->notes)."</p>\n");
    }
}

function computeGrade($db, $assn_id, $assn_json, $user_id)
{
    global $CFG;
    $stmt = pdoQueryDie($db,
        "SELECT S.assn_id, S.user_id AS user_id, email, displayname, S.submit_id as submit_id, 
            MAX(points) as max_points, COUNT(points) as count_points, C.grade_count as grade_count
        FROM {$CFG->dbprefix}peer_submit as S 
        JOIN {$CFG->dbprefix}peer_grade AS G 
            ON S.submit_id = G.submit_id
        JOIN {$CFG->dbprefix}lti_user AS U
            ON S.user_id = U.user_id
        LEFT JOIN (
            SELECT G.user_id AS user_id, count(G.user_id) as grade_count
            FROM {$CFG->dbprefix}peer_submit as S 
            JOIN {$CFG->dbprefix}peer_grade AS G 
                ON S.submit_id = G.submit_id
            WHERE S.assn_id = :AID AND G.user_id = :UID
            ) AS C
            ON U.user_id = C.user_id
        WHERE S.assn_id = :AID AND S.user_id = :UID",
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ( $row === false || $row['user_id']+0 == 0 ) return -1;

    // Compute the overall points
    $assnpoints = $row['max_points']+0;
    if ( $assnpoints < 0 ) $assnpoints = 0;
    if ( $assnpoints > $assn_json->maxpoints ) $assnpoints = $assn_json->maxpoints;

    $gradecount = $row['grade_count']+0;
    if ( $gradecount < 0 ) $gradecount = 0;
    if ( $gradecount > $assn_json->minassess ) $gradecount = $assn_json->minassess;
    $gradepoints = $gradecount * $assn_json->assesspoints;
    return ($assnpoints + $gradepoints) / $assn_json->totalpoints;
}
