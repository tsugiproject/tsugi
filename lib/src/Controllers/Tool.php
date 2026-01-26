<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;

/**
 * Base class for LMS tool controllers
 * 
 * Provides common functionality for controllers that need LMS utilities
 * like instructor checking, analytics recording, etc.
 */
abstract class Tool {

    /**
     * Get the base path for this tool (e.g., '/py4e/pages' or '/py4e/12345/pages')
     * 
     * This method determines where the tool is actually mounted by examining
     * the current request URI and finding the tool's route within it.
     * 
     * @param string $route The route constant for this tool (e.g., '/pages', '/announcements')
     * @return string The base path including the route (e.g., '/py4e/pages')
     */
    protected function toolHome($route) {
        // Get the current request URI
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Remove query string if present
        $requestUri = strtok($requestUri, '?');
        
        // Find where the route appears in the URI
        $routePos = strpos($requestUri, $route);
        
        if ($routePos !== false) {
            // Return everything up to and including the route
            return substr($requestUri, 0, $routePos + strlen($route));
        }
        
        // Fallback: if route not found, try using rest_path
        $path = U::rest_path();
        if ($path && isset($path->parent)) {
            return $path->parent . $route;
        }
        
        // Last resort: use apphome (for backward compatibility)
        global $CFG;
        return isset($CFG->apphome) ? $CFG->apphome . $route : $route;
    }

    /**
     * Get the parent path one level above the tool home
     * 
     * This is useful for shared resources like static files that are mounted
     * at a common path above individual controllers.
     * 
     * @param string $route The route constant for this tool (e.g., '/pages', '/announcements')
     * @return string The parent path (e.g., '/py4e' if toolHome is '/py4e/pages')
     */
    protected function toolParent($route = null) {
        // If no route provided, try to determine from current context
        if ($route === null) {
            // Try to get route from the class constant if it exists
            $reflection = new \ReflectionClass($this);
            if ($reflection->hasConstant('ROUTE')) {
                $route = $reflection->getConstant('ROUTE');
            } else {
                // Fallback: use apphome
                global $CFG;
                $apphome = isset($CFG->apphome) ? $CFG->apphome : '/';
                // Normalize: return empty string if root to avoid double slashes in URL construction
                return ($apphome === '/') ? '' : $apphome;
            }
        }
        
        $toolHome = $this->toolHome($route);
        
        // Remove the route from the end to get parent
        if (substr($toolHome, -strlen($route)) === $route) {
            $parent = substr($toolHome, 0, -strlen($route));
            // Remove trailing slash
            $parent = rtrim($parent, '/');
            // Normalize: return empty string if root to avoid double slashes in URL construction
            return ($parent === '') ? '' : ($parent ?: '');
        }
        
        // Fallback: use apphome
        global $CFG;
        $apphome = isset($CFG->apphome) ? $CFG->apphome : '/';
        // Normalize: return empty string if root to avoid double slashes in URL construction
        return ($apphome === '/') ? '' : $apphome;
    }

    /**
     * Generate URL to another controller
     * 
     * @param string $controllerRoute The route of the target controller (e.g., '/pages', '/announcements')
     * @param string|null $currentRoute Optional current route (defaults to detecting from context)
     * @return string Full URL to the controller
     */
    protected function controllerUrl($controllerRoute, $currentRoute = null) {
        $parent = $this->toolParent($currentRoute);
        return $parent . $controllerRoute;
    }

    /**
     * Static helper to determine the parent path dynamically from the request
     * 
     * This is useful for static methods that need to determine the base path
     * without having a Tool instance. It uses the same logic as toolParent()
     * but works as a static method.
     * 
     * @param string|null $route Optional route to look for (if null, tries to detect from request)
     * @return string The parent path (e.g., '/py4e' or '/ca4e/tsugi')
     */
    public static function determineParentPath($route = null) {
        global $CFG;
        
        // Get the current request URI
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $requestUri = strtok($requestUri, '?');
        
        if ($route !== null) {
            // If route provided, find it in the URI and extract parent
            $routePos = strpos($requestUri, $route);
            if ($routePos !== false) {
                $parentPath = substr($requestUri, 0, $routePos);
                $parentPath = rtrim($parentPath, '/');
                // Normalize: return empty string if root to avoid double slashes in URL construction
                return ($parentPath === '') ? '' : ($parentPath ?: '');
            }
        } else {
            // Try to detect by looking for common controller routes
            $controllerRoutes = ['/announcements', '/pages', '/badges', '/grades', '/lessons', '/assignments', '/map', '/login', '/logout'];
            foreach ($controllerRoutes as $testRoute) {
                $routePos = strpos($requestUri, $testRoute);
                if ($routePos !== false) {
                    $parentPath = substr($requestUri, 0, $routePos);
                    $parentPath = rtrim($parentPath, '/');
                    // Normalize: return empty string if root to avoid double slashes in URL construction
                    return ($parentPath === '') ? '' : ($parentPath ?: '');
                }
            }
        }
        
        // Fallback: try using rest_path
        $path = U::rest_path();
        if ($path && isset($path->parent)) {
            $parent = $path->parent;
            // Normalize: return empty string if root
            return ($parent === '/') ? '' : $parent;
        }
        
        // Last resort: use apphome
        $apphome = isset($CFG->apphome) ? $CFG->apphome : '/';
        // Normalize: return empty string if root to avoid double slashes in URL construction
        return ($apphome === '/') ? '' : $apphome;
    }

