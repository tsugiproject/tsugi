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
if ( ! isInstructor() ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    header('Location: ' . addSession('index.php'));
    return;
}

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $title = trim(U::get($_POST, 'title'));
    $body = U::get($_POST, 'body', '');
    $published = U::get($_POST, 'published', 0) ? 1 : 0;
    $is_main = U::get($_POST, 'is_main', 0) ? 1 : 0;
    
    if ( empty($title) ) {
        $_SESSION['error'] = 'Title is required';
    } else {
        // Generate logical key from title
        $logical_key = generateLogicalKey($title);
        
        // Check if logical_key already exists for this context
        $existing = $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages 
             WHERE context_id = :CID AND logical_key = :KEY",
            array(':CID' => $context_id, ':KEY' => $logical_key)
        );
        
        if ( $existing ) {
            // Append number to make it unique
            $counter = 1;
            $original_key = $logical_key;
            while ( $existing ) {
                $logical_key = $original_key . '-' . $counter;
                if ( strlen($logical_key) > 99 ) {
                    // Truncate if too long
                    $logical_key = substr($original_key, 0, 99 - strlen('-' . $counter)) . '-' . $counter;
                }
                $existing = $PDOX->rowDie(
                    "SELECT page_id FROM {$CFG->dbprefix}pages 
                     WHERE context_id = :CID AND logical_key = :KEY",
                    array(':CID' => $context_id, ':KEY' => $logical_key)
                );
                $counter++;
            }
        }
        
        // Check if there are any other pages
        $page_count = $PDOX->rowDie(
            "SELECT COUNT(*) as cnt FROM {$CFG->dbprefix}pages WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        
        // If there are no other pages, this must be the main page
        if ( $page_count['cnt'] == 0 ) {
            $is_main = 1;
        } else if ( $is_main ) {
            // If this is marked as main and there are other pages, unset all other main pages first
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_main = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        $sql = "INSERT INTO {$CFG->dbprefix}pages 
                (context_id, title, logical_key, body, published, is_main, user_id, created_at, updated_at) 
                VALUES (:CID, :title, :key, :body, :published, :main, :UID, NOW(), NOW())";
        $values = array(
            ':CID' => $context_id,
            ':title' => $title,
            ':key' => $logical_key,
            ':body' => $body,
            ':published' => $published,
            ':main' => $is_main,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( $q->success ) {
            $_SESSION['success'] = 'Page created successfully';
            header('Location: ' . addSession('manage.php'));
            return;
        } else {
            $_SESSION['error'] = 'Error creating page';
        }
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Add New Page</h1>
    
    <form method="post" id="page_form">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" class="form-control" id="title" name="title" 
                   value="<?= htmlspecialchars(U::get($_POST, 'title', '')) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="body">Body:</label>
            <div class="ckeditor-container">
                <textarea name="body" id="editor_body"><?= htmlspecialchars(U::get($_POST, 'body', '')) ?></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="published" value="1" 
                       <?= U::get($_POST, 'published') ? 'checked' : '' ?>>
                Published (visible to students)
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_main" value="1" 
                       <?= U::get($_POST, 'is_main') ? 'checked' : '' ?>>
                This is the main page
            </label>
            <p class="help-block">If checked, this page will become the main page (shown at /lms/pages). Any existing main page will be unset.</p>
        </div>
        
        <p>
            <button type="submit" class="btn btn-primary">Create Page</button>
            <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Cancel</a>
        </p>
    </form>
</div>
<?php
$OUTPUT->footerStart();
?>
<script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
<script type="text/javascript">
ClassicEditor.defaultConfig = {
    toolbar: {
        items: [
            'heading',
            '|',
            'bold',
            'italic',
            'link',
            'bulletedList',
            'numberedList',
            'blockQuote',
            'insertTable',
            'mediaEmbed',
            'undo',
            'redo'
        ]
    }
};

var editor;

$(document).ready( function () {
    ClassicEditor
        .create( document.querySelector( '#editor_body' ), ClassicEditor.defaultConfig )
        .then(ed => {
            editor = ed;
        })
        .catch( error => {
            console.error( error );
        });
    
    // Handle form submission - get data from editor
    $('#page_form').on('submit', function(e) {
        if ( editor ) {
            $('#editor_body').val(editor.getData());
        }
    });
});
</script>
<?php
$OUTPUT->footerEnd();
