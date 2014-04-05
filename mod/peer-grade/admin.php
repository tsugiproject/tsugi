<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

// Gets counts and max of the submissions
$query_parms = array(":LID" => $LTI['link_id']);
$orderfields =  array("S.user_id", "displayname", "email", "S.updated_at", "user_key", "max_score", "scores", "flagged", "min_score","grade_count");
$searchfields = array("S.user_id", "displayname", "email", "S.updated_at", "user_key");

// Note that inner where is lower case and outer WHERE is upper case on purpose
$sql = 
    "SELECT S.user_id AS user_id, displayname, email, S.submit_id as _submit_id, 
        MAX(points) as max_score, MIN(points) AS min_score, COUNT(points) as scores, 
        COUNT(DISTINCT flag_id) as flagged, C.grade_count as grade_count, 
        MAX(S.updated_at) AS updated_at, user_key
    FROM {$p}peer_assn AS A JOIN {$p}peer_submit as S 
        ON A.assn_id = S.assn_id
    LEFT JOIN {$p}peer_grade AS G 
        ON S.submit_id = G.submit_id
    LEFT JOIN {$p}peer_flag AS F 
        ON S.submit_id = F.submit_id
    JOIN {$p}lti_user AS U
        ON S.user_id = U.user_id
    LEFT JOIN (
        SELECT G.user_id AS user_id, count(G.user_id) as grade_count
        FROM {$p}peer_assn AS A JOIN {$p}peer_submit as S 
        JOIN {$p}peer_grade AS G 
            ON A.assn_id = S.assn_id AND S.submit_id = G.submit_id
        where A.link_id = :LID
        GROUP BY G.user_id 
        ) AS C
        ON U.user_id = C.user_id
    WHERE A.link_id = :LID
    GROUP BY S.submit_id";

// View 
headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

// Make us a paged table
pagedPDO($pdo, $sql, $query_parms, $searchfields, $orderfields, "student.php");

?>
<form method="post">
<br/>
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Cancel">
</form>
<?php

footerContent();
