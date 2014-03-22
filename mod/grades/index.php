<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$p = $CFG->dbprefix;

$user_info = false;
$link_info = false;
$links = array();
$user_stmt = false;
$class_stmt = false;
$result_stmt = false;

if ( $instructor && isset($_GET['viewall'] ) ) {
    $result_stmt = pdoQueryDie($pdo,
        "SELECT R.user_id AS user_id, displayname, email, COUNT(grade) AS grade_count
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        JOIN {$p}lti_user as U
            ON R.user_id = U.user_id
        WHERE L.context_id = :CID AND R.grade IS NOT NULL
        GROUP BY R.user_id
        ORDER BY email ASC",
        array(":CID" => $LTI['context_id'])
    );

} else if ( $instructor && isset($_GET['link_id'] ) ) {
    $link_id = $_GET['link_id']+0;
    $class_stmt = pdoQueryDie($pdo,
        "SELECT R.user_id AS user_id, displayname, grade, R.updated_at as updated_at
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        JOIN {$p}lti_user as U
            ON R.user_id = U.user_id
        WHERE R.link_id = :LID AND L.context_id = :CID AND R.grade IS NOT NULL",
        array(":LID" => $link_id, ":CID" => $LTI['context_id'])
    );
    $link_info = loadLinkInfo($pdo, $link_id);

} else { // Gets grades for the current or specified 
    $user_id = $LTI['user_id'];
    if ( $instructor && isset($_GET['user_id']) ) {
        $user_id = $_GET['user_id'] + 0;
    }

    $user_stmt = pdoQueryDie($pdo,
        "SELECT L.title as title, R.grade AS grade, 
            R.note AS note, R.updated_at as updated_at
        FROM {$p}lti_result AS R JOIN {$p}lti_link as L 
            ON R.link_id = L.link_id
        WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL",
        array(":UID" => $user_id, ":CID" => $LTI['context_id'])
    );
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
  <a href="<?php echo(sessionize('index.php?viewall=yes')); ?>" class="btn btn-default">Class Summary</a> 
  <a href="<?php echo(sessionize('index.php')); ?>" class="btn btn-default">My Grades</a> 
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

if ( $user_stmt !== false ) {
    if ( $user_info !== false ) {
        echo("<p>Results for ".$user_info['displayname']."</p>\n");
    }
    dumpTable($user_stmt);
}

if ( $result_stmt !== false ) {
    dumpTable($result_stmt, 'index.php');
}

if ( $class_stmt !== false ) {
    if ( $link_info !== false ) {
        echo("<p>Results for ".$link_info['title']."</p>\n");
    }
    dumpTable($class_stmt, 'index.php');
}


footerContent();
