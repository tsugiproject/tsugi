<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

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

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$goodsession = isset($_SESSION['id']) && isset($_SESSION['email']) && isset($_SESSION['displayname']) &&
    strlen($_SESSION['id']) > 0 && strlen($_SESSION['email']) > 0 && strlen($_SESSION['displayname']) > 0 ;

if ( $goodsession && isset($_POST['title']) && isset($_POST['lti']) &&
        isset($_POST['title']) && isset($_POST['notes']) ) {
    if ( strlen($_POST['title']) < 1 ) {
        $_SESSION['error'] = _m("Requests must have titles");
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
    header("Location: ".LTIX::curPageUrlFolder());
    return;
}

$query_parms = false;
$searchfields = array("request_id", "title", "notes", "state", "admin", "email", "displayname", "R.created_at", "R.updated_at");
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
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">View Key Requests</a>
  <a href="keys" class="btn btn-default">View Keys</a>
  <a href="using" class="btn btn-default">Using Your Key</a>
</p>
<p>
If you are a teacher and want to use the interactive elements on this web
site using IMS Learning Tools Interoperability, you can request a key
form this page.  Please include a description of how you are 
planning on using your key.
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
<ul class="nav nav-tabs">
  <li class="active"><a href="#lti" data-toggle="tab" aria-expanded="true">LTI 1.x</a></li>
  <li><a href="#sakai" data-toggle="tab" aria-expanded="false">Sakai</a></li>
  <li><a href="#canvas" data-toggle="tab" aria-expanded="false">Canvas</a></li>
  <li><a href="#moodle" data-toggle="tab" aria-expanded="false">Moodle</a></li>
  <li><a href="#lti2" data-toggle="tab" aria-expanded="false">LTI 2.x</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="lti">
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
</div>
<div class="tab-pane fade" id="sakai">
Sakai 10 and later supports the IMS Content Item standard so you can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre>
<?= $CFG->wwwroot ?>/lti/store/
</pre>
In Sakai, use the Lessons tool, select "External Tools" and install this as 
an LTI 1.x tool.  Make sure to check the 
"Supports Content Item" option when installing this URL in Sakai and tick 
the boxes to allow both the title and url to be changed.
</p>
<p>
Then this "<?= $CFG->servicename ?> store" will appear in Lessons as a new external tool, when you 
select the store you will be launched into the picker to choose tools and/or
resources to be pulled into Lessons.   The key and secret will be inherited
from the store to each of the installed tools.
</div>
<div class="tab-pane fade" id="canvas">
You can install this into Canvas as an "App Store" / "Learning Object Repository"
using XML configuration with your key and secret
and the following URL:
<pre>
<?= $CFG->wwwroot ?>/lti/store/canvas-config.xml
</pre>
Your tool should see the little search icon (<i style="color: blue;" class="fa fa-search"></i>) once
it is installed in Canvas to indicate that it is a searchable source of tools and content.
This content will be available in the Modules, Pages, Assignments, and Import
within Canvas under "external tools".
</div>
<div class="tab-pane fade" id="moodle">
Moodle 3.4 and later supports the IMS Content Item standard so you can install
this site as an "App Store" / "Learning Object Repository" using this url:
<pre>
<?= $CFG->wwwroot ?>/lti/store/
</pre>
Make sure to find and check the "Supports Content Item" option when installing 
this URL.
</div>
<div class="tab-pane fade" id="lti2">
If your LMS supports LTI 2.x and you have received an LTI 2 key for this service,
use the following registration URL:
<pre>
<?= $CFG->wwwroot ?>/lti/register.php
</pre>
</div>
</div>
</div>

<?php } ?>
<?php 
    Table::pagedTable($newrows, $searchfields, false, "request-detail");
if ( $goodsession ) { ?>
<p>
<button type="button" class="btn btn-default" onclick="$('#request').modal();return false;">New Key Request</button>
</p>
<?php } ?>

<?php
$OUTPUT->footer();

