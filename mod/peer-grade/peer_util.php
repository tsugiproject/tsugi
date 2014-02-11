<?php

function loadUserInfo($pdo, $user_id) 
{
    global $CFG;
    $cacheloc = 'lti_user';
    $row = cacheCheck($cacheloc, $user_id);
    if ( $row != false ) return $row;
    $stmt = pdoQueryDie($pdo,
        "SELECT displayname, email FROM {$CFG->dbprefix}lti_user
            WHERE user_id = :UID",
        array(":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $user_id, $row);
    return $row;
}

// Loads the assignment associated with this link
function loadAssignment($pdo, $LTI)
{
    global $CFG;
    $cacheloc = 'peer_assn';
    $row = cacheCheck($cacheloc, $LTI['link_id']);
    if ( $row != false ) return $row;
    $stmt = pdoQueryDie($pdo,
        "SELECT assn_id, json FROM {$CFG->dbprefix}peer_assn WHERE link_id = :ID",
        array(":ID" => $LTI['link_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $LTI['link_id'], $row);
    return $row;
}

function loadSubmission($pdo, $assn_id, $user_id) 
{
    global $CFG;
    $cacheloc = 'peer_submit';
    $cachekey = $assn_id + "::" + $user_id;
    $submit_row = cacheCheck($cacheloc, $cachekey);
    if ( $submit_row != false ) return $submit_row;
    $submit_row = false;

    $stmt = pdoQueryDie($pdo,
        "SELECT submit_id, json, note, reflect
            FROM {$CFG->dbprefix}peer_submit AS S
            WHERE assn_id = :AID AND S.user_id = :UID",
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $submit_row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $cachekey, $submit_row);
    return $submit_row;
}

// Check for ungraded submissions
function loadUngraded($pdo, $LTI, $assn_id)
{
    global $CFG;
    $stmt = pdoQueryDie($pdo,
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

function showSubmission($LTI, $assn_json, $submit_json)
{
    $blob_ids = $submit_json->blob_ids;
    $urls = $submit_json->urls;
    $blobno = 0;
    $urlno = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == "image" ) {
            $blob_id = $blob_ids[$blobno++];
            if ( is_array($blob_id) ) $blob_id = $blob_id[0];
            $url = getAccessUrlForBlob($blob_id);
            echo ('<a href="'.sessionize($url).'" target="_blank">');
            echo ('<img src="'.sessionize($url).'" width="120"></a>'."\n");
        } else if ( $part->type == "url" ) {
            $url = $urls[$urlno++];
            echo ('<p><a href="'.safe_href($url).'" target="_blank">');
            echo (htmlentities(safe_href($url)).'</a> (Will launch in new window)'."\n");
        }
        
    }

    if ( strlen($submit_json->notes) > 1 ) {
        echo("<p>Notes: ".htmlent_utf8($submit_json->notes)."</p>\n");
    }
}

function computeGrade($pdo, $assn_id, $assn_json, $user_id)
{
    global $CFG;
    $stmt = pdoQueryDie($pdo,
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

// Load the count of grades for this user for an assignment
function loadMyGradeCount($pdo, $LTI, $assn_id) {
    global $CFG;
    $cacheloc = 'peer_grade';
    $cachekey = $assn_id + "::" + $LTI['user_id'];
    $grade_count = cacheCheck($cacheloc, $cachekey);
    if ( $grade_count != false ) return $grade_count;
    $stmt = pdoQueryDie($pdo,
        "SELECT COUNT(grade_id) AS grade_count 
        FROM {$CFG->dbprefix}peer_submit AS S 
        JOIN {$CFG->dbprefix}peer_grade AS G
        ON S.submit_id = G.submit_id
            WHERE S.assn_id = :AID AND G.user_id = :UID",
        array( ':AID' => $assn_id, ':UID' => $LTI['user_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ( $row !== false ) {
        $grade_count = $row['grade_count']+0;
    }
    cacheSet($cacheloc, $cachekey, $grade_count);
    return $grade_count;
}

// Retrieve grades for a submission
// Not cached because another user may have added a grade
// a moment ago
function retrieveSubmissionGrades($pdo, $submit_id)
{
    global $CFG;
    if ( $submit_id === false ) return false;
    $stmt = pdoQueryDie($pdo,
        "SELECT grade_id, points, note, displayname, email
        FROM {$CFG->dbprefix}peer_grade AS G
        JOIN {$CFG->dbprefix}lti_user as U
            ON G.user_id = U.user_id
        WHERE G.submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    $our_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $our_grades;
}

