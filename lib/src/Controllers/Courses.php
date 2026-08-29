<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Cache;
use \Tsugi\Core\Context;
use \Tsugi\Core\Manifest;
use \Tsugi\Core\User;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\UI\Output;
use \Tsugi\Util\U;

class Courses extends Tool {

    const ROUTE = '/courses';

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
        $nested = function(Request $request, $id, $rest) use ($app) {
            return Courses::nested($app, $request, $id, $rest);
        };
        $app->router->get($prefix.'/{id:\d+}/{rest:.*}', $nested);
        $app->router->post($prefix.'/{id:\d+}/{rest:.*}', $nested);
    }

    /**
     * Menu-only path prefix: '' or '/courses/{id}' (no trailing slash).
     *
     * Controllers do not call this. Parent menus:
     * rtrim($CFG->apphome, '/') . Courses::toolPathPrefix() . '/announcements'
     *
     * Temporary site flag: $CFG->setExtension('courses_in_urls', true)
     * or an email allowlist array. Unset/false keeps menus unprefixed.
     * Prefix is Google-login only; LMS launches stay unprefixed.
     */
    public static function toolPathPrefix() {
        global $CFG;
        $flag = $CFG->getExtension('courses_in_urls', false);
        if ( empty($flag) ) {
            return '';
        }
        if ( is_array($flag) ) {
            $email = isset($_SESSION['email']) ? (string) $_SESSION['email'] : '';
            $allowed = false;
            foreach ($flag as $candidate) {
                if ( strcasecmp(trim((string) $candidate), trim($email)) === 0 ) {
                    $allowed = true;
                    break;
                }
            }
            if ( ! $allowed ) {
                return '';
            }
        }
        if ( ! self::isGoogleLoginSession() ) {
            return '';
        }
        $cid = U::currentContextId();
        if ( $cid < 1 ) {
            return '';
        }
        return self::ROUTE . '/' . $cid;
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

        $current = U::currentContextId();
        if ( $current === $cid ) {
            self::wireLaunchConnection();
            return true;
        }

        $user_id = U::loggedInUserId();
        if ( $user_id < 1 ) {
            return 'Must be logged in.';
        }

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
        $p = $CFG->dbprefix;

        $context_row = $PDOX->rowDie(
            "SELECT context_id, context_key, title, manifest_id FROM {$p}lti_context WHERE context_id = :CID",
            array(':CID' => $cid)
        );
        if ( ! $context_row ) {
            return 'Course not found.';
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

        if ( ! empty($CFG->enable_secure_cookie_login) && isset($_SESSION['email']) ) {
            SecureCookie::set($user_id, $_SESSION['email'], $cid);
        }

        return true;
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

        $rows = $PDOX->allRowsDie(
            "SELECT C.context_id, C.title, C.context_key
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_context AS C ON M.context_id = C.context_id
             WHERE M.user_id = :UID
             ORDER BY C.title, C.context_id",
            array(':UID' => $user_id)
        );
        if ( ! is_array($rows) ) {
            $rows = array();
        }

        $tool = new self();
        $home = $tool->toolHome(self::ROUTE);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1>Courses</h1>
            <?php if ( self::canCreate() ) { ?>
            <p><a href="<?= htmlspecialchars($home . '/create') ?>">Add course</a></p>
            <?php } ?>
            <?php if ( count($rows) < 1 ) { ?>
                <p>You are not a member of any courses.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ( $rows as $row ) {
                        $id = (int) $row['context_id'];
                        $title = isset($row['title']) && $row['title'] !== '' ? $row['title'] : ('Course '.$id);
                        $href = htmlspecialchars($home . '/' . $id);
                    ?>
                    <li><a href="<?= $href ?>"><?= htmlspecialchars($title) ?></a></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    public static function enter(Application $app, Request $request, $id) {
        $gate = self::gateResponse();
        if ( $gate ) {
            return $gate;
        }

        $result = self::ensureActiveContext($id);
        if ( $result !== true ) {
            return self::switchFailedResponse($result);
        }

        return new RedirectResponse(self::configuredHomeUrl());
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
            <p>Creates a new course with a starter outline. Lesson authoring for this course saves new manifest versions.</p>
            <form method="post" action="<?= htmlspecialchars($home . '/create') ?>">
                <?= self::csrfField() ?>
                <p>
                    <label for="course_title">Title</label><br/>
                    <input type="text" id="course_title" name="title" required maxlength="512" style="min-width: 20em;"/>
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
        $csrf = self::requireCsrf(U::addSession($home . '/create'));
        if ( $csrf ) {
            return $csrf;
        }

        $title = trim((string) U::get($_POST, 'title', ''));
        if ( $title === '' ) {
            U::flashError(__('Title is required.'));
            return new RedirectResponse(U::addSession($home . '/create'));
        }

        $user_id = U::loggedInUserId();
        $key_id = self::googleKeyId();
        $result = Manifest::createCourse($title, $user_id, $key_id);
        if ( empty($result['ok']) ) {
            $err = isset($result['error']) ? $result['error'] : 'Could not create course.';
            U::flashError($err);
            return new RedirectResponse(U::addSession($home . '/create'));
        }

        $switch = self::ensureActiveContext($result['context_id']);
        if ( $switch !== true ) {
            return self::switchFailedResponse($switch);
        }

        U::flashSuccess(__('Course created.'));
        return new RedirectResponse(U::addSession($home . '/' . (int) $result['context_id']));
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

        $result = self::ensureActiveContext($id);
        if ( $result !== true ) {
            return self::switchFailedResponse($result);
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

        $sql = "SELECT P.profile_id, U.user_id, U.email, C.context_id, C.title
            FROM {$p}profile AS P
            JOIN {$p}lti_user AS U ON P.profile_id = U.profile_id
            JOIN {$p}lti_membership AS M ON U.user_id = M.user_id
            JOIN {$p}lti_context AS C ON M.context_id = C.context_id
            WHERE P.profile_id = :PID";

        $rows = $PDOX->allRowsDie($sql, array(':PID' => $row['profile_id']));
        return response()->json($rows);
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
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
    }

    /**
     * True when the current user may mint a new site-login course.
     */
    public static function canCreate() {
        return User::canCreateCourses();
    }

    /**
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
