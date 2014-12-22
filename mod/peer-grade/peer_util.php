<?php

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// Loads the assignment associated with this link
function loadAssignment($LTI)
{
    global $CFG, $PDOX;
    $cacheloc = 'peer_assn';
    $row = Cache::check($cacheloc, $LTI['link_id']);
    if ( $row != false ) return $row;
    $stmt = $PDOX->queryDie(
        "SELECT assn_id, json FROM {$CFG->dbprefix}peer_assn WHERE link_id = :ID",
        array(":ID" => $LTI['link_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $row['json'] = upgradeSubmission($row['json'] );
    Cache::set($cacheloc, $LTI['link_id'], $row);
    return $row;
}

function loadSubmission($assn_id, $user_id)
{
    global $CFG, $PDOX;
    $cacheloc = 'peer_submit';
    $cachekey = $assn_id + "::" + $user_id;
    $submit_row = Cache::check($cacheloc, $cachekey);
    if ( $submit_row != false ) return $submit_row;
    $submit_row = false;

    $stmt = $PDOX->queryDie(
        "SELECT submit_id, json, note, reflect, inst_points, inst_note, inst_id, updated_at
            FROM {$CFG->dbprefix}peer_submit AS S
            WHERE assn_id = :AID AND S.user_id = :UID",
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $submit_row = $stmt->fetch(PDO::FETCH_ASSOC);
    Cache::set($cacheloc, $cachekey, $submit_row);
    return $submit_row;
}

// Upgrade a submission to cope with name changes
function upgradeSubmission($json_str)
{
    if ( strlen(trim($json_str)) < 1 ) return $json_str;
    $json = json_decode($json_str);
    if ( $json === null ) return $json_str;

    // Add instructorpoints if they are not there
    if ( ! isset($json->instructorpoints) ) $json->instructorpoints = 0;

    // Convert maxpoints to peerpoints
    if ( ( ! isset($json->peerpoints) ) && isset($json->maxpoints) ) $json->peerpoints = $json->maxpoints;
    unset($json->maxpoints);

    // Allow for things to be optional
    if ( ! isset($json->totalpoints) ) $json->totalpoints = 0; // Probably an error
    if ( ! isset($json->assesspoints) ) $json->assesspoints = 0;
    if ( ! isset($json->maxassess) ) $json->maxassess = 0;
    if ( ! isset($json->minassess) ) $json->minassess = 0;
    if ( ! isset($json->peerpoints) ) $json->peerpoints = 0;
    return json_encode($json);
}

// Check for ungraded submissions
function loadUngraded($LTI, $assn_id)
{
    global $CFG, $PDOX;
    $stmt = $PDOX->queryDie(
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

function showSubmission($LTI, $assn_json, $submit_json, $assn_id, $user_id)
{
    global $CFG, $PDOX;
    echo('<div style="padding:5px">');
    $blob_ids = $submit_json->blob_ids;
    $urls = isset($submit_json->urls) ? $submit_json->urls : array();
    $codes = isset($submit_json->codes) ? $submit_json->codes : array();
    $blobno = 0;
    $urlno = 0;
    $codeno = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == "image" ) {
            // This test triggers when an assignment is reconfigured
            // and old submissions have too few blobs
            if ( $blobno >= count($blob_ids) ) continue;
            $blob_id = $blob_ids[$blobno++];
            if ( is_array($blob_id) ) $blob_id = $blob_id[0];
            $url = getAccessUrlForBlob($blob_id);
            $title = 'Student image';
            if( isset($part->title) && strlen($part->title) > 0 ) $title = $part->title;
            echo (' <a href="#" onclick="$(\'#myModal_'.$blobno.'\').modal();"');
            echo ('alt="'.htmlent_utf8($title).'" title="'.htmlent_utf8($title).'">');
            echo ('<img src="'.addSession($url).'" width="240"></a>'."\n");
?>
<div class="modal fade" id="myModal_<?php echo($blobno); ?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo(htmlent_utf8($title)); ?></h4>
      </div>
      <div class="modal-body">
        <img src="<?php echo(addSession($url)); ?>" style="width:100%">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
        } else if ( $part->type == "url" ) {
            $url = $urls[$urlno++];
            echo ('<p><a href="'.safe_href($url).'" target="_blank">');
            echo (htmlentities(safe_href($url)).'</a> (Will launch in new window)</p>'."\n");
        } else if ( $part->type == "code" ) {
            $code_id = $codes[$codeno++];
            $row = $PDOX->rowDie("
                SELECT data FROM {$CFG->dbprefix}peer_text 
                WHERE text_id = :TID AND user_id = :UID AND assn_id = :AID",
                array( ":TID" => $code_id,
                    ":AID" => $assn_id,
                    ":UID" => $user_id)
            );
            if ( $row === FALSE || strlen($row['data']) < 1 ) {
                echo("<p>No Code Found</p>\n");
            } else {
                echo ('<p>Code: <a href="#" onclick="$(\'#myModal_code_'.$codeno.'\').modal();">');
                echo(htmlent_utf8($part->title)."</a> (click to view)</p>\n");
?>
<div class="modal fade" id="myModal_code_<?php echo($codeno); ?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo(htmlent_utf8($part->title)); ?></h4>
      </div>
      <div class="modal-body">
         <pre>
         <code class="language-<?php echo($part->language); ?>">
<?php echo (htmlentities($row['data'])); ?>
         </code>
         </pre>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
            }
        }

    }
    echo("<br/>&nbsp;<br/>\n");

    if ( $blobno > 0 ) {
        echo("<p>Click on each image to see a larger view of the image.</p>\n");
    }

    if ( strlen($submit_json->notes) > 1 ) {
        echo("<p>Notes: ".htmlent_utf8($submit_json->notes)."</p>\n");
    }
    echo('<div style="padding:3px">');
}

function computeGrade($assn_id, $assn_json, $user_id)
{
    global $CFG, $PDOX;
    $stmt = $PDOX->queryDie(
        "SELECT S.assn_id, S.user_id AS user_id, inst_points, email, displayname, S.submit_id as submit_id,
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
    $inst_points = $row['inst_points'] + 0;
    $assnpoints = $row['max_points']+0;
    if ( $assnpoints < 0 ) $assnpoints = 0;
    if ( $assnpoints > $assn_json->peerpoints ) $assnpoints = $assn_json->peerpoints;

    $gradecount = $row['grade_count']+0;
    if ( $gradecount < 0 ) $gradecount = 0;
    if ( $gradecount > $assn_json->minassess ) $gradecount = $assn_json->minassess;
    $gradepoints = $gradecount * $assn_json->assesspoints;
    return ($inst_points + $assnpoints + $gradepoints) / $assn_json->totalpoints;
}

// Load the count of grades for this user for an assignment
function loadMyGradeCount($LTI, $assn_id) {
    global $CFG, $PDOX;
    $cacheloc = 'peer_grade';
    $cachekey = $assn_id + "::" + $LTI['user_id'];
    $grade_count = Cache::check($cacheloc, $cachekey);
    if ( $grade_count != false ) return $grade_count;
    $stmt = $PDOX->queryDie(
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
    Cache::set($cacheloc, $cachekey, $grade_count);
    return $grade_count;
}

// Retrieve grades for a submission
// Not cached because another user may have added a grade
// a moment ago
function retrieveSubmissionGrades($submit_id)
{
    global $CFG, $PDOX;
    if ( $submit_id === false ) return false;
    $grades_received = $PDOX->allRowsDie(
        "SELECT grade_id, points, note, displayname, email
        FROM {$CFG->dbprefix}peer_grade AS G
        JOIN {$CFG->dbprefix}lti_user as U
            ON G.user_id = U.user_id
        WHERE G.submit_id = :SID
        ORDER BY points DESC",
        array( ':SID' => $submit_id)
    );
    return $grades_received;
}

function retrieveGradesGiven($assn_id, $user_id)
{
    global $CFG, $PDOX;
    $grades_given = $PDOX->allRowsDie(
        "SELECT grade_id, points, G.note AS note, displayname, email
        FROM {$CFG->dbprefix}peer_grade AS G
        JOIN {$CFG->dbprefix}peer_submit AS S
            ON G.submit_id = S.submit_id
        JOIN {$CFG->dbprefix}lti_user as U
            ON S.user_id = U.user_id
        WHERE G.user_id = :UID AND S.assn_id = :AID",
        array( ':AID' => $assn_id, ':UID' => $user_id)
    );
    return $grades_given;
}

function mailDeleteSubmit($user_id, $assn_json, $note)
{
    global $CFG, $PDOX;
    if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) return false;

    $LTI = LTIX::requireData();

    $user_row = loadUserInfoBypass($user_id);
    if ( $user_row === false ) return false;
    $to = $user_row['email'];
    if ( strlen($to) < 1 || strpos($to,'@') === false ) return false;

    $name = $user_row['displayname'];
    $token = computeMailCheck($user_id);
    $subject = 'From '.$CFG->servicename.', Your Peer Graded Entry Has Been Reset';
    $E = "\n";
    if ( isset($CFG->maileol) ) $E = $CFG->maileol;

    $message = "This is an automated message.  Your peer-graded entry has been reset.$E$E";
    if ( isset($LTI['context_title']) ) $message .= 'Course Title: '.$LTI['context_title'].$E;
    if ( isset($LTI['link_title']) ) $message .= 'Assignment: '.$LTI['link_title'].$E;
    if ( isset($LTI['user_displayname']) ) $message .= 'Staff member doing reset: '.$LTI['user_displayname'].$E;

    $fixnote = trim($note);
    if ( strlen($fixnote) > 0 ) {
        if ( $E != "\n" ) $fixnote = str_replace("\n",$E,$fixnote);
        $message .= "Notes regarding this action:".$E.$fixnote.$E;
    }
    $message .= "{$E}You may now re-submit your peer-graded assignment.$E";

    $stmt = $PDOX->queryDie(
        "INSERT INTO {$CFG->dbprefix}mail_sent
            (context_id, link_id, user_to, user_from, subject, body, created_at)
            VALUES ( :CID, :LID, :UTO, :UFR, :SUB, :BOD, NOW() )",
        array( ":CID" => $LTI['context_id'], ":LID" => $LTI['link_id'],
            ":UTO" => $user_id, ":UFR" => $LTI['user_id'],
            ":SUB" => $subject, ":BOD" => $message)
    );

    // echo $to, $subject, $message, $user_id, $token;
    $retval = mailSend($to, $subject, $message, $user_id, $token);
    return $retval;
}

function getDefaultJson() 
{
    $json = '{ "title" : "Assignment title",
        "description" : "This is a sample assignment configuration showing the various kinds of items you can ask for in the assignment.  Change this text to describe what you want to be turned in and perhaps something about the grading scale like - This assignment is worth 10 points. 6 points come from your peers and 4 points come from you grading other student\'s submissions.",
        "grading" : "This is a relatively simple assignment.  Don\'t take off points for little mistakes.  If they seem to have done the assignment give them full credit.   Feel free to make suggestions if there are small mistakes.  Please keep your comments positive and useful.  If you do not take grading seriously, the instructors may delete your response and you will lose points.",
        "parts" : [
            { "title" : "URL of your home page",
              "type" : "url"
            },
            { "title" : "Source code of index.php with your name",
              "type" : "code",
              "language" : "php"
            },
            { "title" : "Some HTML using the bold tag",
              "type" : "code",
              "language" : "markup"
            },
            { "title" : "Image (JPG or PNG) of your home page (Maximium 1MB per file)",
              "type" : "image"
            }
        ],
        "totalpoints" : 10,
        "instructorpoints" : 0,
        "peerpoints" : 6,
        "assesspoints" : 2,
        "minassess" : 2,
        "maxassess" : 5
    }';
    $json = json_decode($json);
    if ( $json === null ) die("Bad JSON constant");
    $json = json_encode($json);
    // $json = jsonIndent($json);
    return $json;
}