    /**
     * Check if the current logged-in user is an instructor/admin for the current context
     * 
     * This method checks:
     * 1. If user is site admin (via isAdmin())
     * 2. If user has instructor role or role_override in lti_membership table
     * 3. If user owns the context or its key
     * 
     * Reads context_id and user_id from $_SESSION['context_id'] and $_SESSION['id'].
     * Role values are cached in $_SESSION['role'][$context_id] as the maximum of role and role_override.
     * Context/key ownership grants instructor status and is cached as ROLE_INSTRUCTOR.
     * 
     * @return bool True if user is instructor/admin for the context, false otherwise
     */
    protected function isInstructor() {
        global $CFG, $PDOX;
        
        // Get context_id and user_id from session
        $context_id = U::get($_SESSION, 'context_id');
        $user_id = U::get($_SESSION, 'id');
        
        // If context_id or user_id not in session, user is not an instructor
        if ( ! $context_id || ! $user_id ) {
            return false;
        }
        
        // Check if user is site admin (always true for admins, no need to cache)
        if ( $this->isAdmin() ) {
            return true;
        }
        
        // Initialize role cache if not set
        if ( ! isset($_SESSION['role']) ) {
            $_SESSION['role'] = array();
        }
        
        // Check cache first - if cached, use the cached role value to determine instructor status
        if ( isset($_SESSION['role'][$context_id]) ) {
            $cached_role = $_SESSION['role'][$context_id];
            // Check if cached role indicates instructor (>= ROLE_INSTRUCTOR)
            if ( $cached_role >= LTIX::ROLE_INSTRUCTOR ) {
                return true;
            }
            // If cached role < INSTRUCTOR, user is not an instructor (ownership already checked)
            return false;
        }
        
        $max_role = 0;
        $is_instructor = false;
        
        // Check if user is instructor/admin for this context via membership
        $membership = $PDOX->rowDie(
            "SELECT role, role_override FROM {$CFG->dbprefix}lti_membership 
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( $membership ) {
            $role = isset($membership['role']) ? ($membership['role'] + 0) : 0;
            $role_override = isset($membership['role_override']) ? ($membership['role_override'] + 0) : 0;
            // Cache the maximum role value
            $max_role = max($role, $role_override);
            // ROLE_INSTRUCTOR = 1000, ROLE_ADMINISTRATOR = 5000
            if ( $max_role >= LTIX::ROLE_INSTRUCTOR ) {
                $is_instructor = true;
            }
        }
        
        // Also check if user owns the context or its key
        // Context/key ownership grants instructor role and should be cached as ROLE_INSTRUCTOR
        if ( ! $is_instructor ) {
            $context_check = $PDOX->rowDie(
                "SELECT context_id FROM {$CFG->dbprefix}lti_context
                 WHERE context_id = :CID AND (
                     key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                     OR user_id = :UID
                 )",
                array(':CID' => $context_id, ':UID' => $user_id)
            );
            if ( $context_check ) {
                $is_instructor = true;
                // Cache ownership as ROLE_INSTRUCTOR since it grants instructor status
                $max_role = LTIX::ROLE_INSTRUCTOR;
            }
        }
        
        // Cache the role value
        // This caches either the membership role or ROLE_INSTRUCTOR if granted via ownership
        $_SESSION['role'][$context_id] = $max_role;
        
        return $is_instructor;
    }

    /**
     * Check if the current user is a site administrator
     * 
     * @return bool True if user is admin, false otherwise
     */
    protected function isAdmin() {
        return isset( $_SESSION['admin']) && $_SESSION['admin'] == 'yes';
    }

    /**
     * Check authentication and context
     * 
     * @throws \Exception If user is not logged in or context is missing
     */
    protected function requireAuth() {
        if ( ! U::get($_SESSION, 'id') ) {
            die('Must be logged in');
        }
        if ( ! isset($_SESSION['context_id']) ) {
            die('Context required');
        }
    }

    /**
     * Check if user is instructor/admin, redirect if not
     * 
     * @param string $redirectUrl URL to redirect to if not authorized
     * @return bool True if user is instructor/admin, false otherwise (and redirects)
     */
    protected function requireInstructor($redirectUrl = null) {
        $this->requireAuth();
        if ( ! $this->isInstructor() ) {
            $_SESSION['error'] = "You must be an administrator or instructor for this context";
            if ( $redirectUrl === null ) {
                // Try to determine redirect URL from current route
                $redirectUrl = '/';
            }
            header('Location: ' . U::addSession($redirectUrl));
            exit();
        }
        return true;
    }

    /**
     * Build a stable path identifier for LMS tools (no query/session params).
     *
     * Examples:
     * - /lms/pages
     * - /lms/assignments
     *
     * @return string
     */
    protected function lmsAnalyticsPath() {
        // We want tool-level analytics, not per-resource within a tool:
        // ignore PATH_INFO and query parameters; use just the tool folder.
        $script = U::get($_SERVER, 'SCRIPT_NAME');
        if ( ! $script ) $script = U::get($_SERVER, 'PHP_SELF');
        if ( ! $script ) return '/';

        $dir = dirname($script);
        if ( ! $dir || $dir == '.' ) $dir = '/';

        // Normalize trailing slash (but keep root)
        if ( $dir && $dir != '/' ) $dir = rtrim($dir, '/');
        return $dir;
    }

    /**
     * Build a stable key for LMS analytics, distinct from LTI resource_link_id values.
     *
     * @param string|null $path
     * @return string
     */
    protected function lmsAnalyticsKey($path=null) {
        if ( $path === null ) $path = $this->lmsAnalyticsPath();
        return 'lms:' . $path;
    }

    /**
     * Ensure there is an `lti_link` row for a local LMS page/tool and return link_id.
     *
     * We intentionally store LMS "tools" as synthetic `lti_link` records scoped to the
     * already-selected `lti_context`, so we can reuse the existing analytics tables
     * (`lti_link_activity`, `lti_link_user_activity`) without schema changes.
     *
     * @param int $context_id
     * @param string $link_key
     * @param string|null $title
     * @param string|null $path
     * @return int|false
     */
    protected function lmsEnsureAnalyticsLink($context_id, $link_key, $title=null, $path=null) {
        global $CFG, $PDOX;

        if ( ! $context_id || ! $link_key ) return false;

        $sha = function_exists('lti_sha256') ? lti_sha256($link_key) : hash('sha256', $link_key);

        $row = $PDOX->rowDie(
            "SELECT link_id, title, path
             FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_sha256 = :SHA",
            array(':CID' => $context_id, ':SHA' => $sha)
        );

        if ( ! $row ) {
            // link_key is required (TEXT NOT NULL) - we store our prefixed key
            $stmt = $PDOX->queryReturnError(
                "INSERT IGNORE INTO {$CFG->dbprefix}lti_link
                    (link_sha256, link_key, context_id, title, path, updated_at)
                 VALUES
                    (:SHA, :KEY, :CID, :TITLE, :PATH, NOW())",
                array(
                    ':SHA' => $sha,
                    ':KEY' => $link_key,
                    ':CID' => $context_id,
                    ':TITLE' => $title,
                    ':PATH' => $path
                )
            );
            if ( ! $stmt->success ) {
                error_log("Unable to create LMS analytics link context=".$context_id." key=".$link_key);
                return false;
            }

            $row = $PDOX->rowDie(
                "SELECT link_id, title, path
                 FROM {$CFG->dbprefix}lti_link
                 WHERE context_id = :CID AND link_sha256 = :SHA",
                array(':CID' => $context_id, ':SHA' => $sha)
            );
            if ( ! $row ) return false;
        }

        // Keep title/path reasonably fresh (best-effort)
        $new_title = U::isNotEmpty($title) ? $title : null;
        $new_path = U::isNotEmpty($path) ? $path : null;
        $need_update = false;
        if ( $new_title !== null && $new_title != U::get($row,'title') ) $need_update = true;
        if ( $new_path !== null && $new_path != U::get($row,'path') ) $need_update = true;
        if ( $need_update ) {
            $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_link
                 SET title = COALESCE(:TITLE, title),
                     path = COALESCE(:PATH, path),
                     updated_at = NOW()
                 WHERE link_id = :LID",
                array(
                    ':TITLE' => $new_title,
                    ':PATH' => $new_path,
                    ':LID' => $row['link_id']
                )
            );
        }

        return $row['link_id'] + 0;
    }

