<?php

require_once "classes.php";

function loadGrades($pdo) {
    global $CFG;
    $LTI = requireData(array('link_id', 'role'));
    $instructor = isInstructor($LTI);
    if ( ! $instructor ) die("Requires instructor role");
    $p = $CFG->dbprefix;

    // Get basic grade data
    $stmt = pdoQueryDie($pdo,
        "SELECT R.result_id AS result_id, R.user_id AS user_id,
            grade, note, R.json AS json, R.updated_at AS updated_at, displayname, email
        FROM {$p}lti_result AS R
        JOIN {$p}lti_user AS U ON R.user_id = U.user_id
        WHERE R.link_id = :LID
        ORDER BY updated_at DESC",
        array(":LID" => $LTI['link_id'])
    );
    return $stmt;
}

// $detail is either false or a class with methods
function showGrades($stmt, $detail = false) {
    echo('<table border="1">');
    echo("\n<tr><th>Name<th>Email</th><th>Grade</th><th>Date</th></tr>\n");

    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        echo('<tr><td>');
        if ( $detail ) {
            $detail->link($row);
        } else {
            echo(htmlent_utf8($row['displayname']));
        }
        echo("</td>\n<td>".htmlent_utf8($row['email'])."</td>
            <td>".htmlent_utf8($row['grade'])."</td>
            <td>".htmlent_utf8($row['updated_at'])."</td>
        </tr>\n");
    }
    echo("</table>\n");
}

function loadGrade($pdo, $user_id) {
    global $CFG;
    $LTI = requireData(array('link_id', 'role'));
    $instructor = isInstructor($LTI);
    if ( ! $instructor ) die("Requires instructor role");
    $p = $CFG->dbprefix;

    // Get basic grade data
    $stmt = pdoQueryDie($pdo,
        "SELECT R.result_id AS result_id, R.user_id AS user_id,
            grade, note, R.json AS json, R.updated_at AS updated_at, displayname, email
        FROM {$p}lti_result AS R
        JOIN {$p}lti_user AS U ON R.user_id = U.user_id
        WHERE R.link_id = :LID AND R.user_id = :UID
        GROUP BY U.email",
        array(":LID" => $LTI['link_id'], ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row;
}

function showGradeInfo($row) {
    echo('<p><a href="'.sessionize("grades.php").'">Back to All Grades</a>'."</p><p>\n");
    echo("User Name: ".htmlent_utf8($row['displayname'])."<br/>\n");
    echo("User Email: ".htmlent_utf8($row['email'])."<br/>\n");
    echo("Last Submision: ".htmlent_utf8($row['updated_at'])."<br/>\n");
    echo("Score: ".htmlent_utf8($row['grade'])."<br/>\n");
    echo("</p>\n");
}
