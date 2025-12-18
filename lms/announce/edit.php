<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

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
$is_context_admin = isInstructor();

if ( ! $is_context_admin ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    header('Location: ' . addSession('index.php'));
    return;
}

// Get announcement ID
$announcement_id = U::get($_GET, 'id');
if ( ! $announcement_id || ! is_numeric($announcement_id) ) {
    $_SESSION['error'] = 'Invalid announcement ID';
    header('Location: ' . addSession('manage.php'));
    return;
}

$announcement_id = intval($announcement_id);

// Get announcement for editing
$announcement = $PDOX->rowDie(
    "SELECT * FROM {$CFG->dbprefix}announcement 
     WHERE announcement_id = :AID AND context_id = :CID",
    array(':AID' => $announcement_id, ':CID' => $context_id)
);

if ( ! $announcement ) {
    $_SESSION['error'] = 'Announcement not found';
    header('Location: ' . addSession('manage.php'));
    return;
}

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $title = trim(U::get($_POST, 'title'));
    $text = trim(U::get($_POST, 'text'));
    $url = trim(U::get($_POST, 'url'));
    
    if ( empty($title) || empty($text) ) {
        $_SESSION['error'] = 'Title and text are required';
    } else {
        if ( empty($url) ) {
            $url = null;
        }
        
        $sql = "UPDATE {$CFG->dbprefix}announcement 
                SET title = :title, text = :text, url = :url, updated_at = NOW() 
                WHERE announcement_id = :AID AND context_id = :CID";
        $values = array(
            ':title' => $title,
            ':text' => $text,
            ':url' => $url,
            ':AID' => $announcement_id,
            ':CID' => $context_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( $q->success ) {
            $_SESSION['success'] = 'Announcement updated successfully';
            header('Location: ' . addSession('manage.php'));
            return;
        } else {
            $_SESSION['error'] = 'Error updating announcement';
        }
    }
    // Reload announcement with POST data for form display
    $announcement['title'] = U::get($_POST, 'title');
    $announcement['text'] = U::get($_POST, 'text');
    $announcement['url'] = U::get($_POST, 'url');
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Edit Announcement</h1>
    
    <p>
        <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Back to Manage</a>
        <a href="<?= addSession('index.php') ?>" class="btn btn-default">Student View</a>
    </p>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Edit Announcement</h3>
        </div>
        <div class="panel-body">
            <form method="POST" action="<?= addSession('edit.php?id=' . $announcement_id) ?>">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($announcement['title']) ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="text">Text *</label>
                    <textarea class="form-control" id="text" name="text" rows="5" required><?= htmlspecialchars($announcement['text']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="url">URL (optional)</label>
                    <input type="url" class="form-control" id="url" name="url" 
                           value="<?= htmlspecialchars($announcement['url'] ?: '') ?>" 
                           placeholder="https://example.com">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Announcement</button>
                <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php
$OUTPUT->footer();
