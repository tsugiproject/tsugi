<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: application/json; charset=utf-8');
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('status' => 'error', 'detail' => 'Method not allowed'));
    return;
}

if ( ! U::get($_SESSION,'id') ) {
    http_response_code(401);
    echo json_encode(array('status' => 'error', 'detail' => 'Must be logged in'));
    return;
}

if ( ! isset($_SESSION['context_id']) ) {
    http_response_code(400);
    echo json_encode(array('status' => 'error', 'detail' => 'Context required'));
    return;
}

$announcement_id = U::get($_POST, 'announcement_id');
if ( ! $announcement_id || ! is_numeric($announcement_id) ) {
    http_response_code(400);
    echo json_encode(array('status' => 'error', 'detail' => 'Invalid announcement_id'));
    return;
}

$dismiss = U::get($_POST, 'dismiss', 1); // Default to dismiss if not specified
$dismiss = ($dismiss == 1 || $dismiss === '1' || $dismiss === true) ? 1 : 0;

$user_id = $_SESSION['id'];
$context_id = $_SESSION['context_id'];
$announcement_id = intval($announcement_id);

// Verify the announcement exists and belongs to this context
$announcement = $PDOX->rowDie(
    "SELECT announcement_id FROM {$CFG->dbprefix}announcement 
     WHERE announcement_id = :AID AND context_id = :CID",
    array(':AID' => $announcement_id, ':CID' => $context_id)
);

if ( ! $announcement ) {
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'detail' => 'Announcement not found'));
    return;
}

// Check if already dismissed
$existing = $PDOX->rowDie(
    "SELECT dismissal_id FROM {$CFG->dbprefix}announcement_dismissal 
     WHERE announcement_id = :AID AND user_id = :UID",
    array(':AID' => $announcement_id, ':UID' => $user_id)
);

if ( $dismiss ) {
    // Dismiss: Insert dismissal if not already dismissed
    if ( ! $existing ) {
        $sql = "INSERT INTO {$CFG->dbprefix}announcement_dismissal 
                (announcement_id, user_id, dismissed_at) 
                VALUES (:AID, :UID, NOW())";
        $values = array(
            ':AID' => $announcement_id,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( ! $q->success ) {
            http_response_code(500);
            echo json_encode(array('status' => 'error', 'detail' => 'Database error'));
            return;
        }
    }
} else {
    // Undismiss: Delete dismissal if it exists
    if ( $existing ) {
        $sql = "DELETE FROM {$CFG->dbprefix}announcement_dismissal 
                WHERE announcement_id = :AID AND user_id = :UID";
        $values = array(
            ':AID' => $announcement_id,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( ! $q->success ) {
            http_response_code(500);
            echo json_encode(array('status' => 'error', 'detail' => 'Database error'));
            return;
        }
    }
}

echo json_encode(array('status' => 'success'));
