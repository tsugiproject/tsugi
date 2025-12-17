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

// Handle delete action
$action = U::get($_POST, 'action');
$announcement_id = U::get($_POST, 'announcement_id');

if ( $action === 'delete' && $announcement_id ) {
    // Verify ownership/context
    $check = $PDOX->rowDie(
        "SELECT announcement_id FROM {$CFG->dbprefix}announcement 
         WHERE announcement_id = :AID AND context_id = :CID",
        array(':AID' => $announcement_id, ':CID' => $context_id)
    );
    if ( $check ) {
        $sql = "DELETE FROM {$CFG->dbprefix}announcement 
                WHERE announcement_id = :AID AND context_id = :CID";
        $q = $PDOX->queryReturnError($sql, array(':AID' => $announcement_id, ':CID' => $context_id));
        if ( $q->success ) {
            $_SESSION['success'] = 'Announcement deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting announcement';
        }
    } else {
        $_SESSION['error'] = 'Announcement not found';
    }
    header('Location: ' . addSession('manage.php'));
    return;
}

// Get all announcements for this context
$announcements = $PDOX->allRowsDie(
    "SELECT A.*, U.displayname AS creator_name 
     FROM {$CFG->dbprefix}announcement AS A
     LEFT JOIN {$CFG->dbprefix}lti_user AS U ON A.user_id = U.user_id
     WHERE A.context_id = :CID 
     ORDER BY A.created_at DESC",
    array(':CID' => $context_id)
);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Manage Announcements
        <a href="<?= addSession('index.php') ?>" class="btn btn-default pull-right" style="margin-left: 10px;">Student View</a>
        <a href="<?= addSession('add.php') ?>" class="btn btn-primary pull-right">Add New Announcement</a>
    </h1>
    
    <?php if (count($announcements) == 0): ?>
        <div class="alert alert-info">
            <p>No announcements yet. <a href="<?= addSession('add.php') ?>">Create one</a>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Text Preview</th>
                        <th>URL</th>
                        <th>Created</th>
                        <th>Creator</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?= htmlspecialchars($announcement['title']) ?></td>
                            <td><?= htmlspecialchars(substr($announcement['text'], 0, 100)) ?><?= strlen($announcement['text']) > 100 ? '...' : '' ?></td>
                            <td>
                                <?php if (!empty($announcement['url'])): ?>
                                    <a href="<?= htmlspecialchars($announcement['url']) ?>" target="_blank">
                                        <?= htmlspecialchars(substr($announcement['url'], 0, 30)) ?><?= strlen($announcement['url']) > 30 ? '...' : '' ?>
                                    </a>
                                <?php else: ?>
                                    <em>None</em>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?></td>
                            <td><?= htmlspecialchars($announcement['creator_name'] ?: 'Unknown') ?></td>
                            <td>
                                <a href="<?= addSession('edit.php?id=' . $announcement['announcement_id']) ?>" 
                                   class="btn btn-sm btn-primary">Edit</a>
                                <form method="POST" action="<?= addSession('manage.php') ?>" 
                                      style="display: inline-block;" 
                                      onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['announcement_id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$OUTPUT->footer();
