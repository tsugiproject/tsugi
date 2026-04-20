<?php

use \Tsugi\Core\Cache;
use \Tsugi\UI\Output;
use \Tsugi\Util\LTI;
use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\User;
use \Tsugi\Core\Mail;
use \Tsugi\Blob\BlobUtil;
use \Tsugi\UI\Lessons;

// Loads the assignment associated with this link
function loadAssignment()
{
    global $CFG, $PDOX, $LINK;
    $cacheloc = 'peer_assn';
    $row = Cache::check($cacheloc, $LINK->id);
    if ( $row != false && $row['json'] != 'null' ) return $row;
    $stmt = $PDOX->queryDie(
        "SELECT assn_id, json FROM {$CFG->dbprefix}peer_assn WHERE link_id = :ID",
        array(":ID" => $LINK->id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $custom = LTIX::ltiCustomGet('config');
    // Check custom for errors and make it pretty
    if ( strlen($custom) > 1 ) {
        $decode = json_decode($custom);
        if ( $decode === null ) {
            error_log('Bad custom_config\n'.$custom);
            $custom = null;
        } else {
            $pretty = json_encode($decode,JSON_PRETTY_PRINT);
            $custom = $pretty;
        }
    }

    if ( ( ! $custom || U::isEmpty($custom) ) && isset($_GET["inherit"]) && isset($CFG->lessons) ) {
        $l = new Lessons($CFG->lessons);
        if ( $l ) {
            $lti = $l->getLtiByRlid($_GET['inherit']);
            if ( isset($lti->custom) ) foreach($lti->custom as $c ) {
                if (isset($c->key) && isset($c->json) && $c->key == 'config' ) {
                    $custom = json_encode($c->json, JSON_PRETTY_PRINT);
                }
            }
        }
    }
    if ( $row === false && strlen($custom) > 1 ) {
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}peer_assn
                (link_id, json, created_at, updated_at)
                VALUES ( :ID, :JSON, NOW(), NOW())
                ON DUPLICATE KEY UPDATE json = :JSON, updated_at = NOW()",
            array(
                ':JSON' => $custom,
                ':ID' => $LINK->id)
            );
        Cache::clear("peer_assn");
        $stmt = $PDOX->queryDie(
            "SELECT assn_id, json FROM {$CFG->dbprefix}peer_assn WHERE link_id = :ID",
            array(":ID" => $LINK->id)
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ( ! $row ) return $row;

    $row['json'] = upgradeSubmission($row['json'] );
    Cache::set($cacheloc, $LINK->id, $row);
    return $row;
}

function loadSubmission($assn_id, $user_id)
{
    global $CFG, $PDOX;
    $cacheloc = 'peer_submit';
    $cachekey = $assn_id . "::" . $user_id;
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
    global $CFG;
    if ( U::isEmpty(trim($json_str)) ) return $json_str;
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
    if ( ! isset($json->flag) ) $json->flag = true;
    if ( ! isset($json->rating) ) $json->rating = 0;
    if ( ! isset($json->gallery) ) $json->gallery = "off";
    if ( ! isset($json->galleryformat) ) $json->galleryformat = "card";
    if ( ! isset($json->resubmit) ) $json->resubmit = "off";
    if ( ! isset($json->autopeer) ) $json->autopeer = 0;
    if ( $json->autopeer === false ) $json->autopeer = 0;
    if ( ! isset($json->notepublic) ) $json->notepublic = "false";
    if ( ! isset($json->image_size) ) $json->image_size = 1;
    if ( ! isset($json->pdf_size) ) $json->pdf_size = 0; // Max

    // Fix urls
    if ( isset($json->assignment) && is_string($json->assignment) && ! U::isEmpty($json->assignment) ) {
        $json->assignment = str_replace('{apphome}', $CFG->apphome, $json->assignment);
    }
    return json_encode($json);
}

// Check for ungraded submissions
function loadUngraded($assn_id)
{
    global $CFG, $PDOX, $USER;
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
        array(":AID" => $assn_id, ":UID" => $USER->id)
    );
    return $stmt->fetchAll();
}

function showSubmission($assn_json, $submit_json, $assn_id, $user_id)
{
    global $CFG, $PDOX, $USER, $LINK, $CONTEXT, $OUTPUT;
    echo('<div style="padding:5px">');
    $blob_ids = isset($submit_json->blob_ids) ? $submit_json->blob_ids : array();
    $urls = isset($submit_json->urls) ? $submit_json->urls : array();
    $codes = isset($submit_json->codes) ? $submit_json->codes : array();
    $htmls = isset($submit_json->htmls) ? $submit_json->htmls : array();

    $content_items = isset($submit_json->content_items) ? $submit_json->content_items : array();
    $blobno = 0;
    $urlno = 0;
    $codeno = 0;
    $htmlno = 0;
    $content_item_no = 0;
    foreach ( $assn_json->parts as $part ) {
        if ( $part->type == "image" ) {
            // This test triggers when an assignment is reconfigured
            // and old submissions have too few blobs
            if ( $blobno >= count($blob_ids) ) continue;
            $blob_id = $blob_ids[$blobno++];
            if ( is_array($blob_id) ) $blob_id = $blob_id[0];
            $url = BlobUtil::getAccessUrlForBlob($blob_id);
            $title = 'Student image';
            if( isset($part->title) && U::isNotEmpty($part->title) ) $title = $part->title;
            echo (' <a href="#myModal_'.$blob_id.'" onclick="$(\'#myModal_'.$blob_id.'\').modal(); return false;" aria-label="'.htmlentities($title).' - View larger" title="'.htmlentities($title).'">');
            echo ('<img src="'.addSession($url).'" width="240" style="max-width: 100%" alt="'.htmlentities($title).'"></a>'."\n");
?>
<div class="modal fade" id="myModal_<?php echo($blob_id); ?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo(htmlentities($title)); ?></h4>
      </div>
      <div class="modal-body">
        <img src="<?php echo(addSession($url)); ?>" style="width:100%" alt="<?php echo(htmlentities($title)); ?>">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
        } else if ( $part->type == "pdf" ) {
            $blob_id = $blob_ids[$blobno++];
            if ( is_array($blob_id) ) $blob_id = $blob_id[0];
            $url = BlobUtil::getAccessUrlForBlob($blob_id);
            $title = 'Student PDF';
            if( isset($part->title) && U::isNotEmpty($part->title) ) $title = $part->title;
            // Need session because target="_blank"
            echo ('<p><a href="'.addSession(safe_href($url)).'" target="_blank" rel="noopener noreferrer" aria-label="'.htmlentities($title).' - Opens in new window">');
            echo (htmlentities($title).'</a> (Will launch in new window)</p>'."\n");
        } else if ( $part->type == "url" && $urlno < count($urls) ) {
            $url = $urls[$urlno++];
            echo ('<p><a href="'.safe_href($url).'" target="_blank" rel="noopener noreferrer" aria-label="Open URL in new window">');
            echo (htmlentities(safe_href($url)).'</a> (Will launch in new window)</p>'."\n");
        } else if ( $part->type == "content_item" && $content_item_no < count($content_items) ) {
            $content_item = $content_items[$content_item_no++];

            $endpoint = $content_item->url;
            $info = LTIX::getKeySecretForLaunch($endpoint);
            if ( $info === false ) {
                echo('<p style="color:red">Unable to load key/secret for '.htmlentities($endpoint)."</p>\n");
                $content_item_no++;
                continue;
            }

            $lu1 = LTIX::getLaunchUrl($endpoint, true);
            $lu1 = addSession($lu1);

             echo('<br/><button type="button" onclick="
                $(\'#content_item_frame_'.$content_item_no.'\').attr(\'src\', \''.$lu1.'\');
                showModalIframe(\''.$part->title.'\',
                \'content_item_dialog_'.$content_item_no.'\',\'content_item_frame_'.$content_item_no.'\',
                \''.$OUTPUT->getSpinnerUrl().'\');
                return false;">View Media</button>'."\n");
?>
<div id="content_item_dialog_<?= $content_item_no ?>" title="Content Item Dialog" style="display:none;">
<iframe src="about:blank" id="content_item_frame_<?= $content_item_no ?>"
    style="width:95%; height:500px;"
    scrolling="auto" frameborder="1" transparency></iframe>
</div>
<?php
            $content_item_no++;
        } else if ( $part->type == "html" && $htmlno < count($htmls) ) {
            $html_id = $htmls[$htmlno++];
            $row = $PDOX->rowDie("
                SELECT data FROM {$CFG->dbprefix}peer_text
                WHERE text_id = :TID AND user_id = :UID AND assn_id = :AID",
                array( ":TID" => $html_id,
                    ":AID" => $assn_id,
                    ":UID" => $user_id)
            );
            $json_url = addSession("load_html.php?html_id=$html_id&user_id=$user_id");
            if ( $row === FALSE || U::isEmpty($row['data']) ) {
                echo("<p>No HTML Found</p>\n");
            } else {
                echo ('<p>HTML: <a href="#myModal_html_'.$htmlno.'" onclick="$(\'#myModal_html_'.$htmlno.'\').modal(); return false;" aria-label="View '.htmlentities($part->title).' in larger view">');
                echo(htmlentities($part->title)."</a> (click to view)</p>\n");
?>
<div class="modal fade" id="myModal_html_<?php echo($htmlno); ?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo(htmlentities($part->title)); ?></h4>
      </div>
      <div class="modal-body" id="html_content_<?php echo($htmlno); ?>">
            <img src="<?= $OUTPUT->getSpinnerUrl() ?>"/>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
html_loads.push(['html_content_<?php echo($htmlno); ?>', '<?= $json_url ?>']);
</script>
<?php
            }
        } else if ( $part->type == "code" && $codeno < count($codes) ) {
            $code_id = $codes[$codeno++];
            $row = $PDOX->rowDie("
                SELECT data FROM {$CFG->dbprefix}peer_text
                WHERE text_id = :TID AND user_id = :UID AND assn_id = :AID",
                array( ":TID" => $code_id,
                    ":AID" => $assn_id,
                    ":UID" => $user_id)
            );
            if ( $row === FALSE || U::isEmpty($row['data']) ) {
                echo("<p>No Code Found</p>\n");
            } else {
                echo ('<p>Code: <a href="#myModal_code_'.$codeno.'" onclick="$(\'#myModal_code_'.$codeno.'\').modal(); return false;" aria-label="View '.htmlentities($part->title).' in larger view">');
                echo(htmlentities($part->title)."</a> (click to view)</p>\n");
?>
<div class="modal fade" id="myModal_code_<?php echo($codeno); ?>">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo(htmlentities($part->title)); ?></h4>
      </div>
      <div class="modal-body">
<!-- Don't indent or inadvertently add a newline once the pre starts -->
<pre class="line-numbers"><code
<?php if ( isset($part->language) ) { ?>
class="language-<?php echo($part->language); ?>"
<?php } ?>
><?php echo (htmlentities($row['data'] ?? '')); ?>
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
        echo("<p>Click on each image/pdf to see a larger view of the image.</p>\n");
    }

    if ( isset($submit_json->notes) && is_string($submit_json->notes) && strlen($submit_json->notes) > 1 ) {
        echo("<p>Notes: ".htmlentities($submit_json->notes)."</p>\n");
    }
    echo('<div style="padding:3px">');
}

function load_htmls() {
    global $CFG;
?>
<script src="<?= $CFG->staticroot ?>/js/HtmlSanitizer.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    for(i=0; i<html_loads.length; i++) {
        var divname = html_loads[i][0];
        $.get(html_loads[i][1], function(data) {
            var html = HtmlSanitizer.SanitizeHtml(data);
            $('#'+divname).html(html);
        })
    }
});
</script>
<?php
}

function computeGrade($assn_id, $assn_json, $user_id)
{
    global $CFG, $PDOX;

    // $submit_row = loadSubmission($assn_id, $USER->id);

    if ( $assn_json->totalpoints == 0 ) return 0;
    $sql = "SELECT S.assn_id, S.json AS json, S.user_id AS user_id,
             inst_points, email, displayname,
             S.submit_id as submit_id, S.created_at AS created_at,
            MAX(points) as max_points, COUNT(points) as count_points
        FROM {$CFG->dbprefix}peer_submit as S
        JOIN {$CFG->dbprefix}peer_grade AS G
            ON S.submit_id = G.submit_id
        JOIN {$CFG->dbprefix}lti_user AS U
            ON S.user_id = U.user_id
        WHERE S.assn_id = :AID AND S.user_id = :UID";

    $stmt = $PDOX->queryDie($sql,
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ( $row === false || $row['user_id']+0 == 0 ) return -1;

    $displayname = $row['displayname'];

    $submit_json_str = $row['json'];
    $submit_json = false;
    if ( U::isNotEmpty($submit_json_str) ) {
        $submit_json = json_decode($submit_json_str);
    }

    // Compute the overall points
    $inst_points = $row['inst_points'] + 0;
    $assnpoints = $row['max_points']+0;

    // Handle when the student has waited "long enough" for a peer-grade
    $created_at = strtotime($row['created_at']." UTC");
    $diff = time() - $created_at;
    if ( isset($assn_json->autopeer) && $assn_json->autopeer > 0 &&
        $diff > $assn_json->autopeer && $assnpoints < $assn_json->peerpoints) {
	// TODO: Turn this into an event
        error_log('Auto-peer '.time().' '.$diff.' '.$row['displayname']);
        $assnpoints = $assn_json->peerpoints;
    }

    if ( $assnpoints < 0 ) $assnpoints = 0;
    if ( $assnpoints > $assn_json->peerpoints ) $assnpoints = $assn_json->peerpoints;

    $peer_marks = retrievePeerMarks($assn_id, $user_id);

    $sql = "SELECT count(G.user_id) as grade_count
        FROM {$CFG->dbprefix}peer_submit as S
        JOIN {$CFG->dbprefix}peer_grade AS G
            ON S.submit_id = G.submit_id
        WHERE S.assn_id = :AID AND G.user_id = :UID";

    $stmt = $PDOX->queryDie($sql,
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $gradecount = 0;
    if ( $row ) $gradecount = $row['grade_count']+0;
    if ( $peer_marks > $gradecount ) $gradecount = $peer_marks;
    if ( $gradecount < 0 ) $gradecount = 0;
    if ( $gradecount > $assn_json->minassess ) $gradecount = $assn_json->minassess;
    $gradepoints = $gradecount * $assn_json->assesspoints;

    // Handle if student is exempt from peer grading
    if ( $submit_json && isset($submit_json->peer_exempt) ) {
        $gradepoints = $assn_json->minassess * $assn_json->assesspoints;
        error_log('Accessible override '.time().' '.$displayname.' points='.$gradepoints);
    }

    $retval = ($inst_points + $assnpoints + $gradepoints) / $assn_json->totalpoints;
    if ( $retval > 1.0 ) $retval = 1.0;
    return $retval;
}

// Load the count of grades for this user for an assignment
function loadMyGradeCount($assn_id) {
    global $CFG, $PDOX, $USER;
    $cacheloc = 'peer_grade';
    $cachekey = $assn_id . "::" . $USER->id;
    $grade_count = Cache::check($cacheloc, $cachekey);
    if ( $grade_count != false ) return $grade_count;

    $peer_marks = retrievePeerMarks($assn_id, $USER->id);

    $stmt = $PDOX->queryDie(
        "SELECT COUNT(grade_id) AS grade_count
        FROM {$CFG->dbprefix}peer_submit AS S
        JOIN {$CFG->dbprefix}peer_grade AS G
        ON S.submit_id = G.submit_id
            WHERE S.assn_id = :AID AND G.user_id = :UID",
        array( ':AID' => $assn_id, ':UID' => $USER->id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ( $row !== false ) {
        $grade_count = U::get($row, 'grade_count', '0') + 0;
    } else {
        $grade_count = $peer_marks;
    }
    if ( $peer_marks > $grade_count ) $grade_count = $peer_marks;
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
        "SELECT grade_id, points, note, displayname, email, rating
        FROM {$CFG->dbprefix}peer_grade AS G
        JOIN {$CFG->dbprefix}lti_user as U
            ON G.user_id = U.user_id
        WHERE G.submit_id = :SID
        ORDER BY points DESC",
        array( ':SID' => $submit_id)
    );
    return $grades_received;
}

// Check the peer_marks in case entries have been deleted
function retrievePeerMarks($assn_id, $user_id) {
    global $CFG, $PDOX;
    $sql = "SELECT peer_marks FROM {$CFG->dbprefix}peer_submit
        WHERE assn_id = :AID AND user_id = :UID";

    // TODO: Make queryDie after we are sure the model is upgraded
    $stmt = $PDOX->queryReturnError($sql,
    // $stmt = $PDOX->queryDie($sql,
        array(":AID" => $assn_id, ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $peer_marks = U::get($row, 'peer_marks', '0') + 0;
    return $peer_marks;
}

function retrieveGradesGiven($assn_id, $user_id)
{
    global $CFG, $PDOX;
    $grades_given = $PDOX->allRowsDie(
        "SELECT grade_id, points, G.note AS note, displayname, email, G.rating AS rating
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
    global $CFG, $PDOX, $CONTEXT, $LINK, $USER;
    if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) return false;

    $LAUNCH = LTIX::requireData();

    $user_row = User::loadUserInfoBypass($user_id);
    if ( $user_row === false ) return false;
    $to = $user_row['email'];
    if ( U::isEmpty($to) || strpos($to,'@') === false ) return false;

    $name = $user_row['displayname'];
    $token = Mail::computeCheck($user_id);
    $subject = 'From '.$CFG->servicename.', Your Peer Graded Entry Has Been Reset';
    $E = "\n";
    if ( isset($CFG->maileol) ) $E = $CFG->maileol;

    $message = "This is an automated message.  Your peer-graded entry has been reset.$E$E";
    if ( isset($CONTEXT->title) ) $message .= 'Course Title: '.$CONTEXT->title.$E;
    if ( isset($LINK->title) ) $message .= 'Assignment: '.$LINK->title.$E;
    // if ( isset($USER->displayname) ) $message .= 'Staff member doing reset: '.$USER->displayname.$E;

    $fixnote = trim($note);
    if ( U::isNotEmpty($fixnote) ) {
        if ( $E != "\n" ) $fixnote = str_replace("\n",$E,$fixnote);
        $message .= "Notes regarding this action:".$E.$fixnote.$E;
    }
    $message .= "{$E}You may now re-submit your peer-graded assignment.$E";

    $stmt = $PDOX->queryDie(
        "INSERT INTO {$CFG->dbprefix}mail_sent
            (context_id, link_id, user_to, user_from, subject, body, created_at)
            VALUES ( :CID, :LID, :UTO, :UFR, :SUB, :BOD, NOW() )",
        array( ":CID" => $CONTEXT->id, ":LID" => $LINK->id,
            ":UTO" => $user_id, ":UFR" => $USER->id,
            ":SUB" => $subject, ":BOD" => $message)
    );

    // echo $to, $subject, $message, $user_id, $token;
    $retval = Mail::send($to, $subject, $message, $user_id, $token);
    return $retval;
}

function getDefaultJson()
{
    $json = '{ "title" : "Assignment title",
        "description" : "This is a sample assignment configuration showing the various kinds of items you can ask for in the assignment.",
        "grading" : "Don\'t take off points for little mistakes.  If they seem to have done the assignment give them full credit.   Feel free to make suggestions if there are small mistakes.  Please keep your comments positive and useful.  If you do not take grading seriously, the instructors may delete your response and you will lose points.",
        "parts" : [
            { "title" : "URL of your home page",
              "type" : "url"
            },
            { "title" : "Some HTML using the CKEditor",
              "type" : "html"
            },
            { "title" : "Source code of index.php with your name",
              "type" : "code",
              "language" : "php"
            },
            { "title" : "Image (JPG or PNG) of your home page",
              "type" : "image"
            }
        ],
        "gallery" : "off",
        "galleryformat" : "card",
        "totalpoints" : 10,
        "instructorpoints" : 0,
        "peerpoints" : 6,
        "rating" : 0,
        "assesspoints" : 2,
        "minassess" : 2,
        "maxassess" : 5,
        "flag" : true
    }';
    $json = json_decode($json);
    if ( $json === null ) die("Bad JSON constant");
    $json = json_encode($json);
    // $json = \Tsugi\Util\LTI::jsonIndent($json);
    return $json;
}

function pointsDetail($assn_json) {
    $r = "The total number of points for this assignment is $assn_json->totalpoints.\n";
    if ( isset($assn_json->instructorpoints) && $assn_json->instructorpoints > 0 ) {
        $r .= "You will get up to $assn_json->instructorpoints points from your instructor.\n";
    }
    if ( isset($assn_json->peerpoints) && $assn_json->peerpoints > 0 ) {
        $r .= "Your peers will give you a grade from 0 - $assn_json->peerpoints".".\n";
    }
    if ( isset($assn_json->assesspoints) && $assn_json->assesspoints > 0 ) {
        $r .= "You will get $assn_json->assesspoints for each peer assignment you assess.\n";
    }
    if ( isset($assn_json->minassess) && $assn_json->minassess > 0 ) {
        $r .= "You need to grade a minimum of $assn_json->minassess peer assignments.\n";
    }
    if ( isset($assn_json->maxassess) && $assn_json->maxassess >  $assn_json->minassess) {
        $r .= "You can grade up to $assn_json->maxassess peer assignments if you like but grading extra assignment will not increase your score.\n";
    }
    return $r;
}


function deleteSubmission($assn_row, $submit_row) {
    global $PDOX, $USER, $CFG;

    $p = $CFG->dbprefix;
    $assn_id = $assn_row['assn_id'];
    $submit_id = $submit_row['submit_id'];

    $json = isset($submit_row['json']) ? $submit_row['json'] : false;
    if ( $json && U::isNotEmpty($json) ) $json = json_decode($json);

    if ( $json ) $blob_ids = isset($json->blob_ids) ? $json->blob_ids : false;
    if ( is_array($blob_ids) ) {
        foreach($blob_ids as $blob_id ) {
            echo("Delete blob $blob_id \n");
            BlobUtil::deleteBlob($blob_id);
        }
    }

    if ( $json ) $pdf_ids = isset($json->pdf_ids) ? $json->pdf_ids : false;
    if ( is_array($pdf_ids) ) {
        foreach($pdf_ids as $blob_id ) {
            echo("Delete blob $blob_id \n");
            BlobUtil::deleteBlob($blob_id);
        }
    }

    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_submit
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );

    // Since text items are connected to the assignment not submission
    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_text
            WHERE assn_id = :AID AND user_id = :UID",
        array( ':AID' => $assn_id, ':UID' => $USER->id)
    );

    Cache::clear('peer_grade');
    Cache::clear('peer_submit');
}

/**
 * Send a notification to a student when their grade is changed
 * 
 * @param int $user_id The user ID of the student being graded
 * @param float $grade The new grade (0.0 to 1.0)
 * @param float|null $old_grade Optional old grade to compare (only notify if changed)
 * @param string|null $assignment_title Optional assignment title for the notification
 * @param string|null $url Optional URL to link to
 * @return bool True if notification was sent (or not needed), false on error
 */
function notifyGradeChange($user_id, $grade, $old_grade = null, $assignment_title = null, $url = null)
{
    global $LINK;
    
    // Check if NotificationsService class exists before using it
    // class_exists() will trigger Composer autoloader if available
    try {
        if (!class_exists('\Tsugi\Util\NotificationsService', true)) {
            // Notification feature not available, silently skip
            error_log("Peer-grade notification skipped: NotificationsService class not available (user_id=$user_id, grade=$grade, url=" . ($url ?? 'null') . ")");
            return true;
        }
    } catch (\Exception $e) {
        // If autoloader throws an exception, assume class doesn't exist
        error_log("Peer-grade notification skipped: Exception checking NotificationsService class (user_id=$user_id, grade=$grade, url=" . ($url ?? 'null') . "): " . $e->getMessage());
        return true;
    }
    
    // If old_grade is provided, only notify if the grade actually changed
    if ($old_grade !== null && abs($grade - $old_grade) < 0.0001) {
        // Grade hasn't changed, no need to notify
        error_log("Peer-grade notification skipped: Grade unchanged (user_id=$user_id, old_grade=$old_grade, new_grade=$grade, url=" . ($url ?? 'null') . ")");
        return true;
    }
    
    try {
        $title = 'Your peer-grade assignment grade has been updated';
        if ($assignment_title) {
            $title = 'Grade updated: ' . $assignment_title;
        }
        
        $grade_percent = round($grade * 100, 1);
        $text = "Your grade has been updated to {$grade_percent}%." . ($old_grade !== null ? " (Previously " . round($old_grade * 100, 1) . "%)" : "");
        
        // Generate dedupe_key based on assignment and user
        $link_id = (isset($LINK) && is_object($LINK) && isset($LINK->id)) ? $LINK->id : 'unknown';
        $dedupe_key = \Tsugi\Util\NotificationsService::generateDedupeKey('peer-grade', $user_id, $link_id);
        
        $result = \Tsugi\Util\NotificationsService::create($user_id, $title, $text, $url, null, $dedupe_key);
        if ($result !== false) {
            error_log("Peer-grade notification sent successfully (user_id=$user_id, grade=$grade" . ($old_grade !== null ? ", old_grade=$old_grade" : "") . ", title=" . ($assignment_title ?? 'default') . ", url=" . ($url ?? 'null') . ", dedupe_key=$dedupe_key)");
            return true;
        } else {
            error_log("Peer-grade notification failed: NotificationsService::create returned false (user_id=$user_id, grade=$grade, url=" . ($url ?? 'null') . ")");
            return false;
        }
    } catch (\Exception $e) {
        // Log error but don't fail the grade update
        error_log("Error sending peer-grade notification (user_id=$user_id, grade=$grade, url=" . ($url ?? 'null') . "): " . $e->getMessage());
        return false;
    }
}

