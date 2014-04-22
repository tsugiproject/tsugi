<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$p = $CFG->dbprefix;

$user_info = false;
$links = array();
$user_sql = false;
$class_sql = false;
$summary_sql = false;

$link_id = 0;
if ( isset($_GET['link_id']) ) {
    $link_id = $_GET['link_id']+0;
}

$link_info = false;
if ( $instructor && $link_id > 0 ) {
    $link_info = loadLinkInfo($pdo, $link_id);
}

if ( $instructor && isset($_GET['viewall'] ) ) {
    $query_parms = array(":CID" => $LTI['context_id']);
    $orderfields = array("R.user_id", "displayname", "email", "user_key", "grade_count");
    $searchfields = array("R.user_id", "displayname", "email", "user_key");
    $summary_sql = 
        "SELECT R.user_id AS user_id, displayname, email, COUNT(grade) AS grade_count, user_key
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        JOIN {$p}lti_user as U
            ON R.user_id = U.user_id
        WHERE L.context_id = :CID AND R.grade IS NOT NULL
        GROUP BY R.user_id";

} else if ( $instructor && isset($_GET['link_id'] ) ) {
    $query_parms = array(":LID" => $link_id, ":CID" => $LTI['context_id']);
    $searchfields = array("R.user_id", "displayname", "grade", "R.updated_at");
    $class_sql = 
        "SELECT R.user_id AS user_id, displayname, grade, R.updated_at as updated_at
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        JOIN {$p}lti_user as U
            ON R.user_id = U.user_id
        WHERE R.link_id = :LID AND L.context_id = :CID AND R.grade IS NOT NULL";

} else { // Gets grades for the current or specified 
    $user_id = $LTI['user_id'];
    if ( $instructor && isset($_GET['user_id']) ) {
        $user_id = $_GET['user_id'] + 0;
    }

    $query_parms = array(":UID" => $user_id, ":CID" => $LTI['context_id']);
    $searchfields = array("L.title", "R.grade", "R.note", "R.updated_at");
    $user_sql = 
        "SELECT L.title as title, R.grade AS grade, 
            R.note AS note, R.updated_at as updated_at
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL";
    $user_info = loadUserInfo($pdo, $user_id);
}

if ( $instructor ) {
    $lstmt = pdoQueryDie($pdo,
        "SELECT DISTINCT L.title as title, L.link_id AS link_id 
        FROM {$p}lti_link AS L JOIN {$p}lti_result as R 
            ON L.link_id = R.link_id AND R.grade IS NOT NULL
        WHERE L.context_id = :CID",
        array(":CID" => $LTI['context_id'])
    );
    $links = $lstmt->fetchAll();    
}
// View 
headerContent();
startBody();
flashMessages();

if ( $instructor ) {
?>
  <a href="index.php?viewall=yes" class="btn btn-default">Class Summary</a> 
  <a href="index.php" class="btn btn-default">My Grades</a> 
<?php
if ( $links !== false && count($links) > 0 ) {
?>
  <div class="btn-group">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
      Activities
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
<?php
    foreach($links as $link) {
        echo('<li><a href="#" onclick="window.location=\'');
        echo(sessionize('index.php?link_id='.$link['link_id']).'\';">');
        echo(htmlent_utf8($link['title']));
        echo("</a></li>\n");
    }
?>
    </ul>
  </div>
<?php } ?>
<p></p>
<?php
}

echo("<p>Class: ".$LTI['context_title']."</p>\n");

if ( $user_sql !== false ) {
    if ( $user_info !== false ) {
        echo("<p>Results for ".$user_info['displayname']."</p>\n");
    }
    pagedPDO($pdo, $user_sql, $query_parms, $searchfields);
}

if ( $summary_sql !== false ) {
    pagedPDO($pdo, $summary_sql, $query_parms, $searchfields, $orderfields);
}

if ( $class_sql !== false ) {
    if ( $link_info !== false ) {
        echo("<p>Results for ".$link_info['title']);
        echo(' (<a href="maint.php?link_id='.$link_id.'" target="_new">Maintenance 
            tasks</a>)'."</p>\n");
    }
    pagedPDO($pdo, $class_sql, $query_parms, $searchfields);
}


footerContent();
