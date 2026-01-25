<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Pages extends Tool {

    const ROUTE = '/pages';
    const NAME = 'Pages';
    const REDIRECT = 'tsugi_controllers_pages';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Pages@index');
        $app->router->get($prefix.'/', 'Pages@index');
        $app->router->get('/'.self::REDIRECT, 'Pages@index');
        // Register specific routes BEFORE the parameterized route to avoid conflicts
        $app->router->get($prefix.'/json', 'Pages@json');
        $app->router->get($prefix.'/add', 'Pages@add');
        $app->router->post($prefix.'/add', 'Pages@addPost');
        $app->router->get($prefix.'/edit/{id}', 'Pages@edit');
        $app->router->post($prefix.'/edit/{id}', 'Pages@editPost');
        $app->router->get($prefix.'/manage', 'Pages@manage');
        $app->router->post($prefix.'/manage', 'Pages@managePost');
        $app->router->get($prefix.'/analytics', 'Pages@analytics');
        // Parameterized route must come LAST
        $app->router->get($prefix.'/{logical_key}', 'Pages@index');
    }

    /**
     * Generate a logical key from a title
     * 
     * Converts title to lowercase, removes punctuation, 
     * replaces sequences of spaces with single dash,
     * and limits to 99 characters
     * 
     * @param string $title The page title
     * @return string The logical key
     */
    private function generateLogicalKey($title) {
        // Convert to lowercase
        $key = strtolower($title);
        
        // Remove punctuation (keep alphanumeric, spaces, and dashes)
        $key = preg_replace('/[^a-z0-9\s-]/', '', $key);
        
        // Replace sequences of spaces with single dash
        $key = preg_replace('/\s+/', '-', $key);
        
        // Remove leading/trailing dashes
        $key = trim($key, '-');
        
        // Limit to 99 characters
        if ( strlen($key) > 99 ) {
            $key = substr($key, 0, 99);
            // Remove trailing dash if we cut in the middle
            $key = rtrim($key, '-');
        }
        
        // Ensure we have something
        if ( empty($key) ) {
            $key = 'page-' . time();
        }
        
        return $key;
    }

    public function index(Request $request, $logical_key = null)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        
        // Check if user is instructor/admin for this context
        $is_instructor = $this->isInstructor();
        
        // Get logical_key from URL parameter or route parameter
        if (!$logical_key && isset($_GET['logical_key']) && U::strlen($_GET['logical_key']) > 0) {
            $logical_key = $_GET['logical_key'];
        }
        
        // Determine which page to show
        $page = null;
        if ($logical_key) {
            // Show specific page by logical_key
            $sql = "SELECT page_id, title, body, published, is_main 
                    FROM {$CFG->dbprefix}pages 
                    WHERE context_id = :CID AND logical_key = :KEY";
            $params = array(':CID' => $context_id, ':KEY' => $logical_key);
            
            // Non-instructors can only see published pages
            if (!$is_instructor) {
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
            if (!$is_instructor) {
                $sql .= " AND published = 1";
            }
            
            $page = $PDOX->rowDie($sql, $params);
            
            // If no main page, check if there's only one page (auto-main)
            if (!$page) {
                $sql = "SELECT page_id, title, body, published, is_main 
                        FROM {$CFG->dbprefix}pages 
                        WHERE context_id = :CID";
                $params = array(':CID' => $context_id);
                
                // Non-instructors can only see published pages
                if (!$is_instructor) {
                    $sql .= " AND published = 1";
                }
                
                $all_pages = $PDOX->allRowsDie($sql, $params);
                if (count($all_pages) == 1) {
                    $page = $all_pages[0];
                }
            }
        }
        
        // Record learner analytics for this tool (not per-page within the tool)
        $analytics_path = self::ROUTE;
        $analytics_title = self::NAME;
        $this->lmsRecordLaunchAnalytics($analytics_path, $analytics_title);
        
        // If instructor/admin, add an analytics button
        $is_admin = $this->isAdmin();
        $show_analytics = $is_instructor || $is_admin;
        
        $tool_home = $this->toolHome(self::ROUTE);
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <?php if ($page): ?>
                <h1 style="display: flex; justify-content: space-between; align-items: center;">
                    <span><?= htmlspecialchars($page['title']) ?></span>
                    <span>
                    <?php if ($show_analytics): ?>
                        <?php $analytics_url = $tool_home . '/analytics'; ?>
                        <a href="<?= $analytics_url ?>" class="btn btn-default">
                            <span class="glyphicon glyphicon-signal"></span> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($is_instructor): ?>
                        <?php $manage_url = $tool_home . '/manage'; ?>
                        <a href="<?= $manage_url ?>" class="btn btn-default">Manage Pages</a>
                    <?php endif; ?>
                    </span>
                </h1>
                <div class="page-content">
                    <?= $page['body'] ?>
                </div>
            <?php else: ?>
                <h1 style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Pages</span>
                    <span>
                    <?php if ($show_analytics): ?>
                        <?php $analytics_url = $tool_home . '/analytics'; ?>
                        <a href="<?= $analytics_url ?>" class="btn btn-default">
                            <span class="glyphicon glyphicon-signal"></span> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($is_instructor): ?>
                        <?php $manage_url = $tool_home . '/manage'; ?>
                        <a href="<?= $manage_url ?>" class="btn btn-default">Manage Pages</a>
                    <?php endif; ?>
                    </span>
                </h1>
                <div class="alert alert-info">
                    <p>No page found.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $OUTPUT->footer();
    }

    public function json(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        
        // Get all pages for this context (instructors see all, students see only published)
        $is_instructor = $this->isInstructor();
        $sql = "SELECT page_id, title, logical_key 
                FROM {$CFG->dbprefix}pages 
                WHERE context_id = :CID";
        $params = array(':CID' => $context_id);
        
        if (!$is_instructor) {
            $sql .= " AND published = 1";
        }
        
        $sql .= " ORDER BY title ASC";
        
        $pages = $PDOX->allRowsDie($sql, $params);
        
        // Get base path for REST-style URLs
        $pages_base = $this->toolHome(self::ROUTE);
        
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
        
        return new JsonResponse($formatted_pages);
    }

    public function add(Request $request)
    {
        global $CFG, $OUTPUT;
        
        $this->requireInstructor('/pages');
        
        // Get base path for REST-style URLs
        $tool_home = $this->toolHome(self::ROUTE);
        $pages_base = $tool_home;
        $manage_url = $tool_home . '/manage';
        
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
                    <p class="help-block">If checked, this page will become the main page (shown at /pages). Any existing main page will be unset.</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_front_page" value="1" 
                               <?= U::get($_POST, 'is_front_page') ? 'checked' : '' ?>>
                        This is the front page
                    </label>
                    <p class="help-block">If checked, this page will be marked as the front page. Any existing front page will be unset.</p>
                </div>
                
                <p>
                    <button type="submit" class="btn btn-primary">Create Page</button>
                    <a href="<?= $manage_url ?>" class="btn btn-default">Cancel</a>
                </p>
            </form>
        </div>
        <?php
        $OUTPUT->footerStart();
        ?>
        <style>
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
        <?php $json_url = $tool_home . '/json'; ?>
        var pagesJsonUrl = '<?= $json_url ?>';
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
                    const textNode = writer.createText(page.title);
                    const insertPosition = selection.getFirstPosition();
                    model.insertContent(textNode, insertPosition);
                    
                    const range = writer.createRange(insertPosition, writer.createPositionAfter(textNode));
                    writer.setSelection(range);
                }
                
                editor.execute('link', url);
            });
        }

        $(document).ready( function () {
            ClassicEditor
                .create( document.querySelector( '#editor_body' ), ClassicEditor.defaultConfig )
                .then(ed => {
                    editor = ed;
                    
                    setTimeout(function() {
                        addPageLinkButtonToToolbar();
                    }, 500);
                })
                .catch( error => {
                    console.error( error );
                });
            
            $('#page_form').on('submit', function(e) {
                if ( editor ) {
                    $('#editor_body').val(editor.getData());
                }
            });
            
            window.onclick = function(event) {
                var modal = document.getElementById('page-link-modal');
                if (event.target == modal) {
                    closePageLinkModal();
                }
            };
        });

        function addPageLinkButtonToToolbar() {
            var toolbar = document.querySelector('.ck-editor .ck-toolbar') || 
                          document.querySelector('.ck.ck-toolbar') ||
                          document.querySelector('[class*="ck-toolbar"]');
            
            if (!toolbar) {
                console.log('Toolbar not found, retrying...');
                setTimeout(addPageLinkButtonToToolbar, 200);
                return;
            }
            
            if (toolbar.querySelector('[data-page-link-button]')) {
                return;
            }
            
            var linkButton = toolbar.querySelector('button[aria-label*="Link" i]') ||
                             toolbar.querySelector('button[title*="Link" i]') ||
                             toolbar.querySelector('.ck-button[class*="link" i]');
            
            var separator = document.createElement('span');
            separator.className = 'ck ck-toolbar__separator';
            
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
            
            if (linkButton && linkButton.parentElement) {
                var parent = linkButton.parentElement;
                if (linkButton.nextSibling) {
                    parent.insertBefore(separator, linkButton.nextSibling);
                    parent.insertBefore(button, separator.nextSibling);
                } else {
                    parent.appendChild(separator);
                    parent.appendChild(button);
                }
            } else {
                var toolbarGroup = toolbar.querySelector('.ck-toolbar__items') || toolbar;
                toolbarGroup.appendChild(separator);
                toolbarGroup.appendChild(button);
            }
            
            console.log('Page link button added to toolbar');
        }
        </script>
        <?php
        $OUTPUT->footerEnd();
    }

    public function addPost(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/pages');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $add_url = $tool_home . '/add';
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $user_id = $_SESSION['id'];
        
        $title = trim(U::get($_POST, 'title'));
        $body = U::get($_POST, 'body', '');
        $published = U::get($_POST, 'published', 0) ? 1 : 0;
        $is_main = U::get($_POST, 'is_main', 0) ? 1 : 0;
        $is_front_page = U::get($_POST, 'is_front_page', 0) ? 1 : 0;
        
        if (empty($title)) {
            $_SESSION['error'] = 'Title is required';
            return new RedirectResponse($add_url);
        }
        
        // Generate logical key from title
        $logical_key = $this->generateLogicalKey($title);
        
        // Check if logical_key already exists for this context
        $existing = $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages 
             WHERE context_id = :CID AND logical_key = :KEY",
            array(':CID' => $context_id, ':KEY' => $logical_key)
        );
        
        if ($existing) {
            // Append number to make it unique
            $counter = 1;
            $original_key = $logical_key;
            while ($existing) {
                $logical_key = $original_key . '-' . $counter;
                if (strlen($logical_key) > 99) {
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
        if ($page_count['cnt'] == 0) {
            $is_main = 1;
        } else if ($is_main) {
            // If this is marked as main and there are other pages, unset all other main pages first
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_main = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        // If this is marked as front page, unset all other front pages first
        if ($is_front_page) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_front_page = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        $sql = "INSERT INTO {$CFG->dbprefix}pages 
                (context_id, title, logical_key, body, published, is_main, is_front_page, user_id, created_at, updated_at) 
                VALUES (:CID, :title, :key, :body, :published, :main, :front_page, :UID, NOW(), NOW())";
        $values = array(
            ':CID' => $context_id,
            ':title' => $title,
            ':key' => $logical_key,
            ':body' => $body,
            ':published' => $published,
            ':main' => $is_main,
            ':front_page' => $is_front_page,
            ':UID' => $user_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ($q->success) {
            $_SESSION['success'] = 'Page created successfully';
            return new RedirectResponse($manage_url);
        } else {
            $_SESSION['error'] = 'Error creating page';
            return new RedirectResponse($add_url);
        }
    }

    public function edit(Request $request, $id)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor('/pages');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        $pages_base = $tool_home;
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $page_id = intval($id);
        
        if (!$page_id) {
            $_SESSION['error'] = 'Invalid page ID';
            return new RedirectResponse($manage_url);
        }
        
        // Get page for editing
        $page = $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}pages 
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        
        if (!$page) {
            $_SESSION['error'] = 'Page not found';
            return new RedirectResponse($manage_url);
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
                    <p class="help-block">If checked, this page will become the main page (shown at /pages). Any existing main page will be unset.</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_front_page" value="1" 
                               <?= (U::get($_POST, 'is_front_page', $page['is_front_page'] ?? 0)) ? 'checked' : '' ?>>
                        This is the front page
                    </label>
                    <p class="help-block">If checked, this page will be marked as the front page. Any existing front page will be unset.</p>
                </div>
                
                <p>
                    <button type="submit" class="btn btn-primary">Update Page</button>
                    <a href="<?= $manage_url ?>" class="btn btn-default">Cancel</a>
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
        <?php $json_url = $tool_home . '/json'; ?>
        var pagesJsonUrl = '<?= $json_url ?>';
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
                    const textNode = writer.createText(page.title);
                    const insertPosition = selection.getFirstPosition();
                    model.insertContent(textNode, insertPosition);
                    
                    const range = writer.createRange(insertPosition, writer.createPositionAfter(textNode));
                    writer.setSelection(range);
                }
                
                editor.execute('link', url);
            });
        }

        $(document).ready( function () {
            ClassicEditor
                .create( document.querySelector( '#editor_body' ), ClassicEditor.defaultConfig )
                .then(ed => {
                    editor = ed;
                    
                    setTimeout(function() {
                        addPageLinkButtonToToolbar();
                    }, 500);
                })
                .catch( error => {
                    console.error( error );
                });
            
            $('#page_form').on('submit', function(e) {
                if ( editor ) {
                    $('#editor_body').val(editor.getData());
                }
            });
            
            window.onclick = function(event) {
                var modal = document.getElementById('page-link-modal');
                if (event.target == modal) {
                    closePageLinkModal();
                }
            };
        });

        function addPageLinkButtonToToolbar() {
            var toolbar = document.querySelector('.ck-editor .ck-toolbar') || 
                          document.querySelector('.ck.ck-toolbar') ||
                          document.querySelector('[class*="ck-toolbar"]');
            
            if (!toolbar) {
                console.log('Toolbar not found, retrying...');
                setTimeout(addPageLinkButtonToToolbar, 200);
                return;
            }
            
            if (toolbar.querySelector('[data-page-link-button]')) {
                return;
            }
            
            var linkButton = toolbar.querySelector('button[aria-label*="Link" i]') ||
                             toolbar.querySelector('button[title*="Link" i]') ||
                             toolbar.querySelector('.ck-button[class*="link" i]');
            
            var separator = document.createElement('span');
            separator.className = 'ck ck-toolbar__separator';
            
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
            
            if (linkButton && linkButton.parentElement) {
                var parent = linkButton.parentElement;
                if (linkButton.nextSibling) {
                    parent.insertBefore(separator, linkButton.nextSibling);
                    parent.insertBefore(button, separator.nextSibling);
                } else {
                    parent.appendChild(separator);
                    parent.appendChild(button);
                }
            } else {
                var toolbarGroup = toolbar.querySelector('.ck-toolbar__items') || toolbar;
                toolbarGroup.appendChild(separator);
                toolbarGroup.appendChild(button);
            }
            
            console.log('Page link button added to toolbar');
        }
        </script>
        <?php
        $OUTPUT->footerEnd();
    }

    public function editPost(Request $request, $id)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/pages');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $page_id = intval($id);
        
        $title = trim(U::get($_POST, 'title'));
        $body = U::get($_POST, 'body', '');
        $published = U::get($_POST, 'published', 0) ? 1 : 0;
        $is_main = U::get($_POST, 'is_main', 0) ? 1 : 0;
        $is_front_page = U::get($_POST, 'is_front_page', 0) ? 1 : 0;
        
        if (empty($title)) {
            $_SESSION['error'] = 'Title is required';
            $edit_url = $tool_home . '/edit/' . $page_id;
            return new RedirectResponse($edit_url);
        }
        
        // Generate logical key from title
        $logical_key = $this->generateLogicalKey($title);
        
        // Check if logical_key already exists for this context (excluding current page)
        $existing = $PDOX->rowDie(
            "SELECT page_id FROM {$CFG->dbprefix}pages 
             WHERE context_id = :CID AND logical_key = :KEY AND page_id != :PID",
            array(':CID' => $context_id, ':KEY' => $logical_key, ':PID' => $page_id)
        );
        
        if ($existing) {
            // Append number to make it unique
            $counter = 1;
            $original_key = $logical_key;
            while ($existing) {
                $logical_key = $original_key . '-' . $counter;
                if (strlen($logical_key) > 99) {
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
        if ($is_main) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_main = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        // If this is marked as front page, unset all other front pages first
        if ($is_front_page) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET is_front_page = 0 WHERE context_id = :CID",
                array(':CID' => $context_id)
            );
        }
        
        $sql = "UPDATE {$CFG->dbprefix}pages 
                SET title = :title, logical_key = :key, body = :body, 
                    published = :published, is_main = :main, is_front_page = :front_page, updated_at = NOW()
                WHERE page_id = :PID AND context_id = :CID";
        $values = array(
            ':title' => $title,
            ':key' => $logical_key,
            ':body' => $body,
            ':published' => $published,
            ':main' => $is_main,
            ':front_page' => $is_front_page,
            ':PID' => $page_id,
            ':CID' => $context_id
        );
        $q = $PDOX->queryReturnError($sql, $values);
        if ($q->success) {
            $_SESSION['success'] = 'Page updated successfully';
            return new RedirectResponse($manage_url);
        } else {
            $_SESSION['error'] = 'Error updating page';
            $edit_url = $tool_home . '/edit/' . $page_id;
            return new RedirectResponse($edit_url);
        }
    }

    public function manage(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireInstructor('/pages');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        $add_url = $tool_home . '/add';
        $pages_base = $tool_home;
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        
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
                <span class="pull-right">
                    <a href="<?= $back_url ?>" class="btn btn-default" style="margin-right: 10px;">Back</a>
                    <a href="<?= $add_url ?>" class="btn btn-primary">Add New Page</a>
                </span>
            </h1>
            
            <?php if (count($pages) == 0): ?>
                <div class="alert alert-info">
                    <p>No pages yet. <a href="<?= $add_url ?>">Create your first page</a>.</p>
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
                                        <?php $view_url = $pages_base . '/' . urlencode($page['logical_key']); ?>
                                        <a href="<?= $view_url ?>">
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
                                    <?php $edit_url = $tool_home . '/edit/' . $page['page_id']; ?>
                                    <a href="<?= $edit_url ?>" class="btn btn-xs btn-default">Edit</a>
                                    <?php $view_url = $pages_base . '/' . urlencode($page['logical_key']); ?>
                                    <a href="<?= $view_url ?>" class="btn btn-xs btn-info" target="_blank">View</a>
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
    }

    public function managePost(Request $request)
    {
        global $CFG, $PDOX;
        
        $this->requireInstructor('/pages');
        
        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        
        // Handle delete action
        $action = U::get($_POST, 'action');
        $page_id = U::get($_POST, 'page_id');
        
        if ($action === 'delete' && $page_id) {
            // Verify ownership/context
            $check = $PDOX->rowDie(
                "SELECT page_id FROM {$CFG->dbprefix}pages 
                 WHERE page_id = :PID AND context_id = :CID",
                array(':PID' => $page_id, ':CID' => $context_id)
            );
            if ($check) {
                $sql = "DELETE FROM {$CFG->dbprefix}pages 
                        WHERE page_id = :PID AND context_id = :CID";
                $q = $PDOX->queryReturnError($sql, array(':PID' => $page_id, ':CID' => $context_id));
                if ($q->success) {
                    $_SESSION['success'] = 'Page deleted successfully';
                } else {
                    $_SESSION['error'] = 'Error deleting page';
                }
            } else {
                $_SESSION['error'] = 'Page not found';
            }
        }
        
        // Handle toggle published action
        if ($action === 'toggle_published' && $page_id) {
            $q = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}pages 
                 SET published = NOT published 
                 WHERE page_id = :PID AND context_id = :CID",
                array(':PID' => $page_id, ':CID' => $context_id)
            );
            if ($q->success) {
                $_SESSION['success'] = 'Page status updated successfully';
            } else {
                $_SESSION['error'] = 'Error updating page status';
            }
        }
        
        return new RedirectResponse($manage_url);
    }

    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }
}
