<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";
require_once "page-util.php";

// Get base path for REST-style URLs
$path = U::rest_path();
$pages_base = $path->parent;

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

// Get page ID
$page_id = U::get($_GET, 'id');
if ( ! $page_id || ! is_numeric($page_id) ) {
    $_SESSION['error'] = 'Invalid page ID';
    header('Location: ' . addSession('manage.php'));
    return;
}

$page_id = intval($page_id);

// Get page for editing
$page = $PDOX->rowDie(
    "SELECT * FROM {$CFG->dbprefix}pages 
     WHERE page_id = :PID AND context_id = :CID",
    array(':PID' => $page_id, ':CID' => $context_id)
);

if ( ! $page ) {
    $_SESSION['error'] = 'Page not found';
    header('Location: ' . addSession('manage.php'));
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
        
        // Check if logical_key already exists for this context (excluding current page)
        $existing = $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages 
             WHERE context_id = :CID AND logical_key = :KEY AND page_id != :PID",
            array(':CID' => $context_id, ':KEY' => $logical_key, ':PID' => $page_id)
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
                     WHERE context_id = :CID AND logical_key = :KEY AND page_id != :PID",
                    array(':CID' => $context_id, ':KEY' => $logical_key, ':PID' => $page_id)
                );
                $counter++;
            }
        }
        
        // If this is marked as main, unset all other main pages first
        if ( $is_main ) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_main = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        $sql = "UPDATE {$CFG->dbprefix}pages 
                SET title = :title, logical_key = :key, body = :body, 
                    published = :published, is_main = :main, updated_at = NOW()
                WHERE page_id = :PID AND context_id = :CID";
        $values = array(
            ':title' => $title,
            ':key' => $logical_key,
            ':body' => $body,
            ':published' => $published,
            ':main' => $is_main,
            ':PID' => $page_id,
            ':CID' => $context_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ( $q->success ) {
            $_SESSION['success'] = 'Page updated successfully';
            header('Location: ' . addSession('manage.php'));
            return;
        } else {
            $_SESSION['error'] = 'Error updating page';
        }
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <h1>Edit Page</h1>
    
    <form method="post" id="page_form">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" class="form-control" id="title" name="title" 
                   value="<?= htmlspecialchars(U::get($_POST, 'title', $page['title'])) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="body">Body:</label>
            <div class="ckeditor-container">
                <textarea name="body" id="editor_body"><?= htmlspecialchars(U::get($_POST, 'body', $page['body'])) ?></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="published" value="1" 
                       <?= (U::get($_POST, 'published', $page['published'])) ? 'checked' : '' ?>>
                Published (visible to students)
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_main" value="1" 
                       <?= (U::get($_POST, 'is_main', $page['is_main'])) ? 'checked' : '' ?>>
                This is the main page
            </label>
            <p class="help-block">If checked, this page will become the main page (shown at /lms/pages). Any existing main page will be unset.</p>
        </div>
        
        <p>
            <button type="submit" class="btn btn-primary">Update Page</button>
            <a href="<?= addSession('manage.php') ?>" class="btn btn-default">Cancel</a>
        </p>
    </form>
</div>
<?php
$OUTPUT->footerStart();
?>
<style>
.ckeditor-container { min-height: 400px; }
#page-link-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
#page-link-modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; max-width: 90%; }
#page-link-list { max-height: 300px; overflow-y: auto; margin: 10px 0; }
.page-link-item { padding: 8px; cursor: pointer; border-bottom: 1px solid #ddd; }
.page-link-item:hover { background-color: #f0f0f0; }
[data-page-link-button] { display: inline-flex !important; align-items: center !important; }
[data-page-link-button] .ck-icon { width: 20px !important; height: 20px !important; }
</style>
<div id="page-link-modal">
    <div id="page-link-modal-content">
        <h3>Select a page to link</h3>
        <div id="page-link-list"></div>
        <button type="button" onclick="closePageLinkModal()" class="btn btn-default">Cancel</button>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
<script type="text/javascript">
// Get pages JSON URL and base path
var pagesJsonUrl = '<?= addSession("json.php") ?>';
var pagesBase = '<?= htmlspecialchars($pages_base) ?>';

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
var pagesList = [];

// Load pages list
fetch(pagesJsonUrl)
    .then(response => response.json())
    .then(pages => {
        pagesList = pages;
        populatePageLinkList();
    })
    .catch(error => {
        console.error('Error loading pages:', error);
    });

function populatePageLinkList() {
    var listDiv = document.getElementById('page-link-list');
    if (!listDiv) return;
    
    listDiv.innerHTML = '';
    
    if (pagesList.length === 0) {
        listDiv.innerHTML = '<p>No pages available.</p>';
        return;
    }
    
    pagesList.forEach(function(page) {
        var item = document.createElement('div');
        item.className = 'page-link-item';
        item.textContent = page.title;
        item.onclick = function() {
            insertPageLink(page);
            closePageLinkModal();
        };
        listDiv.appendChild(item);
    });
}

function showPageLinkModal() {
    document.getElementById('page-link-modal').style.display = 'block';
}

function closePageLinkModal() {
    document.getElementById('page-link-modal').style.display = 'none';
}

function insertPageLink(page) {
    if (!editor) return;
    
    const model = editor.model;
    const selection = model.document.selection;
    const url = pagesBase + '/' + encodeURIComponent(page.logical_key);
    
    model.change(writer => {
        if (selection.isCollapsed) {
            // No text selected - insert page title as link
            const textNode = writer.createText(page.title);
            const insertPosition = selection.getFirstPosition();
            model.insertContent(textNode, insertPosition);
            
            // Select the inserted text
            const range = writer.createRange(insertPosition, writer.createPositionAfter(textNode));
            writer.setSelection(range);
        }
        
        // Apply link using CKEditor's link command
        // The link command will use the selected text
        editor.execute('link', url);
    });
}

$(document).ready( function () {
    ClassicEditor
        .create( document.querySelector( '#editor_body' ), ClassicEditor.defaultConfig )
        .then(ed => {
            editor = ed;
            
            // Add custom pageLink button to toolbar after editor is ready
            setTimeout(function() {
                addPageLinkButtonToToolbar();
            }, 500);
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('page-link-modal');
        if (event.target == modal) {
            closePageLinkModal();
        }
    };
});

function addPageLinkButtonToToolbar() {
    // Find the CKEditor toolbar - try multiple selectors
    var toolbar = document.querySelector('.ck-editor .ck-toolbar') || 
                  document.querySelector('.ck.ck-toolbar') ||
                  document.querySelector('[class*="ck-toolbar"]');
    
    if (!toolbar) {
        console.log('Toolbar not found, retrying...');
        setTimeout(addPageLinkButtonToToolbar, 200);
        return;
    }
    
    // Check if button already exists
    if (toolbar.querySelector('[data-page-link-button]')) {
        return;
    }
    
    // Find the link button - try multiple selectors
    var linkButton = toolbar.querySelector('button[aria-label*="Link" i]') ||
                     toolbar.querySelector('button[title*="Link" i]') ||
                     toolbar.querySelector('.ck-button[class*="link" i]');
    
    // Create a separator
    var separator = document.createElement('span');
    separator.className = 'ck ck-toolbar__separator';
    
    // Create the page link button with shopping cart icon
    var button = document.createElement('button');
    button.className = 'ck ck-button ck-toolbar__item';
    button.type = 'button';
    button.setAttribute('aria-label', 'Insert Page Link');
    button.setAttribute('title', 'Insert Page Link');
    button.setAttribute('data-page-link-button', 'true');
    button.innerHTML = '<svg class="ck-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; fill: currentColor;"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';
    
    button.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        showPageLinkModal();
    };
    
    // Insert after link button if found, otherwise find a good spot in toolbar
    if (linkButton && linkButton.parentElement) {
        // Insert separator and button right after link button
        var parent = linkButton.parentElement;
        if (linkButton.nextSibling) {
            parent.insertBefore(separator, linkButton.nextSibling);
            parent.insertBefore(button, separator.nextSibling);
        } else {
            parent.appendChild(separator);
            parent.appendChild(button);
        }
    } else {
        // Try to find the toolbar group and append there
        var toolbarGroup = toolbar.querySelector('.ck-toolbar__items') || toolbar;
        toolbarGroup.appendChild(separator);
        toolbarGroup.appendChild(button);
    }
    
    console.log('Page link button added to toolbar');
}
</script>
<?php
$OUTPUT->footerEnd();
