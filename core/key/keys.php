<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once($CFG->dirroot."/pdo.php");
require_once($CFG->dirroot."/lib/lms_lib.php");

if ( $CFG->providekeys === false || $CFG->owneremail === false ) { 
    $_SESSION['error'] = _("This service does not accept instructor requests for keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! ( isset($_SESSION['id']) || is_admin() ) ) {
    $_SESSION['login_return'] = getPwdFull(__FILE__) . "/index.php";
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$goodsession = isset($_SESSION['id']) && isset($_SESSION['email']) && isset($_SESSION['displayname']) &&
    strlen($_SESSION['id']) > 0 && strlen($_SESSION['email']) > 0 && strlen($_SESSION['displayname']) > 0 ;

if ( ! $goodsession && ! is_admin() ) {
    $_SESSION['error'] = _("Full name and e-mail required to manage keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

if ( isset($_POST['title']) && isset($_POST['lti']) &&
        isset($_POST['title']) && isset($_POST['notes']) ) {
    if ( strlen($_POST['title']) < 1 ) {
        $_SESSION['error'] = _("Requests must have titles");
        header("Location: index.php");
        return;
    }
    $version = $_POST['lti']+0;
    if ( $version != 1 && $version != 2 ) {
        $_SESSION['error'] = _("LTI Version muse be 1 or 2");
        header("Location: index.php");
        return;
    }
    $stmt = pdoQueryDie($pdo,
        "INSERT INTO {$CFG->dbprefix}key_request  
        (user_id, title, notes, state, lti, created_at, updated_at) 
        VALUES ( :UID, :TITLE, :NOTES, 0, :LTI, NOW(), NOW() )",
        array(":UID" => $_SESSION['id'], ":TITLE" => $_POST['title'],
            ":NOTES" => $_POST['notes'], ":LTI" => $version)
    );
    $_SESSION['success'] = "Record inserted";
    header("Location: index.php");
    return;
}

$query_parms = false;
$searchfields = array("key_id", "key_key", "created_at", "updated_at", "user_id");
$sql = "SELECT key_id, key_key, secret, created_at, updated_at, user_id
        FROM {$CFG->dbprefix}lti_key";

if ( !is_admin() ) {
    $sql .= "\nWHERE user_id = :UID";
    $query_parms = array(":UID" => $_SESSION['id']);
}

$newsql = pagedPDOQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = pdoAllRowsDie($pdo, $newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrow['secret'] = '****';
    $newrows[] = $newrow;
}

headerContent();
startBody();
topNav();
flashMessages();
?>
<h1>LTI Keys</h1>
<p>
  <a href="index.php" class="btn btn-default">View Key Requests</a>
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
<?php } ?>
<?php if ( count($newrows) < 1 ) { ?>
<p>
This server hosts various tools that can be integrated into a learning system
using the IMS Learning Tools Interoperability standard.  You can use this page
to request access to this service.
</p>
<?php } else { 
    pagedPDOTable($newrows, $searchfields, false, "key-detail.php");
} 
if ( $goodsession ) { ?>
<p>
<a href="key-add.php" class="btn btn-default">Add Key</a>
</p>
<?php } ?>

<?php
footerContent();

