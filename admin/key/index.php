<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\Mail;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    $_SESSION['error'] = _m("This service does not accept instructor requests for keys");
    header('Location: '.$CFG->wwwroot.'/index.php');
    return;
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    $_SESSION['login_return'] = $CFG->getUrlFull(__FILE__) . "/index.php";
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$goodsession = isset($_SESSION['id']) && isset($_SESSION['email']) && isset($_SESSION['displayname']) &&
    strlen($_SESSION['id']) > 0 && strlen($_SESSION['email']) > 0 && strlen($_SESSION['displayname']) > 0 ;

if ( $goodsession && isset($_POST['title']) && isset($_POST['lti']) &&
        isset($_POST['title']) && isset($_POST['notes']) ) {
    if ( strlen($_POST['title']) < 1 ) {
        $_SESSION['error'] = _m("Requests must have titles");
        header("Location: index.php");
        return;
    }
    $version = $_POST['lti']+0;
    if ( $version != 1 && $version != 2 ) {
        $_SESSION['error'] = _m("LTI Version muse be 1 or 2");
        header("Location: index.php");
        return;
    }
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$CFG->dbprefix}key_request
        (user_id, title, notes, state, lti, created_at, updated_at)
        VALUES ( :UID, :TITLE, :NOTES, 0, :LTI, NOW(), NOW() )",
        array(":UID" => $_SESSION['id'], ":TITLE" => $_POST['title'],
            ":NOTES" => $_POST['notes'], ":LTI" => $version)
    );
    if ( !isAdmin() && $CFG->owneremail && $CFG->OFFLINE === false) {
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
    header("Location: index.php");
    return;
}

$query_parms = false;
$searchfields = array("request_id", "title", "notes", "state", "admin", "created_at", "updated_at");
$sql = "SELECT request_id, title, notes, state, admin, R.created_at, R.updated_at, email, displayname
        FROM {$CFG->dbprefix}key_request  as R
        JOIN {$CFG->dbprefix}lti_user AS U ON R.user_id = U.user_id ";

if ( !isAdmin() ) {
    $sql .= "\nWHERE R.user_id = :UID";
    $query_parms = array(":UID" => $_SESSION['id']);
}

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
  <a href="keys.php" class="btn btn-default">View Keys</a>
  <a href="#" class="btn btn-default" onclick="
    showModal('Using this key', 'about-div');
    return false;
">Using Your Key</a>
</p>
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
            <div class="form-group">
                <label for="request_name">Name:</label>
                <input type="name" class="form-control" id="request_name" disabled
                value="<?php echo(htmlent_utf8($_SESSION['displayname'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_email">Name:</label>
                <input type="name" class="form-control" id="request_email" disabled
                value="<?php echo(htmlent_utf8($_SESSION['email'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_title">Title:</label>
                <input type="name" class="form-control" id="request_title" name="title" required="required">
            </div>

            <div class="radio">
                <label>
                    <input type="radio" name="lti" id="request_lti_1" value="1" checked>
                    IMS LTI 1.x (You will get a key/secret)
                </label>
            </div>
            <div class="radio">
                <label>
                    <input type="radio" name="lti" id="request_lti_2" value="2">
                    IMS LTI 2.x (You will get a registration URL)
                </label>
            </div>

            <label for="request_reason">Reason / Comments:</label>
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

<div id="about-div" style="display:none">
<h1>Using your key</h1>
<p>
There are several ways to use your key:
<ul>
<?php
$tools=findAllRegistrations();
foreach($tools as $tool) {
    echo("<li>\n");
    echo("You can install the ".$tool['short_name']." tool at the folowing URL:\n");
    echo("<pre>\n");
    echo($tool['url']);
    echo("</pre>\n");
    echo("</li>\n");
}
?>
<li>You can install this into Sakai as an "App Store" using IMS Content Item with
the following URL:
<pre>
<?= $CFG->wwwroot ?>/lti/store/
</pre>
Make sure to check the "Supports Content Item" option when installing 
this URL in Sakai and tick the boxes to allow the title and url to be changed.
</li>
<li>You can install this into Canvas as an "App Store" using XML configuration
and the following URL:
<pre>
<?= $CFG->wwwroot ?>/lti/store/canvas-config.xml
</pre>
</li>
<li>If your LMS supports LTI 2.x and you have an LTI 2 key for this service,
use the following registration URL:
<pre>
<?= $CFG->wwwroot ?>/lti/register.php
</pre>
</li>
</ul>
</div>

<?php } ?>
<?php if ( count($newrows) < 1 ) { ?>
<p>
This server hosts various tools that can be integrated into a learning system
using the IMS Learning Tools Interoperability standard.  You can use this page
to request access to this service.
</p>
<?php } else {
    Table::pagedTable($newrows, $searchfields, false, "request-detail.php");
}
if ( $goodsession ) { ?>
<p>
<button type="button" class="btn btn-default" onclick="$('#request').modal();return false;">New Key Request</button>
</p>
<?php } ?>

<?php
$OUTPUT->footer();

