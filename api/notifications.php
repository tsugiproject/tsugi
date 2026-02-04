<?php
// Cookie-session notifications endpoint for retrieving unread notifications and announcements.
//
// This endpoint uses cookie-based sessions to authenticate users and returns
// all unread notifications and unread announcements in a single response.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Util\NotificationsService;

if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    return;
}

header('Content-Type: application/json; charset=utf-8');

LTIX::getConnection();
session_start();

// Must be logged in via cookie session
$user_id = U::get($_SESSION, 'id');
if ( ! $user_id ) {
    http_response_code(403);
    echo(json_encode(array('status' => 'error', 'detail' => 'Not logged in'), JSON_PRETTY_PRINT));
    return;
}
$user_id = $user_id + 0;

// Get context_id from session (may not be set for all users)
$context_id = U::get($_SESSION, 'context_id');

$result = array(
    'status' => 'success',
    'notifications' => array(),
    'announcements' => array(),
    'unread_notification_count' => 0,
    'unread_announcement_count' => 0,
    'total_unread' => 0
);

// Get unread notifications
$unread_notifications = NotificationsService::getForUser($user_id, true, 50); // Only unread, limit 50
$result['unread_notification_count'] = count($unread_notifications);

foreach ($unread_notifications as $notification) {
    // Parse json field if present
    $json_data = null;
    if (!empty($notification['json'])) {
        $decoded = json_decode($notification['json'], true);
        $json_data = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }
    
    $result['notifications'][] = array(
        'notification_id' => intval($notification['notification_id']),
        'title' => $notification['title'],
        'text' => $notification['text'],
        'url' => $notification['url'],
        'json' => $json_data,
        'created_at' => $notification['created_at'],
        'updated_at' => $notification['updated_at'],
        'is_read' => false,
        'type' => 'notification'
    );
}

// Get unread announcements (if context_id is available)
if ($context_id) {
    $p = $CFG->dbprefix;
    
    // Get undismissed announcements for this context
    // Only show announcements created within the last 30 days, limit to 50
    $sql = "SELECT A.announcement_id, A.title, A.text, A.url, A.created_at, A.updated_at,
                U.displayname AS creator_name,
                CASE WHEN D.dismissal_id IS NOT NULL THEN 1 ELSE 0 END AS dismissed
            FROM {$p}announcement AS A
            LEFT JOIN {$p}lti_user AS U ON A.user_id = U.user_id
            LEFT JOIN {$p}announcement_dismissal AS D 
                ON A.announcement_id = D.announcement_id AND D.user_id = :UID
            WHERE A.context_id = :CID
              AND A.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND (D.dismissal_id IS NULL)
            ORDER BY A.created_at DESC
            LIMIT 50";

    $undismissed_announcements = $PDOX->allRowsDie($sql, array(
        ':CID' => $context_id,
        ':UID' => $user_id
    ));
    
    $result['unread_announcement_count'] = count($undismissed_announcements);
    
    foreach ($undismissed_announcements as $announcement) {
        $result['announcements'][] = array(
            'announcement_id' => intval($announcement['announcement_id']),
            'title' => $announcement['title'],
            'text' => $announcement['text'],
            'url' => $announcement['url'],
            'created_at' => $announcement['created_at'],
            'updated_at' => $announcement['updated_at'],
            'creator_name' => $announcement['creator_name'],
            'dismissed' => false,
            'is_read' => false,
            'type' => 'announcement'
        );
    }
} else {
    // No context_id, so no announcements available
    $result['announcements'] = array();
    $result['unread_announcement_count'] = 0;
}

// Calculate total unread count
$result['total_unread'] = $result['unread_notification_count'] + $result['unread_announcement_count'];

echo(json_encode($result, JSON_PRETTY_PRINT));