    /**
     * Record LMS page/tool "launch" into Tsugi internal analytics, mirroring LTIX behavior.
     *
     * - Only records for learners (i.e., not instructors/admins)
     * - Stores data in lti_link_activity / lti_link_user_activity with event=0
     *
     * @param string $analytics_path Required stable path for this tool (e.g., '/lms/pages')
     * @param string|null $title Optional display title for this synthetic link
     * @return int|false The link_id if successful, false otherwise
     */
    protected function lmsRecordLaunchAnalytics($analytics_path, $title=null) {
        global $CFG, $PDOX;

        if ( ! isset($CFG->launchactivity) || ! $CFG->launchactivity ) return false;
        if ( ! U::get($_SESSION,'id') || ! U::get($_SESSION,'context_id') ) return false;
        if ( $this->isInstructor() ) return false; // mirror LTIX: only learner launches are logged

        // Ensure DB connection
        if ( ! isset($PDOX) ) {
            LTIX::getConnection();
        }

        $context_id = $_SESSION['context_id'] + 0;
        $user_id = $_SESSION['id'] + 0;

        $link_key = $this->lmsAnalyticsKey($analytics_path);

        $link_id = $this->lmsEnsureAnalyticsLink($context_id, $link_key, $title, $analytics_path);

        if ( ! $link_id ) return false;

        // ---- Link activity (all users) ----
        $row = $PDOX->rowDie(
            "SELECT link_count, activity
             FROM {$CFG->dbprefix}lti_link_activity
             WHERE link_id = :LID AND event = 0",
            array(':LID' => $link_id)
        );

        if ( ! $row ) {
            $PDOX->queryReturnError(
                "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_activity
                    (link_id, event, link_count, updated_at)
                 VALUES
                    (:LID, 0, 0, NOW())",
                array(':LID' => $link_id)
            );
            $row = array('link_count' => 0, 'activity' => null);
        }

        $ent = new \Tsugi\Event\Entry();
        if ( U::get($row,'activity') ) $ent->deSerialize($row['activity']);
        $ent->total = (U::get($row,'link_count',0) + 0);
        $ent->click();
        $activity = $ent->serialize(LTIX::MAX_ACTIVITY);
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link_activity
             SET activity = :ACT, updated_at = NOW(), link_count = link_count + 1
             WHERE link_id = :LID AND event = 0",
            array(':ACT' => $activity, ':LID' => $link_id)
        );

