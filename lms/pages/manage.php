<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";
require_once "page-util.php";

// Get base path for REST-style URLs
$path = U::rest_path();
$pages_base = $path->parent; // This should be /lms/pages

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
if ( ! isInstructor() ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    header('Location: ' . addSession('index.php'));
    return;
}

// Handle delete action
$action = U::get($_POST, 'action');
$page_id = U::get($_POST, 'page_id');

if ( $action === 'delete' && $page_id ) {
    // Verify ownership/context
    $check = $PDOX->rowDie(
        "SELECT page_id FROM {$CFG->dbprefix}pages 
         WHERE page_id = :PID AND context_id = :CID",
        array(':PID' => $page_id, ':CID' => $context_id)
    );
    if ( $check ) {
        $sql = "DELETE FROM {$CFG->dbprefix}pages 
                WHERE page_id = :PID AND context_id = :CID";
        $q = $PDOX->queryReturnError($sql, array(':PID' => $page_id, ':CID' => $context_id));
        if ( $q->success ) {
            $_SESSION['success'] = 'Page deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting page';
        }
    } else {
        $_SESSION['error'] = 'Page not found';
    }
    header('Location: ' . addSession('manage.php'));
    return;
}

// Handle toggle published action
if ( $action === 'toggle_published' && $page_id ) {
    $q = $PDOX->queryReturnError(
        "UPDATE {$CFG->dbprefix}pages 
         SET published = NOT published 
         WHERE page_id = :PID AND context_id = :CID",
        array(':PID' => $page_id, ':CID' => $context_id)
    );
    if ( $q->success ) {
        $_SESSION['success'] = 'Page status updated successfully';
    } else {
        $_SESSION['error'] = 'Error updating page status';
    }
    header('Location: ' . addSession('manage.php'));
    return;
}

// Get all pages for this context
$pages = $PDOX->allRowsDie(
    "SELECT page_id, title, logical_key, published, is_main, is_front_page, created_at, updated_at 
     FROM {$CFG->dbprefix}pages 
     WHERE context_id = :CID 
     ORDER BY is_main DESC, is_front_page DESC, title ASC",
    array(':CID' => $context_id)
);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Manage Pages
        <a href="<?= addSession('add.php') ?>" class="btn btn-primary pull-right">Add New Page</a>
    </h1>
    
    <?php if (count($pages) == 0): ?>
        <div class="alert alert-info">
            <p>No pages yet. <a href="<?= addSession('add.php') ?>">Create your first page</a>.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Logical Key</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?= addSession($pages_base . '/' . urlencode($page['logical_key'])) ?>">
                                    <?= htmlspecialchars($page['title']) ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($page['logical_key']) ?></code>
                        </td>
                        <td>
                            <?php if ($page['published']): ?>
                                <span class="label label-success">Published</span>
                            <?php else: ?>
                                <span class="label label-default">Draft</span>
                            <?php endif; ?>
                            <?php if ($page['is_main']): ?>
                                <span class="label label-primary">Main</span>
                            <?php endif; ?>
                            <?php if (isset($page['is_front_page']) && $page['is_front_page']): ?>
                                <span class="label label-info">Front Page</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('Y-m-d H:i', strtotime($page['updated_at'])) ?>
                        </td>
                        <td>
                            <a href="<?= addSession('edit.php?id=' . $page['page_id']) ?>" class="btn btn-xs btn-default">Edit</a>
                            <a href="<?= addSession($pages_base . '/' . urlencode($page['logical_key'])) ?>" class="btn btn-xs btn-info" target="_blank">View</a>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to toggle the published status?');">
                                <input type="hidden" name="action" value="toggle_published">
                                <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                                <button type="submit" class="btn btn-xs btn-warning">
                                    <?= $page['published'] ? 'Unpublish' : 'Publish' ?>
                                </button>
                            </form>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                                <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
$OUTPUT->footer();
