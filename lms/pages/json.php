<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

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

// Get all pages for this context (instructors see all, students see only published)
$is_instructor = isInstructor();
$sql = "SELECT page_id, title, logical_key 
        FROM {$CFG->dbprefix}pages 
        WHERE context_id = :CID";
$params = array(':CID' => $context_id);

if ( ! $is_instructor ) {
    $sql .= " AND published = 1";
}

$sql .= " ORDER BY title ASC";

$pages = $PDOX->allRowsDie($sql, $params);

// Get base path for REST-style URLs
$path = U::rest_path();
$pages_base = $path->parent; // This should be /lms/pages

// Format pages for the dropdown
$formatted_pages = array();
foreach ($pages as $page) {
    $formatted_pages[] = array(
        'id' => $page['page_id'],
        'title' => $page['title'],
        'logical_key' => $page['logical_key'],
        'url' => $pages_base . '/' . urlencode($page['logical_key'])
    );
}

echo(json_encode($formatted_pages));
