<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

// Gets counts and max of the submissions
$stmt = pdoQueryDie($db,
    "SELECT A.link_id, A.assn_id, S.user_id AS user_id, email, displayname, S.submit_id as submit_id, 
        MAX(points) as max_points, COUNT(points) as count_points, C.grade_count as grade_count
    FROM {$p}peer_assn AS A JOIN {$p}peer_submit as S 
        ON A.assn_id = S.assn_id
    LEFT JOIN {$p}peer_grade AS G 
        ON S.submit_id = G.submit_id
    JOIN {$p}lti_user AS U
        ON S.user_id = U.user_id
    LEFT JOIN (
        SELECT G.user_id AS user_id, count(G.user_id) as grade_count
        FROM {$p}peer_assn AS A JOIN {$p}peer_submit as S 
        JOIN {$p}peer_grade AS G 
            ON A.assn_id = S.assn_id AND S.submit_id = G.submit_id
        WHERE A.link_id = :LID
        GROUP BY G.user_id 
        ) AS C
        ON U.user_id = C.user_id
    WHERE A.link_id = :LID
    GROUP BY S.submit_id
    ORDER BY S.user_id",
    array(":LID" => $LTI['link_id'])
);

// View 
headerContent();
?>
</head>
<body>
<?php
flashMessages();
welcomeUserCourse($LTI);

echo('<table border="1">');
echo("\n<tr><th>User</th><th>Email</th><th>Max Score</th><th>Scores</th><th>Graded</th></tr>\n");

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    echo('<tr><td><a href="student.php?user_id='.$row['user_id'].'">'.htmlent_utf8($row['displayname'])."</a></td>
        <td>".htmlent_utf8($row['email'])."</td>
        <td>".htmlent_utf8($row['max_points'])."</td><td>".htmlent_utf8($row['count_points'])."</td>
        <td>".htmlent_utf8($row['grade_count'])."</td></tr>\n");
}
echo("</table>\n");

?>
<form method="post">
<br/>
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Cancel">
</form>
<?

footerContent();
