<?php

function loadGrades($pdo) {
    global $CFG;
    $LTI = requireData(array('link_id', 'role'));
    $instructor = isInstructor($LTI);
    if ( ! $instructor ) die("Requires instructor role");
    $p = $CFG->dbprefix;

    // Get basic grade data
    $stmt = pdoQueryDie($pdo,
        "SELECT R.result_id AS result_id, grade, note, R.json AS json, R.updated_at AS updated_at, displayname, email
        FROM {$p}lti_result AS R
        JOIN {$p}lti_user AS U ON R.user_id = U.user_id
        WHERE R.link_id = :LID
        GROUP BY U.email",
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
            $detail->link($row['displayname'], $row['result_id']);
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
