<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "announcement-util.php";

LTIX::getConnection();

header('Content-Type: application/json; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    http_response_code(401);
    echo(json_encode(array("error" => "You are not logged in.")));
    exit();
}

if ( ! isset($_SESSION['context_id']) ) {
    http_response_code(400);
    echo(json_encode(array("error" => "Context required")));
    exit();
}

$context_id = $_SESSION['context_id'];
$user_id = $_SESSION['id'];

// Get announcements using shared utility
$announcement_data = getAnnouncementsForUser($context_id, $user_id);
$announcements = $announcement_data['announcements'];

// Format the response
$result = array(
    'status' => 'success',
    'announcements' => array(),
    'dismissed_count' => $announcement_data['dismissed_count']
);

foreach ($announcements as $announcement) {
    $result['announcements'][] = array(
        'announcement_id' => intval($announcement['announcement_id']),
        'title' => $announcement['title'],
        'text' => $announcement['text'],
        'url' => $announcement['url'],
        'created_at' => $announcement['created_at'],
        'updated_at' => $announcement['updated_at'],
        'creator_name' => $announcement['creator_name'],
        'dismissed' => $announcement['dismissed'] == 1
    );
}

echo(json_encode($result, JSON_PRETTY_PRINT));
