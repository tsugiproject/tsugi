<?php
// Cookie-session analytics endpoint for top-frame LMS tools.
//
// This endpoint is intentionally separate from /api/analytics.php (LTI/cookieless)
// to avoid session-mode ambiguity.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Event\Entry;
use \Tsugi\Core\Rest;
use \Tsugi\Util\U;

if ( Rest::preFlight() ) return;

header('Content-Type: application/json; charset=utf-8');

LTIX::getConnection();
session_start();

$link_id = U::get($_GET, 'link_id');
if ( $link_id !== null ) $link_id = $link_id + 0;
if ( ! $link_id || $link_id < 1 ) {
    http_response_code(403);
    echo(json_encode(array('error' => 'No link_id'), JSON_PRETTY_PRINT));
    return;
}

// Must be logged in via cookie session
$user_id = U::get($_SESSION,'id');
if ( ! $user_id ) {
    http_response_code(403);
    echo(json_encode(array('error' => 'Not logged in'), JSON_PRETTY_PRINT));
    return;
}
$user_id = $user_id + 0;

// Admins are allowed (admin session sets $_SESSION['admin'])
$is_admin = U::get($_SESSION,'admin') ? true : false;

if ( ! $is_admin ) {
    // Instructor check for the link's context (role or role_override >= ROLE_INSTRUCTOR)
    $row = $PDOX->rowDie(
        "SELECT L.context_id, M.role, M.role_override
         FROM {$CFG->dbprefix}lti_link AS L
         LEFT JOIN {$CFG->dbprefix}lti_membership AS M
            ON M.context_id = L.context_id AND M.user_id = :UID
         WHERE L.link_id = :LID",
        array(':UID' => $user_id, ':LID' => $link_id)
    );
    if ( ! $row ) {
        http_response_code(403);
        echo(json_encode(array('error' => 'Invalid link_id'), JSON_PRETTY_PRINT));
        return;
    }
    $role = isset($row['role']) ? ($row['role'] + 0) : 0;
    $role_override = isset($row['role_override']) ? ($row['role_override'] + 0) : 0;
    $max_role = max($role, $role_override);
    if ( $max_role < LTIX::ROLE_INSTRUCTOR ) {
        http_response_code(403);
        echo(json_encode(array('error' => 'Not authorized'), JSON_PRETTY_PRINT));
        return;
    }
}

$sql = "SELECT link_count, activity FROM {$CFG->dbprefix}lti_link_activity
    WHERE link_id = :link_id AND event = 0";
$values = array(':link_id' => $link_id);
$row = $PDOX->rowDie($sql, $values);

$ent = new Entry();
if ( is_array($row) ) {
    $ent->deSerialize($row['activity']);
    $ent->total = $row['link_count']+0;
}
$retval = $ent->viewModel();

echo(json_encode($retval,JSON_PRETTY_PRINT));

