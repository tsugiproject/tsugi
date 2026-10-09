<?php

namespace Tsugi\Controllers;

use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Lumos\Application;
use Tsugi\UI\Output;
use Tsugi\Util\Net;
use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site admin console, mounted by admin/route-controller.php.
 *
 * The passphrase session ($_SESSION['admin'] === 'yes') is the only
 * administrator. Each action passes a capability name. That session is
 * allowed every capability. A later tenant admin or organization admin
 * can be given a subset of those names and a scope on the same actions.
 *
 * Paths are relative to /admin because the front controller lives there.
 * Symfony's path info is /catalog for /admin/catalog.
 */
class Admin extends Tool {

    const ROUTE = '/admin';

    /** @var string Capability for this action. The passphrase session allows all of them. */
    protected $capability = 'admin';

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths(''));
        self::map($app, self::class, 'upgrade', self::paths('upgrade'));
        self::map($app, self::class, 'clear12345', self::paths('clear12345'));
        self::map($app, self::class, 'patchProfile', self::paths('patch_profile'));
        self::map($app, self::class, 'proxy', self::paths('proxy_small_json'));

        Admin\Check::routes($app);
        Admin\Blob::routes($app);
        Admin\Activity::routes($app);
        Admin\Badges::routes($app);
        Admin\Catalog::routes($app);
        Admin\Context::routes($app);
        Admin\Expire::routes($app);
        Admin\External::routes($app);
        Admin\Install::routes($app);
        Admin\Key::routes($app);
        Admin\Mail::routes($app);
        Admin\Org::routes($app);
        Admin\Profile::routes($app);
        Admin\Site::routes($app);
        Admin\Users::routes($app);
    }

    /**
     * @param class-string $class
     * @param array<int, string> $paths
     */
    public static function map(Application $app, $class, $method, array $paths) {
        $handler = '\\'.$class.'@'.$method;
        foreach ( $paths as $path ) {
            $app->router->get($path, $handler);
            $app->router->post($path, $handler);
        }
    }

    /**
     * Folder indexes accept the directory, a slash, and index.php.
     * Scripts accept the bare name, a slash, and .php.
     *
     * @return array<int, string>
     */
    public static function paths($urlPath, $folder = false) {
        if ( $urlPath === '' || $urlPath === '/' ) {
            return array('/', '/index', '/index.php');
        }
        $urlPath = '/'.trim((string) $urlPath, '/');
        if ( $folder ) {
            return array($urlPath, $urlPath.'/', $urlPath.'/index', $urlPath.'/index.php');
        }
        return array($urlPath, $urlPath.'/', $urlPath.'.php');
    }

    /**
     * @return RedirectResponse|Response|null Null when this request may continue.
     */
    protected function gate($capability, $denied = 'form') {
        global $CFG;

        $this->capability = (string) $capability;

        if ( $denied === 'forbid' ) {
            if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
                Net::send403();
                echo 'Must be admin';
                exit;
            }
            return null;
        }

        if ( $CFG->adminpw === false ) {
            unset($_SESSION['admin']);
            die('Please set $CFG->adminpw to a plaintext or hashed string');
        }

        // Login, then the passphrase, then the session flag.
        if ( $this->databaseReady() && $CFG->google_client_id && ! ReqScope::isLoggedInLegacy() ) {
            Login::setReturnUrl($this->here());
            return new RedirectResponse(U::addSession(Login::loginUrl()));
        }

        $early = $this->unlockPost();
        if ( $early ) {
            return $early;
        }

        if ( \Tsugi\Services\Admin\AdminService::isAdmin() ) {
            return null;
        }

        return $this->lockScreen();
    }

    /**
     * @return RedirectResponse|null
     */
    protected function unlockPost() {
        global $CFG;

        if ( ! isset($_POST['passphrase']) ) {
            return null;
        }

        $here = $this->here();

        if ( ! \Tsugi\Services\Admin\AdminService::adminEmailAllowed() ) {
            unset($_SESSION['admin']);
            error_log('Admin unlock denied email='.U::get($_SESSION, 'email', '').
                ' IP='.$_SERVER['REMOTE_ADDR']);
            U::flashError('This account is not allowed to unlock admin');
            return new RedirectResponse(U::addSession($here));
        }

        if ( ! Tool::csrfOk() ) {
            U::flashError('Missing or invalid CSRF token');
            return new RedirectResponse(U::addSession($here));
        }

        if ( \Tsugi\Services\Admin\AdminService::adminUnlockBanned() ) {
            U::flashError('Too many failed admin unlock attempts. Try again in 5 minutes.');
            return new RedirectResponse(U::addSession($here));
        }

        unset($_SESSION['admin']);
        $apw = $CFG->adminpw;
        $phrase = $_POST['passphrase'];
        $hash = 'sha256:'.lti_sha256($phrase);
        if ( (strpos($apw, 'sha256:') === false && $phrase === $apw) ||
            (strpos($apw, 'sha256:') === 0 && $hash === $apw) ) {
            \Tsugi\Services\Admin\AdminService::adminUnlockClearFails();
            $_SESSION['admin'] = 'yes';
            session_regenerate_id(true);
            error_log('Admin login IP='.$_SERVER['REMOTE_ADDR'].
                (ReqScope::isLoggedInLegacy() ? ' id='.ReqScope::loggedInUserIdLegacy().' email='.U::get($_SESSION, 'email', '') : ' developer mode'));
        } else {
            $locked = \Tsugi\Services\Admin\AdminService::adminUnlockRecordFail();
            error_log('Admin bad pw IP='.$_SERVER['REMOTE_ADDR'].
                (ReqScope::isLoggedInLegacy() ? ' id='.ReqScope::loggedInUserIdLegacy().' email='.U::get($_SESSION, 'email', '') : ' developer mode').
                ($locked ? ' locked=5m' : ''));
            if ( $locked ) {
                U::flashError('Too many failed admin unlock attempts. Try again in 5 minutes.');
            }
        }

        return new RedirectResponse(U::addSession($here));
    }

    /**
     * @return RedirectResponse|Response
     */
    protected function lockScreen() {
        global $CFG, $OUTPUT;

        $OUTPUT->buffer = true;
        $html = $OUTPUT->header();
        $html .= $OUTPUT->bodyStart();
        $html .= $OUTPUT->topNav();
        $html .= $OUTPUT->flashMessages();
        if ( ! \Tsugi\Services\Admin\AdminService::adminEmailAllowed() ) {
            $html .= '<p>This account is not allowed to unlock admin.</p>'."\n";
            $html .= $OUTPUT->footer();
            $OUTPUT->buffer = false;
            return new Response($html);
        }
        if ( \Tsugi\Services\Admin\AdminService::adminUnlockBanned() ) {
            $html .= '<p>Too many failed admin unlock attempts. Try again in 5 minutes.</p>'."\n";
            $html .= $OUTPUT->footer();
            $OUTPUT->buffer = false;
            return new Response($html);
        }
        $action = htmlspecialchars((string) $CFG->wwwroot).'/admin/';
        $html .= '<form method="post" action="'.$action.'">'."\n";
        $html .= Tool::csrfField();
        $html .= '<label for="passphrase">Admin Unlock:<br/>'."\n";
        $html .= '<input type="password" autocomplete="off" name="passphrase" size="80">'."\n";
        $html .= '</label>'."\n";
        $html .= '<input type="submit">'."\n";
        $html .= '</form>'."\n";
        $html .= $OUTPUT->footer();
        $OUTPUT->buffer = false;
        return new Response($html);
    }

    protected function databaseReady() {
        global $CFG, $PDOX;
        if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
        try {
            $PDOX = LTIX::getConnection();
            $stmt = $PDOX->queryReturnError("SELECT key_id FROM {$CFG->dbprefix}lti_key LIMIT 1");
            return $stmt->success;
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * Absolute URL of this admin request, without a query string.
     */
    protected function here() {
        return LTIX::curPageUrlNoQuery();
    }

    /**
     * Run a page that used to be a script under admin/.
     * The include writes the response. Exit so the application does not send a second one.
     */
    protected function view($template) {
        global $CFG, $OUTPUT, $PDOX, $LAUNCH, $USER, $CONTEXT, $LINK;
        $path = __DIR__.'/templates/Admin/'.$template;
        if ( ! is_file($path) ) {
            throw new \RuntimeException('Missing admin template '.$template);
        }
        include $path;
        exit;
    }

    /**
     * Warning for the admin console command list when any qa_ setting is turned on.
     *
     * The unlock form does not call this.
     */
    public static function qaNotice() {
        global $CFG;
        if ( ! is_object($CFG) || ! method_exists($CFG, 'enabledQaSettings') ) {
            return '';
        }
        $names = $CFG->enabledQaSettings();
        if ( count($names) < 1 ) {
            return '';
        }
        $shown = array();
        foreach ( $names as $name ) {
            $shown[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }
        return '<div class="alert alert-warning" style="margin: 10px;">'."\n"
            .'<p>QA settings enabled, these settings are not suitable for production systems: '
            .implode(', ', $shown).".</p>\n"
            ."</div>\n";
    }

    public function index() {
        if ( $r = $this->gate('admin.console') ) return $r;
        $this->view('console/index.php');
    }

    public function upgrade() {
        global $CFG;
        if ( $r = $this->gate('upgrade.run') ) return $r;
        require_once $CFG->dirroot.'/admin/upgrade.php';
        tsugi_admin_upgrade_run();
        exit;
    }

    public function clear12345() {
        if ( $r = $this->gate('admin.clear12345') ) return $r;
        $this->view('console/clear12345.php');
    }

    public function patchProfile() {
        if ( $r = $this->gate('admin.patch_profile') ) return $r;
        $this->view('console/patch_profile.php');
    }

    public function proxy() {
        if ( $r = $this->gate('admin.proxy', 'forbid') ) return $r;
        $this->view('console/proxy_small_json.php');
    }
}
