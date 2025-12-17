<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../../admin/admin_util.php";

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
$is_context_admin = false;
if ( isAdmin() ) {
    $is_context_admin = true;
} else if ( U::get($_SESSION, 'id') ) {
    // Check if user is instructor/admin for this context
    $membership = $PDOX->rowDie(
        "SELECT role FROM {$CFG->dbprefix}lti_membership 
         WHERE context_id = :CID AND user_id = :UID",
        array(':CID' => $context_id, ':UID' => $user_id)
    );
    if ( $membership && isset($membership['role']) ) {
        $role = $membership['role'] + 0;
        // ROLE_INSTRUCTOR = 1000, ROLE_ADMINISTRATOR = 5000
        if ( $role >= LTIX::ROLE_INSTRUCTOR ) {
            $is_context_admin = true;
        }
    }
    // Also check if user owns the context or its key
    if ( ! $is_context_admin ) {
        $context_check = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (
                 key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                 OR user_id = :UID
             )",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( $context_check ) {
            $is_context_admin = true;
        }
    }
}

if ( ! $is_context_admin ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    header('Location: ' . addSession('index.php'));
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
        
        $sql = "INSERT INTO {$CFG->dbprefix}announcement 
                (context_id, title, text, url, user_id, created_at, updated_at) 
                VALUES (:CID, :title, :text, :url, :UID, NOW(), NOW())";
        $values = array(
            ':CID' => $context_id,
            ':title' => $title,
            ':text' => $text,
            ':url' => $url,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( $q->success ) {
            $_SESSION['success'] = 'Announcement created successfully';
            header('Location: ' . addSession('manage.php'));
            return;
        } else {
            $_SESSION['error'] = 'Error creating announcement';
        }
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Add New Announcement</h1>
    
    <p>
        <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Back to Manage</a>
        <a href="<?= addSession('index.php') ?>" class="btn btn-default">Student View</a>
    </p>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Create New Announcement</h3>
        </div>
        <div class="panel-body">
            <form method="POST" action="<?= addSession('add.php') ?>">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars(U::get($_POST, 'title', '')) ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="text">Text *</label>
                    <textarea class="form-control" id="text" name="text" rows="5" required><?= htmlspecialchars(U::get($_POST, 'text', '')) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="url">URL (optional)</label>
                    <input type="url" class="form-control" id="url" name="url" 
                           value="<?= htmlspecialchars(U::get($_POST, 'url', '')) ?>" 
                           placeholder="https://example.com">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Announcement</button>
                <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php
$OUTPUT->footer();
