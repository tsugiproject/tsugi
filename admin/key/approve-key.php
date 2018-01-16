<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\CrudForm;
use \Tsugi\Core\Mail;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['request_id']) ) {
    die("request_id required");
}

$from_location = "request-detail?request_id=".$_REQUEST['request_id'];

$row = $PDOX->rowDie(
    "SELECT request_id, title, notes, admin, state, lti, R.created_at, R.updated_at, R.user_id, displayname, email
    FROM {$CFG->dbprefix}key_request AS R
    JOIN {$CFG->dbprefix}lti_user AS U ON R.user_id=U.user_id
    WHERE request_id = :rid",
    array(':rid' => $_REQUEST['request_id'])
);

if ( $row['state'] != 0 ) {
    $_SESSION['error'] = 'Row not ready to be approved';
    header("Location: ".$from_location);
}

if ( $row['lti'] == 1 ) {
    $lti_version = 1;
} else if ( $row['lti'] == 2 ) {
    $lti_version = 2;
} else {
    die("LTI must be version 1 or 2 only");
}

// Set up the email variables
$user_id = $row['user_id'];
$token = Mail::computeCheck($user_id);
$to = $row['email'];

// Handle post
if ( isset($_POST['doReject']) && isset($_POST['request_id']) ) {
    $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}key_request SET state=2 WHERE request_id = :rid",
            array('rid' => $_REQUEST['request_id'])
    );
    // if ( $CFG->owneremail && $CFG->OFFLINE === false) {
    if ( $CFG->owneremail ) {
        $subject = "Key Request Denied from ".$row['displayname'].' ('.$row['email'].' )';
        $message = "Key Request Denied from ".$row['displayname'].' ('.$row['email'].' )\n'.
            "System Admin: ".$CFG->ownername." (".$CFG->owneremail.")\n";
        $retval = Mail::send($to, $subject, $message, $user_id, $token);
    }

    $_SESSION['success'] = 'Request denied';
    header('Location: '.$from_location);
    return;
}

if ( isset($_POST['doApprove']) && isset($_POST['request_id']) ) {
    $subject = false;
    // if ( $CFG->owneremail && $CFG->OFFLINE === false) {
    if ( $CFG->owneremail ) {
        $subject = "Key Request Approved from ".$row['displayname'].' ('.$row['email'].' )';
        $message = "Key Request Approved from ".$row['displayname'].' ('.$row['email'].' )'.
            "\nSystem Admin: ".$CFG->ownername." (".$CFG->owneremail.")\n";
    }

    if ( $lti_version == 1 ) {
        $oauth_consumer_key = 'lti1_'.bin2hex(openssl_random_pseudo_bytes(256/8));
        $oauth_secret = bin2hex(openssl_random_pseudo_bytes(256/8));
        $key_sha256 = lti_sha256($oauth_consumer_key);
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_key 
                (key_sha256, key_key, secret, user_id, created_at, updated_at)
                VALUES ( :k256, :key, :secret, :uid, NOW(), NOW() )",
            array(
                'k256' => $key_sha256,
                'key' => $oauth_consumer_key,
                'secret' => $oauth_secret,
                'uid' => $user_id
            )
        );
        $message .= "\n\nKey: $oauth_consumer_key\n";
        $message .= "\nSecret: $oauth_secret\n";
        $message .= "\nInstructions for using your LTI 1.x key are at\n\n";
        $message .= $CFG->wwwroot . "/settings/key/using\n\n";
        error_log("New LTI 1.x Key Inserted: $oauth_consumer_key User: ".$row['email']);
    } else {
        $message .= "\nThe URL for LTI 2.x Registration is at\n\n";
        $message .= $CFG->wwwroot . "/lti/register\n\n";
        error_log("LTI 2.x Key Approved request_id=".$_REQUEST['request_id']." User: ".$row['email']);
    }

    // Update the request row
    $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}key_request SET state=1 WHERE request_id = :rid",
        array('rid' => $_REQUEST['request_id'])
    );

    if ( $subject ) {
        error_log("Email sent to $to, Subject: $subject");
        $retval = Mail::send($to, $subject, $message, $user_id, $token);
    }

    $_SESSION['success'] = 'Request approved';
    header('Location: '.$from_location);
    return;
}

/*
if ( isset($_POST['doApprove']) && isset($_POST['request_id']) ) {
    if ( $lti_version == 2 ) {
        $row = $PDOX->query(
            "UPDATE {$CFG->dbprefix}key_request SET state=1"

    }

}
*/

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<form method="post">
<input type="submit" name="doApprove" class="btn btn-warning" value="<?= _m("Approve") ?>"
  onclick="return confirm('Are you sure you want to approve this request?');">
<input type="submit" name="doReject" class="btn btn-danger" value="<?= _m("Reject") ?>"
  onclick="return confirm('Are you sure you want to reject this request?');">
<a href="<?= $from_location ?>" class="btn btn-default"><?= _m('Exit'); ?></a>
<input type="hidden" name="request_id" value="<?= htmlentities($_REQUEST['request_id']) ?>"/>
<h2>LTI <?= $lti_version ?>.x key request</h2>
<p><strong>User Name:</strong> <?= htmlent_utf8($row['displayname']) ?></p>
<p><strong>Email:</strong> <?= htmlent_utf8($row['email']) ?></p>
<p><strong>Title:</strong> <?= htmlent_utf8($row['title']) ?></p>
<p><strong>Created:</strong> <?= htmlent_utf8($row['created_at']) ?></p>
<p><strong>Updated:</strong> <?= htmlent_utf8($row['updated_at']) ?></p>
<p><strong>Note:</strong> <?= htmlent_utf8($row['notes']) ?></p>
</form>
<?php

$OUTPUT->footer();

