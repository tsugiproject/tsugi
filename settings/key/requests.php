<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\Mail;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    $_SESSION['error'] = _m("This service does not accept instructor requests for keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! ( isset($_SESSION['id']) ) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$goodsession = isset($_SESSION['id']) && isset($_SESSION['email']) && isset($_SESSION['displayname']) &&
    strlen($_SESSION['email']) > 0 && strlen($_SESSION['displayname']) > 0 ;

if ( $goodsession && isset($_POST['title']) && isset($_POST['lti']) &&
        isset($_POST['title']) && isset($_POST['notes']) ) {
    if ( strlen($_POST['title']) < 1 ) {
        $_SESSION['error'] = _m("Requests must have titles");
        header("Location: ".LTIX::curPageUrl());
        return;
    }
    if ( strlen($_POST['notes']) < 1 ) {
        $_SESSION['error'] = _m("You must include a reason (i.e. what course you are teaching) in this request.");
        header("Location: ".LTIX::curPageUrl());
        return;
    }
    $version = $_POST['lti']+0;
    if ( $version != 1 && $version != 2 ) {
        $_SESSION['error'] = _m("LTI Version muse be 1 or 2");
        header("Location: ".LTIX::curPageUrlFolder());
        return;
    }
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$CFG->dbprefix}key_request
        (user_id, title, notes, state, lti, created_at, updated_at)
        VALUES ( :UID, :TITLE, :NOTES, 0, :LTI, NOW(), NOW() )",
        array(":UID" => $_SESSION['id'], ":TITLE" => $_POST['title'],
            ":NOTES" => $_POST['notes'], ":LTI" => $version)
    );
    if ( $CFG->owneremail && $CFG->OFFLINE === false) {
        $user_id = $_SESSION['id'];
        $token = Mail::computeCheck($user_id);
        $to = $CFG->owneremail;
        $subject = "Key Request from ".$_SESSION['displayname'].' ('.$_SESSION['email'].' )';
        $message = "Key Request from ".$_SESSION['displayname'].' ('.$_SESSION['email'].' )\n'.
            "\nNotes\n".$_POST['notes']."\n\n".
            "Link: ".$CFG->getCurrentFileUrl(__FILE__)."\n";

        $retval = Mail::send($to, $subject, $message, $user_id, $token);
    }
    $_SESSION['success'] = "Record inserted";
    header("Location: ".LTIX::curPageUrlFolder());
    return;
}

$query_parms = array(":UID" => $_SESSION['id']);
$searchfields = array("request_id", "title", "notes", "state", "admin", "email", "displayname", "R.created_at", "R.updated_at");
$sql = "SELECT request_id, title, notes, state, admin, 
        R.created_at, R.updated_at, email, displayname
        FROM {$CFG->dbprefix}key_request  as R
        JOIN {$CFG->dbprefix}lti_user AS U ON R.user_id = U.user_id
        WHERE R.user_id = :UID";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $state = $row['state'];
    if ( $state == 0 ) {
        $newrow['state'] = "0 (Waiting)";
    } else if ( $state == 1 ) {
        $newrow['state'] = "1 (Approved)";
    } else if ( $state == 2 ) {
        $newrow['state'] = "2 (Not approved)";
    }
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI Key Requests</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">LTI 1.x Keys</a>
  <a href="using" class="btn btn-default">Using Your Key</a>
  <a href="requests" class="btn btn-default active">Key Requests</a>
</p>
<p>
If you are a teacher and want to use the interactive elements on this web
site using IMS Learning Tools Interoperability, you can request a key
from this page.  Please include a description of how you are
planning on using your key.
</p>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<p>
If you are using Google Classroom, there is no need to request a key, simply
connect to Google Classroom and install tools.
</p>
<?php } ?>
<?php if ( $goodsession ) { ?>
<div class="modal fade" id="request">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="request_form" method="post">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Request an API Key</h4>
      </div>
      <div class="modal-body">
            <p>Please how you will be using the key below (i.e. the course you are
            teaching and the school where you are teaching).
            Students do not need a key to use this site.</p>
            </p>
            <div class="form-group">
                <label for="request_name">Name:</label>
                <input type="name" class="form-control" id="request_name" disabled
                value="<?php echo(htmlent_utf8($_SESSION['displayname'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_email">Email:</label>
                <input type="name" class="form-control" id="request_email" disabled
                value="<?php echo(htmlent_utf8($_SESSION['email'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_title">Course Title:</label>
                <input type="name" class="form-control" id="request_title" name="title" required="required">
            </div>

            <div class="radio">
                <label>
                    <input type="radio" name="lti" id="request_lti_1" value="1" checked>
                    IMS LTI 1.x (You will get a key/secret - this is the most common option)
                </label>
            </div>
            <div class="radio">
                <label>
                    <input type="radio" name="lti" id="request_lti_2" value="2">
                    IMS LTI 2.x (You will get a registration URL)
                </label>
            </div>

            <label for="request_reason">Reason / Comments: (required)</label>
            <textarea class="form-control" id="request_reason" name="notes" rows="6"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <input type="submit" id="request_save" class="btn btn-primary" value="Submit Request">
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php 
    Table::pagedTable($newrows, $searchfields, false, "request-detail");
?>
<p>
<button type="button" class="btn btn-default" onclick="$('#request').modal();return false;">New Key Request</button>
</p>

<?php
} // $goodsession
$OUTPUT->footer();