        // ---- User activity (per-user) ----
        $urow = $PDOX->rowDie(
            "SELECT link_user_count, activity
             FROM {$CFG->dbprefix}lti_link_user_activity
             WHERE link_id = :LID AND user_id = :UID AND event = 0",
            array(':LID' => $link_id, ':UID' => $user_id)
        );

        if ( ! $urow ) {
            $PDOX->queryReturnError(
                "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_user_activity
                    (link_id, user_id, event, link_user_count, updated_at)
                 VALUES
                    (:LID, :UID, 0, 0, NOW())",
                array(':LID' => $link_id, ':UID' => $user_id)
            );
            $urow = array('link_user_count' => 0, 'activity' => null);
        }

        $uent = new \Tsugi\Event\Entry();
        if ( U::get($urow,'activity') ) $uent->deSerialize($urow['activity']);
        $uent->total = (U::get($urow,'link_user_count',0) + 0);
        $uent->click();
        $uactivity = $uent->serialize(LTIX::MAX_ACTIVITY);
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link_user_activity
             SET activity = :ACT, updated_at = NOW(), link_user_count = link_user_count + 1
             WHERE link_id = :LID AND user_id = :UID AND event = 0",
            array(':ACT' => $uactivity, ':LID' => $link_id, ':UID' => $user_id)
        );

        return $link_id;
    }

    /**
     * Display analytics for an LMS tool
     * 
     * Automatically derives back_url and stable_path from the route.
     * 
     * @param string $route The route constant (e.g., '/pages', '/announcements')
     * @param string|null $title Optional display title (defaults to route without leading slash, capitalized)
     * @return string Empty string (outputs HTML directly)
     */
    protected function showAnalytics($route, $title = null) {
        global $CFG, $OUTPUT, $PDOX;
        
        $this->requireAuth();
        
        if (!$this->isInstructor() && !$this->isAdmin()) {
            die('Not authorized');
        }
        
        LTIX::getConnection();
        
        // Derive values from route
        $stable_path = $route;
        $tool_home = $this->toolHome($route);
        $back_url = $tool_home;
        
        // Derive title if not provided (remove leading slash and capitalize)
        if ($title === null) {
            $title = ucfirst(ltrim($route, '/'));
        }
        
        // Derive tool_name from route (remove leading slash)
        $tool_name = ltrim($route, '/');
        
        $context_id = $_SESSION['context_id'] + 0;
        
        // Compute analytics link
        $link_key = $this->lmsAnalyticsKey($stable_path);
        $analytics_link_id = $this->lmsEnsureAnalyticsLink($context_id, $link_key, $title, $stable_path);
        
        if (!$analytics_link_id) {
            die_with_error_log('Unable to locate analytics link');
        }
        
        // Build analytics URL relative to the controller's base path (same pattern as Pages.php)
        // This handles cases like /py4e/lessons, /py4e/12345/lessons, /lessons, etc.
        // Use toolHome() to get the full path, then append /api/analytics_cookie.php
        $analytics_url = $CFG->wwwroot . '/api/analytics_cookie.php?link_id=' . $analytics_link_id;
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <p>
                <a href="<?= U::addSession($back_url) ?>" class="btn btn-default">Back</a>
            </p>
            <h1>Analytics: <?= htmlspecialchars($title) ?></h1>
            <?= \Tsugi\UI\Analytics::graphBody() ?>
        </div>
        <?php
        $OUTPUT->footerStart();
        echo(\Tsugi\UI\Analytics::graphScript($analytics_url));
        $OUTPUT->footerEnd();
        
        return "";
    }

    /**
     * Generate URL for a controller-specific static file (JS, CSS, etc.)
     * 
     * Static files are served from tsugi/lib/src/Controllers/static/{ControllerName}/
     * This method generates the proper URL for accessing these static files.
     * 
     * @param string $filename Static filename (e.g., 'tsugi-announce.js')
     * @param string|null $controllerName Optional controller name (defaults to current controller)
     * @return string Full URL to the static file
     */
    protected function staticUrl($filename, $controllerName = null)
    {
        // If controller name not provided, derive it from the class name
        if ($controllerName === null) {
            $className = get_class($this);
            $parts = explode('\\', $className);
            $controllerName = end($parts);
        }
        
        // Use toolParent to determine the base path for static files
        $parentPath = $this->toolParent();
        // Normalize: if parent is "/", use empty string to avoid double slashes
        if ($parentPath === '/') {
            $parentPath = '';
        }
        $basePath = $parentPath . '/static';
        
        return \Tsugi\Controllers\StaticFiles::url($controllerName, $filename, $basePath);
    }
    
    /**
     * Alias for staticUrl() for backward compatibility
     * 
     * @deprecated Use staticUrl() instead
     */
    protected function assetUrl($filename, $controllerName = null)
    {
        return $this->staticUrl($filename, $controllerName);
    }

    /**
     * Render a template file using the standard naming convention
     * 
     * Templates are located at: templates/{ControllerName}/{template_name}.inc.php
     * The controller name is automatically derived from the current class name.
     * 
     * Example:
     *   In Lessons.php: $this->renderTemplate('author_interface', ['lessons_title' => '...', ...])
     *   Will include: templates/Lessons/author_interface.inc.php
     * 
     * @param string $templateName Template name without extension (e.g., 'author_interface')
     * @param array $variables Associative array of variables to extract for the template
     * @return string The rendered template output
     */
    protected function renderTemplate($templateName, array $variables = [])
    {
        // Derive controller name from class name
        $className = get_class($this);
        $parts = explode('\\', $className);
        $controllerName = end($parts);
        
        // Build template path using convention: templates/{ControllerName}/{template_name}.inc.php
        $templatePath = __DIR__ . '/templates/' . $controllerName . '/' . $templateName . '.inc.php';
        
        // Check if template exists
        if (!file_exists($templatePath)) {
            error_log("Template not found: {$templatePath}");
            return '<p>Error: Template not found</p>';
        }
        
        // Extract variables for template use
        extract($variables);
        
        // Capture output
        ob_start();
        include($templatePath);
        return ob_get_clean();
    }
}
