<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Cache;
use \Tsugi\Core\Context;
use \Tsugi\Core\ContextImages;
use \Tsugi\Core\Manifest;
use \Tsugi\Core\User;
use \Tsugi\UI\Output;
use \Tsugi\Util\U;

class Courses extends Tool {

    const ROUTE = '/courses';

    /** Courses shown in the site-menu flyout (fits on screen; no scrollbar). */
    const FLYOUT_LIMIT = 5;

    /** Consumer key used by Google site login. */
    const GOOGLE_KEY = 'google.com';

    /** @var bool Guard against re-dispatch loops. */
    private static $dispatchingNested = false;

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/json', function(Request $request) use ($app) {
            return Courses::getjson($app);
        });
        $app->router->get($prefix.'/create', function(Request $request) use ($app) {
            return Courses::createForm($app, $request);
        });
        $app->router->post($prefix.'/create', function(Request $request) use ($app) {
            return Courses::createPost($app, $request);
        });
        $app->router->get($prefix, function(Request $request) use ($app) {
            return Courses::index($app, $request);
        });
        $app->router->get($prefix.'/', function(Request $request) use ($app) {
            return Courses::index($app, $request);
        });
        $app->router->get($prefix.'/{id:\d+}', function(Request $request, $id) use ($app) {
            return Courses::enter($app, $request, $id);
        });
        $app->router->get($prefix.'/{id:\d+}/', function(Request $request, $id) use ($app) {
            return Courses::enter($app, $request, $id);
        });
        $app->router->get($prefix.'/{id:\d+}/image/{kind}', function(Request $request, $id, $kind) use ($app) {
            return Courses::image($app, $request, $id, $kind);
        });
        $nested = function(Request $request, $id, $rest) use ($app) {
            return Courses::nested($app, $request, $id, $rest);
        };
        $app->router->get($prefix.'/{id:\d+}/{rest:.*}', $nested);
        $app->router->post($prefix.'/{id:\d+}/{rest:.*}', $nested);
    }

    /**
     * Path prefix for this request: '' or '/courses/{id}' (no trailing slash).
     *
     * Follows REQUEST_URI. Parent site menus should not call this.
     */
    public static function toolPathPrefix() {
        $cid = self::courseIdFromRequest();
        if ( $cid < 1 ) {
            return '';
        }
        return self::ROUTE . '/' . $cid;
    }

    /**
     * Site top-menu waffle. Off unless $CFG->show_courses_widget is set.
     */
    public static function showCoursesWidget() {
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            return false;
        }
        return ! empty($CFG->show_courses_widget);
    }

    /**
     * Session key for the Google site-login course (never overwritten by /courses/{id}).
     */
    const SESSION_SITE_CONTEXT_ID = 'site_context_id';

    /**
     * True when REQUEST_URI is /courses/{id} or /courses/{id}/…
     *
     * This is the course vs site menu split. Do not use currentContextId().
     */
    public static function isCourseMountedRequest() {
        return (bool) preg_match('#/courses/\d+(?:/|$)#', self::requestPath());
    }

    /**
     * On site URLs (buildmenu world), drop a leftover /courses/{id} sandbox
     * and put the Google-login course back in the session.
     *
     * Course-mounted URLs, the Settings cartridge uploader, and LMS LTI
     * launches are left alone. Idempotent.
     * Does not call touchVisited() — bouncing back to the Google home
     * course on site URLs must not steal the flyout's recency list.
     */
    public static function restoreSiteLoginContext() {
        if ( self::isCourseMountedRequest() ) {
            return false;
        }
        if ( Settings::isCartridgeUploadRequest() ) {
            return false;
        }
        if ( ! U::isLoggedIn() ) {
            return false;
        }
        if ( ! self::isGoogleLoginSession() ) {
            return false;
        }
        $home = self::siteLoginContextId();
        $current = U::currentContextId();
        $hadManifest = Manifest::activeId() > 0;
        if ( $home > 0 && $current !== $home ) {
            $result = self::ensureActiveContext($home);
            if ( $result !== true ) {
                Manifest::rememberInSession(0);
                return false;
            }
            Manifest::rememberInSession(0);
            return true;
        }
        if ( $hadManifest ) {
            Manifest::rememberInSession(0);
            Cache::clearAllSessionCaches();
            Output::clearTopNavSession();
            if ( function_exists('_tsugiResetIdentitySnapshot') ) {
                _tsugiResetIdentitySnapshot();
            }
            return true;
        }
        return false;
    }

    /**
     * Google site-login context id, or 0.
     */
    public static function siteLoginContextId() {
        global $CFG, $PDOX;
        if ( isset($_SESSION[self::SESSION_SITE_CONTEXT_ID]) ) {
            $id = (int) $_SESSION[self::SESSION_SITE_CONTEXT_ID];
            if ( $id > 0 ) {
                return $id;
            }
        }
        if ( ! isset($CFG) || ! is_object($CFG) || ! $CFG->hasSiteContextTitle() ) {
            return 0;
        }
        if ( $PDOX === null || $PDOX === false ) {
            try {
                $PDOX = LTIX::getConnection();
            } catch ( \Throwable $e ) {
                return 0;
            }
        }
        $keyId = self::googleKeyId();
        if ( $keyId < 1 ) {
            return 0;
        }
        $context_key = 'course:'.md5($CFG->context_title);
        $row = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context
                WHERE context_sha256 = :SHA AND key_id = :KID
                  AND (deleted IS NULL OR deleted = 0)
                LIMIT 1",
            array(':SHA' => lti_sha256($context_key), ':KID' => $keyId)
        );
        if ( ! $row || ! isset($row['context_id']) ) {
            return 0;
        }
        $id = (int) $row['context_id'];
        if ( $id > 0 ) {
            $_SESSION[self::SESSION_SITE_CONTEXT_ID] = $id;
        }
        return $id;
    }

    /**
     * Context id from a course-mounted REQUEST_URI, or 0.
     */
    public static function courseIdFromRequest() {
        if ( preg_match('#/courses/(\d+)(?:/|$)#', self::requestPath(), $m) ) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Absolute or path prefix for /courses/{id} with no trailing slash.
     */
    public static function courseUrlPrefix($context_id) {
        global $CFG;
        $id = (int) $context_id;
        $path = self::requestPath();
        if ( $id > 0 && preg_match('#^(.*?/courses/'.$id.')(?:/|$)#', $path, $m) ) {
            $found = $m[1];
            if ( preg_match('#^https?://#i', $found) ) {
                return $found;
            }
            $home = ( isset($CFG->apphome) && is_string($CFG->apphome) && $CFG->apphome )
                ? rtrim($CFG->apphome, '/')
                : rtrim((string) $CFG->wwwroot, '/');
            if ( $home !== '' && str_starts_with($found, '/') ) {
                $parts = parse_url($home);
                $origin = '';
                if ( is_array($parts) && isset($parts['scheme'], $parts['host']) ) {
                    $origin = $parts['scheme'].'://'.$parts['host'];
                    if ( isset($parts['port']) ) {
                        $origin .= ':'.$parts['port'];
                    }
                }
                return $origin.$found;
            }
            return $found;
        }
        $home = ( isset($CFG->apphome) && is_string($CFG->apphome) && $CFG->apphome )
            ? rtrim($CFG->apphome, '/')
            : rtrim((string) $CFG->wwwroot, '/');
        if ( $id < 1 ) {
            return $home;
        }
        return $home.self::ROUTE.'/'.$id;
    }

    /**
     * App home (home_path, else apphome, else wwwroot).
     */
    public static function appHomeUrl() {
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) || ! method_exists($CFG, 'getHomeUrl') ) {
            return '';
        }
        $url = $CFG->getHomeUrl();
        return is_string($url) ? $url : '';
    }

    /**
     * Where a membership card opens. The Google site-login course opens
     * the app home, not /courses/{id}.
     */
    public static function membershipHref($context_id, $listHome, $siteContextId, $siteHomeUrl) {
        $id = (int) $context_id;
        $siteId = (int) $siteContextId;
        $site = trim((string) $siteHomeUrl);
        if ( $id > 0 && $siteId > 0 && $id === $siteId && $site !== '' ) {
            return $site;
        }
        if ( $id < 1 ) {
            return (string) $listHome;
        }
        return self::joinToolHome($listHome, (string) $id);
    }

    /**
     * Redirect the Google site-login course to the app home.
     *
     * @return RedirectResponse|null
     */
    public static function siteLoginRedirect($context_id) {
        $id = (int) $context_id;
        $siteId = self::siteLoginContextId();
        if ( $id < 1 || $siteId < 1 || $id !== $siteId ) {
            return null;
        }
        $dest = self::appHomeUrl();
        if ( $dest === '' ) {
            return null;
        }
        return new RedirectResponse($dest);
    }

    /**
     * Course Home URL after switching into /courses/{id}.
     *
     * GET /courses/{id} used to bounce to the site apphome (bare `/`). That
     * leaves the course URL space and shows buildmenu.php. Send people to
     * /courses/{id}/home so the course nav owns the next page.
     */
    public static function courseHomeUrl($context_id) {
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $id = (int) $context_id;
        if ( $id < 1 ) {
            return U::addSession($home);
        }
        return U::addSession(self::joinToolHome($home, $id.'/home'));
    }

    /**
     * True when this session is a Google site login (not an LMS LTI launch).
     */
    public static function isGoogleLoginSession() {
        $postKey = defined('TSUGI_SESSION_LTI_POST') ? TSUGI_SESSION_LTI_POST : 'lti_post';
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( ! empty($_SESSION[$postKey]) ) {
            return false;
        }
        $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : null;
        if ( $key === null && isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ) {
            $key = isset($_SESSION[$ltiKey]['key_key']) ? $_SESSION[$ltiKey]['key_key'] : null;
        }
        return $key === self::GOOGLE_KEY;
    }

    /**
     * Switch the Google-login session to $context_id when needed.
     *
     * @return true|string true on success, error message otherwise
     */
    public static function ensureActiveContext($context_id) {
        global $CFG, $PDOX, $CONTEXT, $LAUNCH, $TSUGI_LAUNCH;

        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return 'Invalid course.';
        }

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;

        $context_row = $PDOX->rowDie(
            "SELECT context_id, context_key, title, manifest_id FROM {$p}lti_context
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $cid)
        );
        if ( ! $context_row ) {
            return 'Course not found.';
        }

        $current = U::currentContextId();
        if ( $current === $cid ) {
            self::wireLaunchConnection();
            // Pages/Files only need context_id. Lessons reads the session
            // manifest_id, which Google login never set for this course.
            $mid = Manifest::idForContext($cid);
            if ( $mid > 0 ) {
                Manifest::rememberInSession($mid);
            }
            return true;
        }

        $user_id = U::loggedInUserId();
        if ( $user_id < 1 ) {
            return 'Must be logged in.';
        }

        $is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        $member = $PDOX->rowDie(
            "SELECT membership_id, role, role_override FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $cid, ':UID' => $user_id)
        );
        if ( ! $is_admin && ! $member ) {
            return 'You are not a member of that course.';
        }

        $role = 0;
        $membership_id = null;
        if ( $member ) {
            $r = isset($member['role']) ? ($member['role'] + 0) : 0;
            $ro = isset($member['role_override']) ? ($member['role_override'] + 0) : 0;
            $role = max($r, $ro);
            if ( isset($member['membership_id']) ) {
                $membership_id = $member['membership_id'] + 0;
            }
        }
        if ( $is_admin && $role < LTIX::ROLE_INSTRUCTOR ) {
            $role = LTIX::ROLE_INSTRUCTOR;
        }

        $title = isset($context_row['title']) ? $context_row['title'] : '';
        $context_key = isset($context_row['context_key']) ? $context_row['context_key'] : '';
        $manifest_id = isset($context_row['manifest_id']) ? (int) $context_row['manifest_id'] : 0;

        $_SESSION['context_id'] = $cid;
        $_SESSION['context_key'] = $context_key;
        $_SESSION['context_title'] = $title;
        $_SESSION['isinstructor'] = ($role >= LTIX::ROLE_INSTRUCTOR);
        // Do not touch User::SESSION_CREATE_COURSES — that is a user capability, not a context role.
        if ( $membership_id !== null ) {
            $_SESSION['membership_id'] = $membership_id;
        } else {
            unset($_SESSION['membership_id']);
        }

        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        $lti = isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ? $_SESSION[$ltiKey] : array();
        $lti['context_id'] = $cid;
        $lti['context_key'] = $context_key;
        $lti['context_title'] = $title;
        $lti['resource_title'] = $title;
        $lti['role'] = $role;
        if ( $membership_id !== null ) {
            $lti['membership_id'] = $membership_id;
        } else {
            unset($lti['membership_id']);
        }
        unset($lti['context_settings']);
        $_SESSION[$ltiKey] = $lti;
        Manifest::rememberInSession($manifest_id);

        Cache::clearAllSessionCaches();
        Output::clearTopNavSession();

        if ( function_exists('_tsugiResetIdentitySnapshot') ) {
            _tsugiResetIdentitySnapshot();
        }

        // Do not attach Context to the dummy $LAUNCH from lms_lib.php (no pdox).
        // Null launch objects so buildLaunch() recreates them on $TSUGI_LAUNCH.
        global $USER, $LINK, $RESULT, $TSUGI_KEY, $PROFILE;
        $CONTEXT = null;
        $USER = null;
        $LINK = null;
        $RESULT = null;
        $TSUGI_KEY = null;
        $PROFILE = null;
        LTIX::buildLaunch($lti);
        self::wireLaunchConnection();

        return true;
    }

    /**
     * Forget $context_id when it is the course in this session.
     *
     * Leaves login, CSRF, and the stored LTI return URL in place.
     *
     * @param int $context_id
     * @return void
     */
    public static function dropActiveContext($context_id) {
        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return;
        }
        $current = (int) ($_SESSION['context_id'] ?? 0);
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        $ltiId = 0;
        if ( isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ) {
            $ltiId = (int) ($_SESSION[$ltiKey]['context_id'] ?? 0);
        }
        if ( $current !== $context_id && $ltiId !== $context_id ) {
            return;
        }
        self::clearActiveContext();
    }

    /**
     * Drop a deleted course from the session and return to the site-login course when one remains.
     *
     * @param int $deleted_id
     * @return void
     */
    public static function releaseContextAfterDelete($deleted_id) {
        $deleted_id = (int) $deleted_id;
        if ( isset($_SESSION[self::SESSION_SITE_CONTEXT_ID])
            && (int) $_SESSION[self::SESSION_SITE_CONTEXT_ID] === $deleted_id ) {
            unset($_SESSION[self::SESSION_SITE_CONTEXT_ID]);
        }

        $home = 0;
        try {
            $home = self::siteLoginContextId();
        } catch ( \Throwable $e ) {
            $home = 0;
        }
        if ( $home > 0 && $home !== $deleted_id ) {
            $switched = self::ensureActiveContext($home);
            if ( $switched === true ) {
                Manifest::rememberInSession(0);
                return;
            }
        }
        self::clearActiveContext();
    }

    /**
     * Forget the current course in this session.
     *
     * @return void
     */
    private static function clearActiveContext() {
        unset(
            $_SESSION['context_id'],
            $_SESSION['context_key'],
            $_SESSION['context_title'],
            $_SESSION['membership_id'],
            $_SESSION['isinstructor']
        );
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) ) {
            unset(
                $_SESSION[$ltiKey]['context_id'],
                $_SESSION[$ltiKey]['context_key'],
                $_SESSION[$ltiKey]['context_title'],
                $_SESSION[$ltiKey]['resource_title'],
                $_SESSION[$ltiKey]['membership_id'],
                $_SESSION[$ltiKey]['context_settings']
            );
        }
        Manifest::rememberInSession(0);
        Cache::clearAllSessionCaches();
        Output::clearTopNavSession();
        if ( function_exists('_tsugiResetIdentitySnapshot') ) {
            _tsugiResetIdentitySnapshot();
        }

        global $CONTEXT, $USER, $LINK, $RESULT, $LAUNCH, $TSUGI_LAUNCH, $TSUGI_KEY, $PROFILE;
        $CONTEXT = null;
        $USER = null;
        $LINK = null;
        $RESULT = null;
        $LAUNCH = null;
        $TSUGI_LAUNCH = null;
        $TSUGI_KEY = null;
        $PROFILE = null;
    }

    /**
     * Record that this user entered a course (flyout recency).
     *
     * Call only from explicit /courses/{id} entry (enter, nested switch,
     * create). Do not call from restoreSiteLoginContext().
     */
    public static function touchVisited($context_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        $user_id = U::loggedInUserId();
        if ( $cid < 1 || $user_id < 1 ) {
            return;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;
        $PDOX->queryDie(
            "UPDATE {$p}lti_membership
             SET visited_at = NOW()
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $cid, ':UID' => $user_id)
        );
    }

    /**
     * Point Context/Output at $TSUGI_LAUNCH and ensure it has a PDOX connection.
     * lms_lib.php may have created a dummy $LAUNCH without pdox.
     */
    public static function wireLaunchConnection() {
        global $PDOX, $TSUGI_LAUNCH, $LAUNCH, $OUTPUT, $CONTEXT;

        if ( ! isset($TSUGI_LAUNCH) || ! is_object($TSUGI_LAUNCH) ) {
            return;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $TSUGI_LAUNCH->pdox = $PDOX;
        $LAUNCH = $TSUGI_LAUNCH;
        if ( isset($OUTPUT) && is_object($OUTPUT) ) {
            $OUTPUT->launch = $TSUGI_LAUNCH;
            $TSUGI_LAUNCH->output = $OUTPUT;
        }
        if ( isset($CONTEXT) && is_object($CONTEXT) ) {
            $CONTEXT->launch = $TSUGI_LAUNCH;
            $TSUGI_LAUNCH->context = $CONTEXT;
        }
    }

    public static function index(Application $app, Request $request) {
        global $CFG, $OUTPUT, $PDOX;

        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;
        $user_id = U::loggedInUserId();
        $siteId = self::siteLoginContextId();

        $rows = $PDOX->allRowsDie(
            "SELECT C.context_id, C.context_key,
                    COALESCE(NULLIF(MF.title, ''), C.title) AS title,
                    CI.hero_bytes, CI.hero_updated_at, CI.icon_bytes, CI.icon_updated_at
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_context AS C ON M.context_id = C.context_id
             LEFT JOIN {$p}manifest AS MF ON C.manifest_id = MF.manifest_id
             LEFT JOIN {$p}context_images AS CI ON CI.context_id = C.context_id
             WHERE M.user_id = :UID
               AND (C.deleted IS NULL OR C.deleted = 0)
               ".self::listedCourseSql()."
             ORDER BY COALESCE(NULLIF(MF.title, ''), C.title), C.context_id",
            array(':UID' => $user_id, ':SITE' => $siteId)
        );
        if ( ! is_array($rows) ) {
            $rows = array();
        }
        $rows = self::withImageUrls($rows);

        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $can_create = self::canCreate();
        $create_url = self::joinToolHome($home, 'create');
        $siteUrl = self::appHomeUrl();
        foreach ( $rows as $i => $row ) {
            $id = (int) $row['context_id'];
            $rows[$i]['href'] = self::membershipHref($id, $home, $siteId, $siteUrl);
            if ( ! isset($row['title']) || $row['title'] === '' ) {
                $rows[$i]['title'] = 'Course '.$id;
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Courses/index.inc.php';
        $OUTPUT->footer();
        return '';
    }

    public static function enter(Application $app, Request $request, $id) {
        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }

        $siteHome = self::siteLoginRedirect($id);
        if ( $siteHome !== null ) {
            return $siteHome;
        }

        $result = self::ensureActiveContext($id);
        if ( $result !== true ) {
            return self::switchFailedResponse($result);
        }
        self::touchVisited($id);

        return new RedirectResponse(self::courseHomeUrl($id));
    }

    /**
     * Serve a course hero or icon.
     *
     * Same Google gate as the rest of /courses (gateResponse). Membership
     * is also required. Does not switch context. LMS LTI sessions do not
     * use this URL; course nav and Settings Images are course-mounted.
     */
    public static function image(Application $app, Request $request, $id, $kind) {
        global $PDOX;

        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }
        $cid = (int) $id;
        if ( $cid < 1 || ! ContextImages::isKind($kind) ) {
            return new Response('', 404);
        }
        $member = self::memberCheck($cid);
        if ( $member !== true ) {
            return new Response($member, 403);
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $row = ContextImages::blob($cid, $kind);
        return self::imageResponse($row, $kind, $cid);
    }

    /**
     * JPEG bytes with a versioned ETag, or 404.
     *
     * @param array{bytes:string,mime:string,updated_at:?string}|null $row
     */
    private static function imageResponse($row, $kind, $context_id) {
        if ( ! is_array($row) || ! isset($row['bytes']) || ! is_string($row['bytes']) || $row['bytes'] === '' ) {
            $response = new Response('', 404);
            $response->headers->set('Cache-Control', 'private, max-age=60');
            return $response;
        }
        $etag = '"'.sha1($context_id.'|'.$kind.'|'.strlen($row['bytes']).'|'.(string) ($row['updated_at'] ?? '')).'"';
        $inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ( is_string($inm) && $inm !== '' && hash_equals($etag, $inm) ) {
            $response = new Response('', 304);
            $response->setEtag(trim($etag, '"'));
            $response->headers->set('Cache-Control', 'private, max-age=86400');
            return $response;
        }
        $response = new Response($row['bytes'], 200);
        $response->headers->set('Content-Type', isset($row['mime']) ? $row['mime'] : ContextImages::MIME);
        $response->headers->set('Content-Length', (string) strlen($row['bytes']));
        $response->headers->set('Cache-Control', 'private, max-age=86400');
        $response->setEtag(trim($etag, '"'));
        return $response;
    }

    /**
     * Login + membership (or site admin) for $context_id without switching session.
     *
     * @return true|string
     */
    private static function memberCheck($context_id) {
        global $CFG, $PDOX;

        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return 'Invalid course.';
        }
        $user_id = U::loggedInUserId();
        if ( $user_id < 1 ) {
            return 'Must be logged in.';
        }
        $is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        if ( $is_admin ) {
            return true;
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;
        $member = $PDOX->rowDie(
            "SELECT M.membership_id
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_context AS C ON C.context_id = M.context_id
             WHERE M.context_id = :CID AND M.user_id = :UID
               AND (C.deleted IS NULL OR C.deleted = 0)",
            array(':CID' => $cid, ':UID' => $user_id)
        );
        if ( ! $member ) {
            return 'You are not a member of that course.';
        }
        return true;
    }

    /**
     * Form to create a new site-login course with a starter manifest.
     */
    public static function createForm(Application $app, Request $request) {
        global $OUTPUT;

        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $gate = self::createGateResponse($home);
        if ( $gate ) {
            return $gate;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1>Add course</h1>
            <p>Creates a new course with a Lessons JSON v2 starter outline in the database. Authoring is available for this course and saves new manifest versions. It does not write a lessons.json file.</p>
            <form method="post" action="<?= htmlspecialchars(self::joinToolHome($home, 'create')) ?>">
                <?= self::csrfField() ?>
                <p>
                    <label for="course_title">Title</label><br/>
                    <input type="text" id="course_title" name="title" required maxlength="512" style="min-width: 20em;"/>
                </p>
                <p>
                    <label for="course_short_title">Short title</label><br/>
                    <input type="text" id="course_short_title" name="short_title" maxlength="<?= (int) Manifest::SHORT_TITLE_MAX ?>" style="min-width: 20em;"/>
                    <br/>
                    <span class="help-block">Optional. The first six characters become the upper-left home label. For example, title “Django for everybody” and short title “DJ Free”.</span>
                </p>
                <p>
                    <button type="submit" class="btn btn-primary">Create course</button>
                    <a href="<?= htmlspecialchars($home) ?>" class="btn btn-default">Cancel</a>
                </p>
            </form>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * POST: insert context + starter manifest, enter the course.
     */
    public static function createPost(Application $app, Request $request) {
        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);
        $gate = self::createGateResponse($home);
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf(U::addSession(self::joinToolHome($home, 'create')));
        if ( $csrf ) {
            return $csrf;
        }

        $title = trim((string) U::get($_POST, 'title', ''));
        if ( $title === '' ) {
            U::flashError(__('Title is required.'));
            return new RedirectResponse(U::addSession(self::joinToolHome($home, 'create')));
        }
        $short_title = (string) U::get($_POST, 'short_title', '');

        $user_id = U::loggedInUserId();
        $key_id = self::googleKeyId();
        $result = Manifest::createCourse($title, $user_id, $key_id, $short_title);
        if ( empty($result['ok']) ) {
            $err = isset($result['error']) ? $result['error'] : 'Could not create course.';
            U::flashError($err);
            return new RedirectResponse(U::addSession(self::joinToolHome($home, 'create')));
        }

        $switch = self::ensureActiveContext($result['context_id']);
        if ( $switch !== true ) {
            return self::switchFailedResponse($switch);
        }
        self::touchVisited($result['context_id']);

        U::flashSuccess(__('Course created.'));
        return new RedirectResponse(self::courseHomeUrl($result['context_id']));
    }

    /**
     * lti_key.key_id for the Google site-login consumer.
     */
    public static function googleKeyId() {
        global $CFG, $PDOX;
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( isset($_SESSION[$ltiKey]['key_id']) ) {
            $kid = (int) $_SESSION[$ltiKey]['key_id'];
            if ( $kid > 0 ) {
                return $kid;
            }
        }
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $row = $PDOX->rowDie(
            "SELECT key_id FROM {$CFG->dbprefix}lti_key
             WHERE key_sha256 = :SHA LIMIT 1",
            array(':SHA' => lti_sha256(self::GOOGLE_KEY))
        );
        return $row ? (int) $row['key_id'] : 0;
    }

    public static function nested(Application $app, Request $request, $id, $rest) {
        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }

        $rest = is_string($rest) ? trim($rest, '/') : '';
        if ( $rest === '' || $rest === 'courses' || str_starts_with($rest, 'courses/') ) {
            return self::enter($app, $request, $id);
        }
        if ( $rest === 'home' ) {
            $siteHome = self::siteLoginRedirect($id);
            if ( $siteHome !== null ) {
                return $siteHome;
            }
        }

        $before = U::currentContextId();
        $result = self::ensureActiveContext($id);
        if ( $result !== true ) {
            return self::switchFailedResponse($result);
        }
        if ( $before !== (int) $id ) {
            self::touchVisited($id);
        }

        if ( self::$dispatchingNested ) {
            return new Response('Nested course dispatch loop', 500);
        }

        self::$dispatchingNested = true;
        try {
            return $app->dispatch(self::innerRequest($request, $rest));
        } finally {
            self::$dispatchingNested = false;
        }
    }

    public static function getjson(Application $app)
    {
        global $CFG;

        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }

        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user)) {
            return \response()->json(
                array('error' => 'You are not logged in.')
            );
        }

        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;

        $row = $PDOX->rowDie("SELECT profile_id FROM {$p}lti_user WHERE user_id = :UID;",
            array(':UID' => $tsugi->user->id)
        );

        if ( $row === false || ! isset($row['profile_id']) ) {
            return \response()->json(array("error" => "No profile_id"));
        }

        $limit = (int) self::FLYOUT_LIMIT;
        $home = self::siteLoginContextId();
        $params = array(':PID' => $row['profile_id'], ':SITE' => $home);
        $excludeHome = '';
        if ( $home > 0 ) {
            $excludeHome = ' AND C.context_id != :HOME';
            $params[':HOME'] = $home;
        }

        $sql = self::flyoutMembershipSql($p, $excludeHome)." LIMIT {$limit}";
        $rows = $PDOX->allRowsDie($sql, $params);
        if ( ! is_array($rows) ) {
            $rows = array();
        }
        // Google home course stays out of the waffle unless it is the only site.
        if ( count($rows) < 1 && $home > 0 ) {
            $rows = $PDOX->allRowsDie(
                self::flyoutMembershipSql($p, ' AND C.context_id = :HOME').' LIMIT 1',
                array(':PID' => $row['profile_id'], ':HOME' => $home, ':SITE' => $home)
            );
            if ( ! is_array($rows) ) {
                $rows = array();
            }
        }
        $rows = self::withImageUrls($rows);
        $list = rtrim((string) $CFG->wwwroot, '/').self::ROUTE;
        $siteUrl = self::appHomeUrl();
        foreach ( $rows as $i => $row ) {
            $rows[$i]['href'] = self::membershipHref(
                (int) ($row['context_id'] ?? 0),
                $list,
                $home,
                $siteUrl
            );
        }
        return response()->json(array(
            'status' => 'success',
            'courses' => $rows,
            'current_context_id' => U::currentContextId(),
            'can_create' => self::canCreate(),
            'show_catalog' => Catalog::showCourseCatalog(),
            'catalog_url' => Catalog::showCourseCatalog() ? Catalog::catalogUrl() : '',
        ));
    }

    /**
     * Flyout memberships: never-visited rows use created_at so new
     * enrollments still surface. Caller adds WHERE extras and LIMIT.
     *
     * Requires :SITE, the site-login course id (0 if there is none).
     */
    private static function flyoutMembershipSql($p, $extraWhere='') {
        return "SELECT P.profile_id, U.user_id, U.email, C.context_id,
                COALESCE(NULLIF(MF.title, ''), C.title) AS title,
                CI.hero_bytes, CI.hero_updated_at, CI.icon_bytes, CI.icon_updated_at
            FROM {$p}profile AS P
            JOIN {$p}lti_user AS U ON P.profile_id = U.profile_id
            JOIN {$p}lti_membership AS M ON U.user_id = M.user_id
            JOIN {$p}lti_context AS C ON M.context_id = C.context_id
            LEFT JOIN {$p}manifest AS MF ON C.manifest_id = MF.manifest_id
            LEFT JOIN {$p}context_images AS CI ON CI.context_id = C.context_id
            WHERE P.profile_id = :PID
              AND (C.deleted IS NULL OR C.deleted = 0)
              ".self::listedCourseSql()."{$extraWhere}
            ORDER BY COALESCE(M.visited_at, M.created_at) DESC";
    }

    /**
     * Courses with no manifest stay off Courses and the flyout.
     *
     * The site-wide course for $CFG->context_title is the exception (:SITE).
     */
    private static function listedCourseSql() {
        return 'AND (C.manifest_id IS NOT NULL OR C.context_id = :SITE)';
    }

    /**
     * Replace image byte metadata with versioned URLs. Does not touch BLOBs.
     *
     * @param array<int, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    public static function withImageUrls(array $rows) {
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $id = (int) ($row['context_id'] ?? 0);
            $row['hero_url'] = ContextImages::servedUrl(
                $id,
                ContextImages::KIND_HERO,
                $row['hero_bytes'] ?? 0,
                $row['hero_updated_at'] ?? null
            );
            $row['icon_url'] = ContextImages::servedUrl(
                $id,
                ContextImages::KIND_ICON,
                $row['icon_bytes'] ?? 0,
                $row['icon_updated_at'] ?? null
            );
            unset($row['hero_bytes'], $row['hero_updated_at'], $row['icon_bytes'], $row['icon_updated_at']);
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Build an inner request whose pathInfo is /{rest}.
     * PHP $_SERVER is unchanged, so toolHome() still sees the nested URL.
     */
    public static function innerRequest(Request $request, $rest) {
        $newPath = '/' . ltrim($rest, '/');
        $qs = $request->getQueryString();
        $uri = $newPath . ($qs ? '?'.$qs : '');
        $params = $request->isMethod('GET') || $request->isMethod('HEAD')
            ? $request->query->all()
            : $request->request->all();
        return Request::create(
            $uri,
            $request->getMethod(),
            $params,
            $request->cookies->all(),
            self::filesForCreate($request),
            $request->server->all(),
            $request->getContent()
        );
    }

    /**
     * FileBag turns empty inputs into null; Request::create() rejects null.
     *
     * @return array<string, mixed>
     */
    private static function filesForCreate(Request $request) {
        return self::sanitizeFilesForCreate($request->files->all());
    }

    /**
     * @param array<string|int, mixed> $files
     * @return array<string|int, mixed>
     */
    private static function sanitizeFilesForCreate(array $files) {
        $out = array();
        foreach ( $files as $key => $file ) {
            if ( $file instanceof UploadedFile ) {
                $out[$key] = $file;
                continue;
            }
            if ( is_array($file) ) {
                $nested = self::sanitizeFilesForCreate($file);
                if ( $nested !== array() ) {
                    $out[$key] = $nested;
                }
            }
        }
        return $out;
    }

    /**
     * True when the current user may mint a new site-login course.
     */
    public static function canCreate() {
        return User::canCreateCourses();
    }

    /**
     * Google site-login only. Every /courses route uses this, including
     * nested tools and /courses/{id}/image/{kind}. LMS LTI launches already
     * have a course from the LMS and do not use this URL family.
     *
     * @return Response|null
     */
    public static function gateResponse() {
        if ( ! U::isLoggedIn() ) {
            return new Response('Must be logged in', 403);
        }
        if ( ! self::isGoogleLoginSession() ) {
            return new Response(
                'This session is an LTI launch from an LMS. Course switching is not available.',
                403
            );
        }
        return null;
    }

    /**
     * Login + Google session + create_courses (or site admin).
     *
     * @return Response|null
     */
    public static function createGateResponse($home) {
        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }
        if ( ! self::canCreate() ) {
            U::flashError('You are not allowed to create courses.');
            return new RedirectResponse(U::addSession($home));
        }
        return null;
    }

    /**
     * @param string $message
     * @return Response
     */
    private static function switchFailedResponse($message) {
        $status = ($message === 'You are not a member of that course.') ? 403 : 400;
        return new Response($message, $status);
    }
}
