<?php

namespace Tsugi\Controllers;

require_once __DIR__ . '/../UI/CKEditor.php';

use Tsugi\Util\U;
use Tsugi\Core\LTIX;

// Ensure CKEditor helper is loaded (fallback if autoload misses it)
require_once __DIR__ . '/../UI/CKEditor.php';
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
        $app->router->get($prefix.'/lessons-json', 'Pages@lessonsJson');
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
        
        // Remove all punctuation (keep alphanumeric and spaces)
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        
        // Reduce sequences of whitespace to a single space
        $key = preg_replace('/\s+/', ' ', $key);
        
        // Convert spaces to dashes
        $key = str_replace(' ', '-', $key);
        
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
        <main class="container" role="main" id="main-content">
            <?php if ($page): ?>
                <h1 style="display: flex; justify-content: space-between; align-items: center;">
                    <span><?= htmlspecialchars($page['title']) ?></span>
                    <span>
                    <?php if ($show_analytics): ?>
                        <?php $analytics_url = $tool_home . '/analytics'; ?>
                        <a href="<?= htmlspecialchars($analytics_url) ?>" class="btn btn-default" aria-label="<?= htmlspecialchars(__('View page analytics')) ?>">
                            <span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($is_instructor): ?>
                        <?php if ($page): ?>
                        <?php $edit_url = $tool_home . '/edit/' . $page['page_id']; ?>
                        <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-primary" aria-label="<?= htmlspecialchars(__('Edit')) ?> <?= htmlspecialchars($page['title']) ?>">Edit</a>
                        <?php endif; ?>
                        <?php $manage_url = $tool_home . '/manage'; ?>
                        <a href="<?= htmlspecialchars($manage_url) ?>" class="btn btn-default" aria-label="<?= htmlspecialchars(__('Manage pages')) ?>">Manage Pages</a>
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
                        <a href="<?= htmlspecialchars($analytics_url) ?>" class="btn btn-default" aria-label="<?= htmlspecialchars(__('View page analytics')) ?>">
                            <span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($is_instructor): ?>
                        <?php $manage_url = $tool_home . '/manage'; ?>
                        <a href="<?= htmlspecialchars($manage_url) ?>" class="btn btn-default" aria-label="<?= htmlspecialchars(__('Manage pages')) ?>">Manage Pages</a>
                    <?php endif; ?>
                    </span>
                </h1>
                <div class="alert alert-info" role="status">
                    <p>No page found.</p>
                </div>
            <?php endif; ?>
        </main>
        <?php
        $OUTPUT->footerStart();
        ?>
        <style>
        <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => false]); ?>
        </style>
        <?php
        $OUTPUT->footerEnd();
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

    /**
     * Return linkable items from lessons (modules) for the page-link picker.
     * Includes LTI tools, slides, references, videos, discussions, etc.
     */
    public function lessonsJson(Request $request)
    {
        global $CFG;

        $this->requireAuth();

        $apphome = isset($CFG->apphome) ? rtrim($CFG->apphome, '/') : '';
        $lessons_file = isset($CFG->lessons) ? $CFG->lessons : '';
        $items = array();

        if (empty($apphome) || empty($lessons_file) || !is_readable($lessons_file)) {
            return new JsonResponse(array('items' => $items, 'modules' => array()));
        }

        $json = @file_get_contents($lessons_file);
        if ($json === false) {
            return new JsonResponse(array('items' => $items, 'modules' => array()));
        }

        $data = @json_decode($json, true);
        if (!is_array($data) || empty($data['modules'])) {
            return new JsonResponse(array('items' => $items, 'modules' => array()));
        }

        $top_level_modules = array();
        foreach ($data['modules'] as $module) {
            $module_title = U::get($module, 'title', '');
            $module_anchor = U::get($module, 'anchor', '');
            if (!empty($module_title) && !empty($module_anchor)) {
                $top_level_modules[] = array(
                    'title' => $module_title,
                    'url' => $apphome . '/lessons/' . $module_anchor,
                    'anchor' => $module_anchor
                );
            }
            $module_items = U::get($module, 'items', array());

            foreach ($module_items as $item) {
                $type = U::get($item, 'type', '');
                $title = U::get($item, 'title', '');
                $url = null;

                if ($type === 'header') {
                    continue;
                }

                if ($type === 'lti' || $type === 'not-lti' || $type === 'discussion') {
                    $resource_link_id = U::get($item, 'resource_link_id', '');
                    if (!empty($resource_link_id)) {
                        $url = $apphome . '/lessons_launch/' . rawurlencode($resource_link_id);
                    }
                } elseif ($type === 'slide' || $type === 'reference') {
                    $href = U::get($item, 'href', '');
                    if (!empty($href)) {
                        $url = str_replace('{apphome}', $apphome, $href);
                    }
                } elseif ($type === 'video' && U::get($item, 'youtube', '')) {
                    $youtube_id = trim(U::get($item, 'youtube', ''));
                    if (!empty($youtube_id)) {
                        $url = 'https://www.youtube.com/watch?v=' . $youtube_id;
                    }
                }

                if (!empty($url) && !empty($title)) {
                    $item_data = array(
                        'title' => $title,
                        'url' => $url,
                        'module' => $module_title,
                        'module_anchor' => $module_anchor,
                        'type' => $type
                    );
                    if ($type === 'video') {
                        $item_data['target_blank'] = true;
                    }
                    $items[] = $item_data;
                }
            }
        }

        return new JsonResponse(array('items' => $items, 'modules' => $top_level_modules));
    }

    public function add(Request $request)
    {
        global $CFG, $OUTPUT;
        
        $this->requireInstructor('/pages');
        
        // Get base path for REST-style URLs
        $tool_home = $this->toolHome(self::ROUTE);
        $pages_base = $tool_home;
        $manage_url = $tool_home . '/manage';
        $apphome = isset($CFG->apphome) ? rtrim($CFG->apphome, '/') : '';
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
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
                    <label for="add_published">
                        <input type="checkbox" name="published" id="add_published" value="1" 
                               <?= U::get($_POST, 'published') ? 'checked' : '' ?>>
                        Published (visible to students)
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="add_is_main">
                        <input type="checkbox" name="is_main" id="add_is_main" value="1" 
                               <?= U::get($_POST, 'is_main') ? 'checked' : '' ?>>
                        This is the main page
                    </label>
                    <p class="help-block">If checked, this page will become the main page (shown at /pages). Any existing main page will be unset.</p>
                </div>
                
                <div class="form-group">
                    <label for="add_is_front_page">
                        <input type="checkbox" name="is_front_page" id="add_is_front_page" value="1" 
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
        </main>
        <?php
        $OUTPUT->footerStart();
        ?>
        <style>
        <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => true]); ?>
        </style>
        <?php \Tsugi\UI\CKEditor::renderLinkPickerModal('Insert link'); ?>

        <?php \Tsugi\UI\CKEditor::renderScriptTag(); ?>
        <script type="text/javascript">
        <?php $json_url = $tool_home . '/json'; ?>
        <?php $lessons_json_url = $tool_home . '/lessons-json'; ?>
        var pagesJsonUrl = '<?= $json_url ?>';
        var lessonsJsonUrl = '<?= $lessons_json_url ?>';
        var pagesBase = '<?= htmlspecialchars($pages_base) ?>';
        var appHome = '<?= htmlspecialchars(isset($apphome) ? $apphome : '') ?>';
        var currentPageId = <?= isset($current_page_id) ? (int)$current_page_id : 'null' ?>;

        <?php \Tsugi\UI\CKEditor::renderConfigScript(); ?>

        var editor;
        var pagesList = [];
        var lessonsList = [];
        var lessonsModules = [];

        Promise.all([
            fetch(pagesJsonUrl).then(function(r) { return r.json(); }),
            fetch(lessonsJsonUrl).then(function(r) { return r.json(); })
        ]).then(function(results) {
            pagesList = results[0] || [];
            var lessonsData = results[1] || {};
            lessonsList = lessonsData.items || (Array.isArray(lessonsData) ? lessonsData : []);
            lessonsModules = lessonsData.modules || [];
            populatePageLinkList();
        }).catch(function(error) {
            console.error('Error loading link data:', error);
        });

        function createExpandoSection(title, count, itemsContainer, startCollapsed) {
            var expando = document.createElement('div');
            expando.className = 'page-link-expando' + (startCollapsed ? ' collapsed' : '');
            expando.setAttribute('role', 'group');
            expando.setAttribute('aria-label', title);

            var header = document.createElement('div');
            header.className = 'page-link-expando-header';
            header.setAttribute('role', 'button');
            header.setAttribute('aria-expanded', !startCollapsed);
            header.setAttribute('tabindex', '0');
            header.innerHTML = '<span>' + title + ' (' + count + ')</span><span class="expando-chevron" aria-hidden="true">&#9660;</span>';

            header.onclick = function(e) {
                e.preventDefault();
                expando.classList.toggle('collapsed');
                header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
            };
            header.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    expando.classList.toggle('collapsed');
                    header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
                }
            };

            expando.appendChild(header);
            expando.appendChild(itemsContainer);
            return expando;
        }

        function populatePageLinkList() {
            var listDiv = document.getElementById('page-link-list');
            if (!listDiv) return;
            
            listDiv.innerHTML = '';
            
            var displayPages = currentPageId ? pagesList.filter(function(p) { return p.id != currentPageId; }) : pagesList;
            var hasContent = false;
            
            if (displayPages.length > 0) {
                var pagesContent = document.createElement('div');
                pagesContent.className = 'page-link-expando-content';
                pagesContent.setAttribute('role', 'list');

                displayPages.forEach(function(page) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'page-link-item';
                    item.textContent = page.title;
                    item.setAttribute('role', 'listitem');
                    item.onclick = function() {
                        insertPageLink(page);
                        closePageLinkModal();
                    };
                    item.onkeydown = function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            insertPageLink(page);
                            closePageLinkModal();
                        }
                    };
                    pagesContent.appendChild(item);
                });

                var pagesExpando = createExpandoSection('Pages', displayPages.length, pagesContent, true);
                listDiv.appendChild(pagesExpando);
                hasContent = true;
            }

            if (lessonsModules.length > 0) {
                var modulesContent = document.createElement('div');
                modulesContent.className = 'page-link-expando-content';
                modulesContent.setAttribute('role', 'list');

                lessonsModules.forEach(function(moduleItem) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'page-link-item';
                    item.textContent = moduleItem.title;
                    item.setAttribute('role', 'listitem');
                    item.setAttribute('title', moduleItem.url);
                    item.onclick = function() {
                        insertLessonLink(moduleItem);
                        closePageLinkModal();
                    };
                    item.onkeydown = function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            insertLessonLink(moduleItem);
                            closePageLinkModal();
                        }
                    };
                    modulesContent.appendChild(item);
                });

                var modulesExpando = createExpandoSection('Modules', lessonsModules.length, modulesContent, true);
                listDiv.appendChild(modulesExpando);
                hasContent = true;
            }
            
            if (lessonsList.length > 0) {
                var typeGroups = {
                    video: { label: 'Videos', items: [] },
                    discussion: { label: 'Discussions', items: [] },
                    lti: { label: 'LTI & Tools', items: [] },
                    'not-lti': { label: 'LTI & Tools', items: [] },
                    reference: { label: 'References', items: [] },
                    slide: { label: 'Slides', items: [] }
                };

                lessonsList.forEach(function(lessonItem) {
                    var t = lessonItem.type || 'reference';
                    if (t === 'not-lti') t = 'lti';
                    if (typeGroups[t]) typeGroups[t].items.push(lessonItem);
                });

                var ltiItems = (typeGroups.lti ? typeGroups.lti.items : []).concat(typeGroups['not-lti'] ? typeGroups['not-lti'].items : []);
                var groupsToShow = [
                    { label: 'Videos', items: typeGroups.video ? typeGroups.video.items : [] },
                    { label: 'Discussions', items: typeGroups.discussion ? typeGroups.discussion.items : [] },
                    { label: 'LTI & Tools', items: ltiItems },
                    { label: 'References', items: typeGroups.reference ? typeGroups.reference.items : [] },
                    { label: 'Slides', items: typeGroups.slide ? typeGroups.slide.items : [] }
                ];

                groupsToShow.forEach(function(group) {
                    if (!group.items || group.items.length === 0) return;

                    var content = document.createElement('div');
                    content.className = 'page-link-expando-content';
                    content.setAttribute('role', 'list');

                    group.items.forEach(function(lessonItem) {
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'page-link-item';
                        item.textContent = lessonItem.title + (lessonItem.module ? ' (' + lessonItem.module + ')' : '');
                        item.setAttribute('role', 'listitem');
                        item.setAttribute('title', lessonItem.url);
                        item.onclick = function() {
                            insertLessonLink(lessonItem);
                            closePageLinkModal();
                        };
                        item.onkeydown = function(e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                insertLessonLink(lessonItem);
                                closePageLinkModal();
                            }
                        };
                        content.appendChild(item);
                    });

                    var expando = createExpandoSection(group.label, group.items.length, content, true);
                    listDiv.appendChild(expando);
                    hasContent = true;
                });
            }
            
            if (!hasContent) {
                listDiv.innerHTML = '<p role="status">No pages or lesson content available.</p>';
            }
        }

        var pageLinkModalFocusBeforeOpen = null;

        function showPageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            pageLinkModalFocusBeforeOpen = document.activeElement;
            modal.style.display = 'block';
            requestAnimationFrame(function() { modal.classList.add('open'); });
            document.addEventListener('keydown', pageLinkModalKeyHandler);
            var firstFocusable = modal.querySelector('.page-link-item') || modal.querySelector('button');
            if (firstFocusable) {
                firstFocusable.focus();
            } else {
                modal.focus();
            }
        }

        function closePageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            modal.classList.remove('open');
            document.removeEventListener('keydown', pageLinkModalKeyHandler);
            setTimeout(function() {
                modal.style.display = 'none';
                if (pageLinkModalFocusBeforeOpen) {
                    pageLinkModalFocusBeforeOpen.focus();
                }
            }, 250);
        }

        function pageLinkModalKeyHandler(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closePageLinkModal();
            }
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
                    const insertedRange = model.insertContent(textNode, insertPosition);
                    writer.setSelection(insertedRange);
                }
            });
            
            editor.execute('link', url);
        }

        function insertLessonLink(lessonItem) {
            if (!editor) return;
            
            const model = editor.model;
            const selection = model.document.selection;
            const url = lessonItem.url;
            const title = lessonItem.title;
            
            model.change(writer => {
                if (selection.isCollapsed) {
                    const textNode = writer.createText(title);
                    const insertPosition = selection.getFirstPosition();
                    const insertedRange = model.insertContent(textNode, insertPosition);
                    writer.setSelection(insertedRange);
                }
            });
            
            editor.execute('link', url);
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
            button.innerHTML = '<svg class="ck-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; fill: currentColor;" aria-hidden="true"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';
            
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
            U::flashError('Title is required');
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
            U::flashSuccess('Page created successfully');
            return new RedirectResponse($manage_url);
        } else {
            U::flashError('Error creating page');
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
        $apphome = isset($CFG->apphome) ? rtrim($CFG->apphome, '/') : '';
        
        LTIX::getConnection();
        
        $context_id = $_SESSION['context_id'];
        $page_id = intval($id);
        
        if (!$page_id) {
            U::flashError('Invalid page ID');
            return new RedirectResponse($manage_url);
        }
        
        // Get page for editing
        $page = $PDOX->rowDie(
            "SELECT * FROM {$CFG->dbprefix}pages 
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        
        if (!$page) {
            U::flashError('Page not found');
            return new RedirectResponse($manage_url);
        }
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
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
                    <label for="edit_published">
                        <input type="checkbox" name="published" id="edit_published" value="1" 
                               <?= (U::get($_POST, 'published', $page['published'])) ? 'checked' : '' ?>>
                        Published (visible to students)
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="edit_is_main">
                        <input type="checkbox" name="is_main" id="edit_is_main" value="1" 
                               <?= (U::get($_POST, 'is_main', $page['is_main'])) ? 'checked' : '' ?>>
                        This is the main page
                    </label>
                    <p class="help-block">If checked, this page will become the main page (shown at /pages). Any existing main page will be unset.</p>
                </div>
                
                <div class="form-group">
                    <label for="edit_is_front_page">
                        <input type="checkbox" name="is_front_page" id="edit_is_front_page" value="1" 
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
        </main>
        <?php
        $OUTPUT->footerStart();
        $current_page_id = (int)$page['page_id'];
        ?>
        <style>
        <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => true, 'extraStyles' => '.ckeditor-container { min-height: 400px; }']); ?>
        </style>
        <?php \Tsugi\UI\CKEditor::renderLinkPickerModal('Insert link'); ?>

        <?php \Tsugi\UI\CKEditor::renderScriptTag(); ?>
        <script type="text/javascript">
        <?php $json_url = $tool_home . '/json'; ?>
        <?php $lessons_json_url = $tool_home . '/lessons-json'; ?>
        var pagesJsonUrl = '<?= $json_url ?>';
        var lessonsJsonUrl = '<?= $lessons_json_url ?>';
        var pagesBase = '<?= htmlspecialchars($pages_base) ?>';
        var appHome = '<?= htmlspecialchars(isset($apphome) ? $apphome : '') ?>';
        var currentPageId = <?= isset($current_page_id) ? (int)$current_page_id : 'null' ?>;

        <?php \Tsugi\UI\CKEditor::renderConfigScript(); ?>

        var editor;
        var pagesList = [];
        var lessonsList = [];
        var lessonsModules = [];

        Promise.all([
            fetch(pagesJsonUrl).then(function(r) { return r.json(); }),
            fetch(lessonsJsonUrl).then(function(r) { return r.json(); })
        ]).then(function(results) {
            pagesList = results[0] || [];
            var lessonsData = results[1] || {};
            lessonsList = lessonsData.items || (Array.isArray(lessonsData) ? lessonsData : []);
            lessonsModules = lessonsData.modules || [];
            populatePageLinkList();
        }).catch(function(error) {
            console.error('Error loading link data:', error);
        });

        function createExpandoSection(title, count, itemsContainer, startCollapsed) {
            var expando = document.createElement('div');
            expando.className = 'page-link-expando' + (startCollapsed ? ' collapsed' : '');
            expando.setAttribute('role', 'group');
            expando.setAttribute('aria-label', title);

            var header = document.createElement('div');
            header.className = 'page-link-expando-header';
            header.setAttribute('role', 'button');
            header.setAttribute('aria-expanded', !startCollapsed);
            header.setAttribute('tabindex', '0');
            header.innerHTML = '<span>' + title + ' (' + count + ')</span><span class="expando-chevron" aria-hidden="true">&#9660;</span>';

            header.onclick = function(e) {
                e.preventDefault();
                expando.classList.toggle('collapsed');
                header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
            };
            header.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    expando.classList.toggle('collapsed');
                    header.setAttribute('aria-expanded', !expando.classList.contains('collapsed'));
                }
            };

            expando.appendChild(header);
            expando.appendChild(itemsContainer);
            return expando;
        }

        function populatePageLinkList() {
            var listDiv = document.getElementById('page-link-list');
            if (!listDiv) return;
            
            listDiv.innerHTML = '';
            
            var displayPages = currentPageId ? pagesList.filter(function(p) { return p.id != currentPageId; }) : pagesList;
            var hasContent = false;
            
            if (displayPages.length > 0) {
                var pagesContent = document.createElement('div');
                pagesContent.className = 'page-link-expando-content';
                pagesContent.setAttribute('role', 'list');

                displayPages.forEach(function(page) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'page-link-item';
                    item.textContent = page.title;
                    item.setAttribute('role', 'listitem');
                    item.onclick = function() {
                        insertPageLink(page);
                        closePageLinkModal();
                    };
                    item.onkeydown = function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            insertPageLink(page);
                            closePageLinkModal();
                        }
                    };
                    pagesContent.appendChild(item);
                });

                var pagesExpando = createExpandoSection('Pages', displayPages.length, pagesContent, true);
                listDiv.appendChild(pagesExpando);
                hasContent = true;
            }

            if (lessonsModules.length > 0) {
                var modulesContent = document.createElement('div');
                modulesContent.className = 'page-link-expando-content';
                modulesContent.setAttribute('role', 'list');

                lessonsModules.forEach(function(moduleItem) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'page-link-item';
                    item.textContent = moduleItem.title;
                    item.setAttribute('role', 'listitem');
                    item.setAttribute('title', moduleItem.url);
                    item.onclick = function() {
                        insertLessonLink(moduleItem);
                        closePageLinkModal();
                    };
                    item.onkeydown = function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            insertLessonLink(moduleItem);
                            closePageLinkModal();
                        }
                    };
                    modulesContent.appendChild(item);
                });

                var modulesExpando = createExpandoSection('Modules', lessonsModules.length, modulesContent, true);
                listDiv.appendChild(modulesExpando);
                hasContent = true;
            }
            
            if (lessonsList.length > 0) {
                var typeGroups = {
                    video: { label: 'Videos', items: [] },
                    discussion: { label: 'Discussions', items: [] },
                    lti: { label: 'LTI & Tools', items: [] },
                    'not-lti': { label: 'LTI & Tools', items: [] },
                    reference: { label: 'References', items: [] },
                    slide: { label: 'Slides', items: [] }
                };

                lessonsList.forEach(function(lessonItem) {
                    var t = lessonItem.type || 'reference';
                    if (t === 'not-lti') t = 'lti';
                    if (typeGroups[t]) typeGroups[t].items.push(lessonItem);
                });

                var ltiItems = (typeGroups.lti ? typeGroups.lti.items : []).concat(typeGroups['not-lti'] ? typeGroups['not-lti'].items : []);
                var groupsToShow = [
                    { label: 'Videos', items: typeGroups.video ? typeGroups.video.items : [] },
                    { label: 'Discussions', items: typeGroups.discussion ? typeGroups.discussion.items : [] },
                    { label: 'LTI & Tools', items: ltiItems },
                    { label: 'References', items: typeGroups.reference ? typeGroups.reference.items : [] },
                    { label: 'Slides', items: typeGroups.slide ? typeGroups.slide.items : [] }
                ];

                groupsToShow.forEach(function(group) {
                    if (!group.items || group.items.length === 0) return;

                    var content = document.createElement('div');
                    content.className = 'page-link-expando-content';
                    content.setAttribute('role', 'list');

                    group.items.forEach(function(lessonItem) {
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'page-link-item';
                        item.textContent = lessonItem.title + (lessonItem.module ? ' (' + lessonItem.module + ')' : '');
                        item.setAttribute('role', 'listitem');
                        item.setAttribute('title', lessonItem.url);
                        item.onclick = function() {
                            insertLessonLink(lessonItem);
                            closePageLinkModal();
                        };
                        item.onkeydown = function(e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                insertLessonLink(lessonItem);
                                closePageLinkModal();
                            }
                        };
                        content.appendChild(item);
                    });

                    var expando = createExpandoSection(group.label, group.items.length, content, true);
                    listDiv.appendChild(expando);
                    hasContent = true;
                });
            }
            
            if (!hasContent) {
                listDiv.innerHTML = '<p role="status">No pages or lesson content available.</p>';
            }
        }

        var pageLinkModalFocusBeforeOpen = null;

        function showPageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            pageLinkModalFocusBeforeOpen = document.activeElement;
            modal.style.display = 'block';
            requestAnimationFrame(function() { modal.classList.add('open'); });
            document.addEventListener('keydown', pageLinkModalKeyHandler);
            var firstFocusable = modal.querySelector('.page-link-item') || modal.querySelector('button');
            if (firstFocusable) {
                firstFocusable.focus();
            } else {
                modal.focus();
            }
        }

        function closePageLinkModal() {
            var modal = document.getElementById('page-link-modal');
            modal.classList.remove('open');
            document.removeEventListener('keydown', pageLinkModalKeyHandler);
            setTimeout(function() {
                modal.style.display = 'none';
                if (pageLinkModalFocusBeforeOpen) {
                    pageLinkModalFocusBeforeOpen.focus();
                }
            }, 250);
        }

        function pageLinkModalKeyHandler(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closePageLinkModal();
            }
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
                    const insertedRange = model.insertContent(textNode, insertPosition);
                    writer.setSelection(insertedRange);
                }
            });
            
            editor.execute('link', url);
        }

        function insertLessonLink(lessonItem) {
            if (!editor) return;
            
            const model = editor.model;
            const selection = model.document.selection;
            const url = lessonItem.url;
            const title = lessonItem.title;
            
            model.change(writer => {
                if (selection.isCollapsed) {
                    const textNode = writer.createText(title);
                    const insertPosition = selection.getFirstPosition();
                    const insertedRange = model.insertContent(textNode, insertPosition);
                    writer.setSelection(insertedRange);
                }
            });
            
            editor.execute('link', url);
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
            button.innerHTML = '<svg class="ck-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; fill: currentColor;" aria-hidden="true"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.15.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>';
            
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
            U::flashError('Title is required');
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
            U::flashSuccess('Page updated successfully');
            return new RedirectResponse($manage_url);
        } else {
            U::flashError('Error updating page');
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
        <main class="container" role="main" id="main-content">
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
                <table class="table table-striped" role="table">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Logical Key</th>
                            <th scope="col">Status</th>
                            <th scope="col">Updated</th>
                            <th scope="col">Actions</th>
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
                                    <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-xs btn-default" aria-label="<?= htmlspecialchars(__('Edit')) ?> <?= htmlspecialchars($page['title']) ?>">Edit</a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to toggle the published status?');">
                                        <input type="hidden" name="action" value="toggle_published">
                                        <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-warning" aria-label="<?= $page['published'] ? 'Unpublish' : 'Publish' ?> <?= htmlspecialchars($page['title']) ?>">
                                            <?= $page['published'] ? 'Unpublish' : 'Publish' ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-danger" aria-label="Delete <?= htmlspecialchars($page['title']) ?>">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
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
                    U::flashSuccess('Page deleted successfully');
                } else {
                    U::flashError('Error deleting page');
                }
            } else {
                U::flashError('Page not found');
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
                U::flashSuccess('Page status updated successfully');
            } else {
                U::flashError('Error updating page status');
            }
        }
        
        return new RedirectResponse($manage_url);
    }

    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }
}
