<?php

namespace Tsugi\Controllers;


use \Tsugi\Util\U;
use Tsugi\Util\CCFileBase;
require_once __DIR__ . '/../UI/CKEditor.php';

use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\UI\LessonsNormalize;

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
        $app->router->get($prefix.'/history/{id}', 'Pages@history');
        $app->router->post($prefix.'/history/restore', 'Pages@historyRestore');
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

    /**
     * Expand canonical FILEBASE URLs for the editor or browser.
     *
     * @param string $html
     * @return string
     */
    private function expandPageHtml($html) {
        return CCFileBase::expand($html, $this->courseFileBaseUrl(self::ROUTE), self::courseLocalPrefixes());
    }

    /**
     * Convert current-course URLs to $IMS-CC-FILEBASE$ before storing HTML.
     *
     * @param string $html
     * @return string
     */
    private function canonicalizePageHtml($html) {
        return CCFileBase::canonicalize($html, $this->courseFileBaseUrl(self::ROUTE), self::courseLocalPrefixes());
    }

    public function index(Request $request, $logical_key = null)
    {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        
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
                    <?= $this->expandPageHtml($page['body']) ?>
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
        
        $context_id = U::currentContextId();
        
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
        $parent = $this->toolParent(self::ROUTE);
        $data = self::lessonsDocumentForPicker();
        return new JsonResponse(self::lessonsLinkPickerPayload(
            is_array($data) ? $data : array(),
            $apphome,
            $parent . '/lessons',
            $parent . '/lessons_launch/',
            $parent . '/launch/'
        ));
    }

    /**
     * Current lessons document for the link picker (manifest, then $CFG->lessons).
     *
     * @return array<string, mixed>|null
     */
    public static function lessonsDocumentForPicker() {
        $doc = Manifest::currentDocument();
        if ( is_array($doc) && isset($doc['json']) && is_string($doc['json']) ) {
            $data = json_decode($doc['json'], true);
            if ( is_array($data) ) {
                return $data;
            }
        }
        return null;
    }

    /**
     * Pages / Lessons / Files picker rows from a lessons document.
     *
     * @param array<string, mixed> $data
     * @return array{items: list<array<string, mixed>>, modules: list<array<string, mixed>>, launches: list<array<string, mixed>>}
     */
    public static function lessonsLinkPickerPayload(array $data, $apphome, $lessons_base, $lessons_launch, $launch_base) {
        $items = array();
        $launches_out = array();
        $top_level_modules = array();
        $apphome = is_string($apphome) ? rtrim($apphome, '/') : '';

        $top_launches = U::get($data, 'launches', array());
        if ( is_array($top_launches) ) {
            foreach ( $top_launches as $launch ) {
                if ( ! is_array($launch) ) {
                    continue;
                }
                $type = U::get($launch, 'type', 'lti');
                $title = U::get($launch, 'title', '');
                $rlid = U::get($launch, 'resource_link_id', '');
                if ( $type !== 'lti' || $title === '' || $rlid === '' ) {
                    continue;
                }
                $launches_out[] = array(
                    'type' => $type,
                    'title' => $title,
                    'url' => $launch_base . rawurlencode($rlid),
                    'resource_link_id' => $rlid,
                    'result' => U::get($launch, 'result', true),
                );
            }
        }

        $modules = U::get($data, 'modules', array());
        if ( ! is_array($modules) ) {
            return array('items' => $items, 'modules' => $top_level_modules, 'launches' => $launches_out);
        }

        foreach ( $modules as $module ) {
            if ( ! is_array($module) ) {
                continue;
            }
            $module_title = U::get($module, 'title', '');
            $module_anchor = U::get($module, 'anchor', '');
            if ( $module_title !== '' && $module_anchor !== '' ) {
                $top_level_modules[] = array(
                    'title' => $module_title,
                    'url' => $lessons_base . '/' . $module_anchor,
                    'anchor' => $module_anchor
                );
            }
            $module_items = U::get($module, 'items', array());
            if ( ! is_array($module_items) ) {
                continue;
            }

            foreach ( $module_items as $item ) {
                if ( ! is_array($item) ) {
                    continue;
                }
                $norm = LessonsNormalize::normalizeItem($item);
                $type = U::get($norm, 'type', '');
                $kind = LessonsNormalize::presentationKind($norm);
                $title = U::get($norm, 'title', '');
                $url = null;

                if ( LessonsNormalize::isHeading($norm) || $type === 'header' ) {
                    continue;
                }

                if ( LessonsNormalize::isLtiLaunch($norm) || $type === 'not-lti' ) {
                    $resource_link_id = U::get($norm, 'resource_link_id', '');
                    if ( $resource_link_id !== '' ) {
                        $url = $lessons_launch . rawurlencode($resource_link_id);
                    }
                } else if ( $kind === 'slide' || $kind === 'reference' || $kind === 'assignment'
                    || $kind === 'solution' || $type === 'web_link' || $type === 'html_page'
                    || $type === 'file' ) {
                    $href = U::get($norm, 'href', '');
                    if ( $href !== '' ) {
                        $url = str_replace('{apphome}', $apphome, $href);
                    }
                } else if ( $kind === 'video' ) {
                    $href = U::get($norm, 'href', '');
                    $youtube_id = trim(U::get($norm, 'youtube', ''));
                    if ( $href !== '' ) {
                        $url = str_replace('{apphome}', $apphome, $href);
                    } else if ( $youtube_id !== '' ) {
                        $url = 'https://www.youtube.com/watch?v=' . $youtube_id;
                    }
                }

                if ( $url !== null && $url !== '' && $title !== '' ) {
                    $item_data = array(
                        'title' => $title,
                        'url' => $url,
                        'module' => $module_title,
                        'module_anchor' => $module_anchor,
                        'type' => $kind !== '' ? $kind : $type
                    );
                    if ( $kind === 'video' ) {
                        $item_data['target_blank'] = true;
                    }
                    $items[] = $item_data;
                }
            }
        }

        return array('items' => $items, 'modules' => $top_level_modules, 'launches' => $launches_out);
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
                <?= self::csrfField() ?>
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
        <?php $files_home = $this->controllerUrl(\Tsugi\Controllers\Files::ROUTE, self::ROUTE); ?>
        var pagesJsonUrl = '<?= $json_url ?>';
        var lessonsJsonUrl = '<?= $lessons_json_url ?>';
        var filesJsonUrl = '<?= htmlspecialchars($files_home . '/json') ?>';
        var filesBase = '<?= htmlspecialchars($files_home) ?>';
        var pagesBase = '<?= htmlspecialchars($pages_base) ?>';
        var appHome = '<?= htmlspecialchars(isset($apphome) ? $apphome : '') ?>';
        var currentPageId = <?= isset($current_page_id) ? (int)$current_page_id : 'null' ?>;

        <?php \Tsugi\UI\CKEditor::renderConfigScript(); ?>

        <?php \Tsugi\UI\CKEditor::renderLinkPickerScript(); ?>
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
        $csrf = self::requireCsrf($add_url);
        if ( $csrf ) {
            return $csrf;
        }
        
        LTIX::getConnection();
        
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        
        $title = trim(U::get($_POST, 'title'));
        $body = $this->canonicalizePageHtml(U::get($_POST, 'body', ''));
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
        
        $context_id = U::currentContextId();
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
                <?= self::csrfField() ?>
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars(U::get($_POST, 'title', $page['title'])) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="body">Body:</label>
                    <div class="ckeditor-container">
                        <textarea name="body" id="editor_body"><?= htmlspecialchars(U::get($_POST, 'body', $this->expandPageHtml($page['body']))) ?></textarea>
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
        <?php $files_home = $this->controllerUrl(\Tsugi\Controllers\Files::ROUTE, self::ROUTE); ?>
        var pagesJsonUrl = '<?= $json_url ?>';
        var lessonsJsonUrl = '<?= $lessons_json_url ?>';
        var filesJsonUrl = '<?= htmlspecialchars($files_home . '/json') ?>';
        var filesBase = '<?= htmlspecialchars($files_home) ?>';
        var pagesBase = '<?= htmlspecialchars($pages_base) ?>';
        var appHome = '<?= htmlspecialchars(isset($apphome) ? $apphome : '') ?>';
        var currentPageId = <?= isset($current_page_id) ? (int)$current_page_id : 'null' ?>;

        <?php \Tsugi\UI\CKEditor::renderConfigScript(); ?>

        <?php \Tsugi\UI\CKEditor::renderLinkPickerScript(); ?>
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
        $csrf = self::requireCsrf($manage_url);
        if ( $csrf ) {
            return $csrf;
        }
        
        LTIX::getConnection();
        
        $context_id = U::currentContextId();
        $page_id = intval($id);
        
        $title = trim(U::get($_POST, 'title'));
        $body = $this->canonicalizePageHtml(U::get($_POST, 'body', ''));
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

        // Fetch current page to check if content changed (for history)
        $current = $PDOX->rowDie(
            "SELECT title, body FROM {$CFG->dbprefix}pages WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        $content_changed = ($current && ($current['title'] !== $title || $current['body'] !== $body));
        
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
            // Save previous version to history only when content (title or body) actually changed
            if ($content_changed && $current) {
                $PDOX->queryDie(
                    "INSERT INTO {$CFG->dbprefix}page_history (page_id, title, body) VALUES (:PID, :title, :body)",
                    array(':PID' => $page_id, ':title' => $current['title'], ':body' => $current['body'])
                );
                // Trim to last 5 per page (delete oldest)
                $ids = $PDOX->allRowsDie(
                    "SELECT history_id FROM {$CFG->dbprefix}page_history WHERE page_id = :PID ORDER BY saved_at DESC",
                    array(':PID' => $page_id)
                );
                if (count($ids) > 5) {
                    $to_delete = array_slice($ids, 5);
                    foreach ($to_delete as $row) {
                        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}page_history WHERE history_id = :HID", array(':HID' => $row['history_id']));
                    }
                }
            }
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
        
        $context_id = U::currentContextId();
        
        // Get page_ids that have history (for showing History button)
        // Join via pages to scope to this context — context_id was removed from page_history (redundant via FK)
        $pages_with_history = $PDOX->allRowsDie(
            "SELECT DISTINCT ph.page_id FROM {$CFG->dbprefix}page_history ph
             JOIN {$CFG->dbprefix}pages p ON ph.page_id = p.page_id
             WHERE p.context_id = :CID",
            array(':CID' => $context_id)
        );
        $page_ids_with_history = array_column($pages_with_history, 'page_id');
        
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
                                    <?php if (in_array($page['page_id'], $page_ids_with_history)): ?>
                                    <?php $history_url = $tool_home . '/history/' . $page['page_id']; ?>
                                    <a href="<?= htmlspecialchars($history_url) ?>" class="btn btn-xs btn-info" aria-label="<?= htmlspecialchars(__('History')) ?> <?= htmlspecialchars($page['title']) ?>">History</a>
                                    <?php endif; ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to toggle the published status?');">
                                        <?= self::csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_published">
                                        <input type="hidden" name="page_id" value="<?= $page['page_id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-warning" aria-label="<?= $page['published'] ? 'Unpublish' : 'Publish' ?> <?= htmlspecialchars($page['title']) ?>">
                                            <?= $page['published'] ? 'Unpublish' : 'Publish' ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                        <?= self::csrfField() ?>
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
        $csrf = self::requireCsrf($manage_url);
        if ( $csrf ) {
            return $csrf;
        }
        
        LTIX::getConnection();
        
        $context_id = U::currentContextId();
        
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

    public function history(Request $request, $id)
    {
        global $CFG, $OUTPUT, $PDOX;

        $this->requireInstructor('/pages');

        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $page_id = intval($id);

        $page = $PDOX->rowDie(
            "SELECT page_id, title, body, logical_key, updated_at FROM {$CFG->dbprefix}pages 
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        if (!$page) {
            U::flashError('Page not found');
            return new RedirectResponse($manage_url);
        }

        $histories = $PDOX->allRowsDie(
            "SELECT history_id, title, body, saved_at FROM {$CFG->dbprefix}page_history 
             WHERE page_id = :PID ORDER BY saved_at DESC",
            array(':PID' => $page_id)
        );

        if (count($histories) == 0) {
            U::flashError('No history for this page');
            return new RedirectResponse($manage_url);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" role="main" id="main-content">
            <h1>Page History: <?= htmlspecialchars($page['title']) ?>
                <span class="pull-right">
                    <a href="<?= $manage_url ?>" class="btn btn-default">Back to Manage</a>
                    <a href="<?= $tool_home . '/edit/' . $page_id ?>" class="btn btn-primary">Edit Page</a>
                </span>
            </h1>

            <p>Current version is shown below. Restore a previous version to make it the current page (current will be saved to history).</p>

            <table class="table table-striped" role="table">
                <thead>
                    <tr>
                        <th scope="col">Saved At</th>
                        <th scope="col">Title</th>
                        <th scope="col">Chars</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $currentCharCount = strlen($page['title'] ?? '') + strlen($page['body'] ?? ''); ?>
                    <tr class="info">
                        <td><?= date('Y-m-d H:i', strtotime($page['updated_at'])) ?></td>
                        <td><?= htmlspecialchars($page['title']) ?> <span class="label label-info">Current</span></td>
                        <td><?= number_format($currentCharCount) ?></td>
                        <td><em>—</em></td>
                    </tr>
                    <?php foreach ($histories as $h): ?>
                        <?php $charCount = strlen($h['title'] ?? '') + strlen($h['body'] ?? ''); ?>
                        <tr>
                            <td><?= date('Y-m-d H:i', strtotime($h['saved_at'])) ?></td>
                            <td><?= htmlspecialchars($h['title']) ?></td>
                            <td><?= number_format($charCount) ?></td>
                            <td>
                                <button type="button" class="btn btn-xs btn-default btn-diff" data-history-id="<?= (int)$h['history_id'] ?>"
                                        data-title="<?= htmlspecialchars($h['title']) ?>"
                                        data-body="<?= htmlspecialchars($h['body']) ?>">View / Diff</button>
                                <form method="post" action="<?= $tool_home ?>/history/restore" style="display: inline;"
                                      onsubmit="return confirm('Restore this version? The current version will be saved to history.');">
                                    <?= self::csrfField() ?>
                                    <input type="hidden" name="page_id" value="<?= $page_id ?>">
                                    <input type="hidden" name="history_id" value="<?= (int)$h['history_id'] ?>">
                                    <button type="submit" class="btn btn-xs btn-primary">Restore</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>

        <div id="diff-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="diff-modal-label">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h2 class="modal-title" id="diff-modal-label">Compare with current</h2>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <h3>This version (saved date shown)</h3>
                                <div id="diff-left" class="diff-panel"></div>
                            </div>
                            <div class="col-sm-6">
                                <h3>Current version</h3>
                                <div id="diff-right" class="diff-panel"></div>
                            </div>
                        </div>
                        <hr>
                        <h3>Text diff</h3>
                        <div id="diff-output" class="diff-output"></div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .diff-panel { max-height: 200px; overflow: auto; border: 1px solid #ccc; padding: 8px; background: #f9f9f9; }
        .diff-output { max-height: 300px; overflow: auto; border: 1px solid #ccc; padding: 8px; font-family: monospace; font-size: 12px; }
        .diff-add { background: #dfd; }
        .diff-remove { background: #fdd; }
        </style>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jsdiff/5.1.0/diff.min.js"></script>
        <script>
        (function() {
            var currentTitle = <?= json_encode($page['title']) ?>;
            var currentBody = <?= json_encode($page['body']) ?>;
            document.querySelectorAll('.btn-diff').forEach(function(btn) {
                btn.onclick = function() {
                    var histTitle = btn.getAttribute('data-title');
                    var histBody = btn.getAttribute('data-body');
                    var leftPlain = htmlToPlainLines(histTitle + '\n\n' + histBody);
                    var rightPlain = htmlToPlainLines(currentTitle + '\n\n' + currentBody);
                    document.getElementById('diff-left').innerHTML = '<strong>' + escapeHtml(histTitle) + '</strong><hr>' + stripTags(histBody);
                    document.getElementById('diff-right').innerHTML = '<strong>' + escapeHtml(currentTitle) + '</strong><hr>' + stripTags(currentBody);
                    var diff = Diff.diffLines(leftPlain, rightPlain);
                    var html = '';
                    diff.forEach(function(part) {
                        var cls = part.added ? 'diff-add' : (part.removed ? 'diff-remove' : '');
                        var text = escapeHtml(part.value);
                        if (part.added) text = '<ins>' + text + '</ins>';
                        else if (part.removed) text = '<del>' + text + '</del>';
                        html += '<div class="' + cls + '">' + text.replace(/\n/g, '<br>') + '</div>';
                    });
                    document.getElementById('diff-output').innerHTML = html || '(no changes)';
                    $('#diff-modal').modal('show');
                };
            });
            function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
            function stripTags(s) { var d = document.createElement('div'); d.innerHTML = s; return d.textContent || ''; }
            function htmlToPlainLines(html) {
                if (!html) return '';
                var s = html.replace(/<\/(p|div|h[1-6]|li|tr|blockquote)>/gi, '\n')
                    .replace(/<(br|hr)\s*\/?>/gi, '\n');
                var d = document.createElement('div');
                d.innerHTML = s;
                var text = (d.textContent || d.innerText || '');
                return text.split('\n').map(function(l) { return l.replace(/\s+/g, ' ').trim(); }).filter(Boolean).join('\n');
            }
        })();
        </script>
        <?php
        $OUTPUT->footer();
    }

    public function historyRestore(Request $request)
    {
        global $CFG, $PDOX;

        $this->requireInstructor('/pages');

        $tool_home = $this->toolHome(self::ROUTE);
        $manage_url = $tool_home . '/manage';
        $csrf = self::requireCsrf($manage_url);
        if ( $csrf ) {
            return $csrf;
        }

        LTIX::getConnection();

        $context_id = U::currentContextId();
        $page_id = (int) U::get($_POST, 'page_id');
        $history_id = (int) U::get($_POST, 'history_id');

        if (!$page_id || !$history_id) {
            U::flashError('Invalid restore request');
            return new RedirectResponse($manage_url);
        }

        $page = $PDOX->rowDie(
            "SELECT page_id, title, body, logical_key FROM {$CFG->dbprefix}pages 
             WHERE page_id = :PID AND context_id = :CID",
            array(':PID' => $page_id, ':CID' => $context_id)
        );
        $hist = $PDOX->rowDie(
            "SELECT history_id, title, body FROM {$CFG->dbprefix}page_history 
             WHERE history_id = :HID AND page_id = :PID",
            array(':HID' => $history_id, ':PID' => $page_id)
        );
        if (!$page || !$hist) {
            U::flashError('Page or history entry not found');
            return new RedirectResponse($manage_url);
        }

        $logical_key = $this->generateLogicalKey($hist['title']);

        $PDOX->beginTransaction();
        try {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}page_history (page_id, title, body) VALUES (:PID, :title, :body)",
                array(':PID' => $page_id, ':title' => $page['title'], ':body' => $page['body'])
            );
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}pages SET title = :title, logical_key = :key, body = :body, updated_at = NOW() WHERE page_id = :PID AND context_id = :CID",
                array(':title' => $hist['title'], ':key' => $logical_key, ':body' => $this->canonicalizePageHtml($hist['body']), ':PID' => $page_id, ':CID' => $context_id)
            );
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}page_history WHERE history_id = :HID",
                array(':HID' => $history_id)
            );
            $ids = $PDOX->allRowsDie(
                "SELECT history_id FROM {$CFG->dbprefix}page_history WHERE page_id = :PID ORDER BY saved_at DESC",
                array(':PID' => $page_id)
            );
            if (count($ids) > 5) {
                foreach (array_slice($ids, 5) as $row) {
                    $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}page_history WHERE history_id = :HID", array(':HID' => $row['history_id']));
                }
            }
            $PDOX->commit();
        } catch (\Exception $e) {
            $PDOX->rollBack();
            U::flashError('Error restoring: ' . $e->getMessage());
            return new RedirectResponse($manage_url);
        }

        U::flashSuccess('Page restored successfully');
        return new RedirectResponse($manage_url);
    }

    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }
}
