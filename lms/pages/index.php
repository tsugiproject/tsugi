<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";
require_once "page-util.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($_SESSION['context_id']) ) {
    die('Context required');
}

$context_id = $_SESSION['context_id'];
$user_id = $_SESSION['id'];

// Check if user is instructor/admin for this context
$is_instructor = isInstructor();

// Get logical_key from URL parameter or PATH_INFO
$logical_key = null;
if ( isset($_GET['logical_key']) && U::strlen($_GET['logical_key']) > 0 ) {
    $logical_key = $_GET['logical_key'];
} else if ( isset($_SERVER['PATH_INFO']) && U::strlen($_SERVER['PATH_INFO']) > 0 ) {
    $logical_key = ltrim($_SERVER['PATH_INFO'], '/');
}

// Determine which page to show
$page = null;
if ( $logical_key ) {
    // Show specific page by logical_key
    $sql = "SELECT page_id, title, body, published, is_main 
            FROM {$CFG->dbprefix}pages 
            WHERE context_id = :CID AND logical_key = :KEY";
    $params = array(':CID' => $context_id, ':KEY' => $logical_key);
    
    // Non-instructors can only see published pages
    if ( ! $is_instructor ) {
        $sql .= " AND published = 1";
    }
    
    $page = $PDOX->rowDie($sql, $params);
} else {
    // No logical_key - show main page
    // First, check if there's a page marked as main
    $sql = "SELECT page_id, title, body, published, is_main 
            FROM {$CFG->dbprefix}pages 
            WHERE context_id = :CID AND is_main = 1";
    $params = array(':CID' => $context_id);
    
    // Non-instructors can only see published pages
    if ( ! $is_instructor ) {
        $sql .= " AND published = 1";
    }
    
    $page = $PDOX->rowDie($sql, $params);
    
    // If no main page, check if there's only one page (auto-main)
    if ( ! $page ) {
        $sql = "SELECT page_id, title, body, published, is_main 
                FROM {$CFG->dbprefix}pages 
                WHERE context_id = :CID";
        $params = array(':CID' => $context_id);
        
        // Non-instructors can only see published pages
        if ( ! $is_instructor ) {
            $sql .= " AND published = 1";
        }
        
        $all_pages = $PDOX->allRowsDie($sql, $params);
        if ( count($all_pages) == 1 ) {
            $page = $all_pages[0];
        }
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <?php if ($page): ?>
        <h1 style="display: flex; justify-content: space-between; align-items: center;">
            <span><?= htmlspecialchars($page['title']) ?></span>
            <?php if ($is_instructor): ?>
                <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Manage Pages</a>
            <?php endif; ?>
        </h1>
        <div class="page-content">
            <?= $page['body'] ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No page found.</p>
        </div>
    <?php endif; ?>
</div>
<?php
$OUTPUT->footer();
