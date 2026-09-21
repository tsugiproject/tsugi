<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Core\ContextImages;
use Tsugi\Crypt\AesOpenSSL;
use Tsugi\UI\Table;
use Tsugi\UI\CrudForm;
use Tsugi\UI\SettingsDialog;
use Tsugi\UI\Supporter;
use Tsugi\UI\LessonsCartridge;
use Tsugi\Blob\BlobUtil;
use Tsugi\Core\Mail;
use Tsugi\Lumen\Application;
use Tsugi\Services\Settings\Expire;
use Tsugi\Services\Settings\DynamicRegistration;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Cartridge\Importer;
use Tsugi\Services\Cartridge\ImportException;
use Tsugi\Services\Cartridge\Package;
use Tsugi\Services\Cartridge\Pending;
use Tsugi\Services\CourseNav\CourseNav;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Instructor settings: site-wide at /settings, course-mounted at /courses/{id}/settings.
 *
 * Site-wide (wwwroot/settings): keys, contexts, PII expiry. Login is required;
 * a course context is not. Do not call requireAuth() on those pages.
 *
 * Course-mounted (/courses/{id}/settings): theme, navigation, images, and
 * Common Cartridge import/export for the active manifest. Instructor + manifest only. Nested dispatch from
 * Courses keeps REQUEST_URI prefixed, so isCourseRoute() can tell the two
 * families apart. File-based $CFG->lessons sites keep using the site $CFG->theme.
 *
 * Parent menus (site URLs only; instructors of a manifest course):
 * if ( \Tsugi\Controllers\Settings::showInMenu() ) {
 *     $set->addLink('Settings', rtrim($CFG->apphome, '/') . '/settings');
 * }
 */
class Settings extends Tool {

    const ROUTE = '/settings';
    const NAME = 'Settings';
    const REDIRECT = 'tsugi_controllers_settings';

    /** Keep in sync with Controllers/util/upload/.user.ini and .htaccess */
    const CARTRIDGE_UPLOAD_MAX = '128M';
    const CARTRIDGE_UPLOAD_PATH = '/lib/src/Controllers/util/upload/';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        self::mapPage($app, $prefix, 'index', true);
        self::mapPage($app, $prefix.'/index', 'index', true);
        $app->router->get('/'.self::REDIRECT, 'Settings@index');

        self::mapPage($app, $prefix.'/export/download', 'exportDownload', false);
        self::mapPage($app, $prefix.'/export', 'export', true);
        self::mapPage($app, $prefix.'/import', 'import', true);
        self::mapPage($app, $prefix.'/navigation', 'navigation', true);
        self::mapPage($app, $prefix.'/images', 'images', true);

        self::mapPage($app, $prefix.'/encrypt', 'encrypt', true);
        self::mapPage($app, $prefix.'/gclass_login', 'gclassLogin', false);
        self::mapPage($app, $prefix.'/recent', 'recent', false);
        self::mapPage($app, $prefix.'/privacy', 'privacy', false);
        self::mapPage($app, $prefix.'/privacy/index', 'privacy', false);

        self::mapPage($app, $prefix.'/context', 'contextIndex', false);
        self::mapPage($app, $prefix.'/context/index', 'contextIndex', false);
        self::mapPage($app, $prefix.'/context/context-settings', 'contextSettings', true);
        self::mapPage($app, $prefix.'/context/membership', 'contextMembership', false);
        self::mapPage($app, $prefix.'/context/member-detail', 'contextMemberDetail', true);
        self::mapPage($app, $prefix.'/context/mailing-list', 'contextMailingList', true);

        self::mapPage($app, $prefix.'/expire', 'expireIndex', false);
        self::mapPage($app, $prefix.'/expire/index', 'expireIndex', false);
        self::mapPage($app, $prefix.'/expire/pii-detail', 'expirePiiDetail', false);
        self::mapPage($app, $prefix.'/expire/pii-expire', 'expirePiiExpire', true);

        self::mapPage($app, $prefix.'/key', 'keyIndex', false);
        self::mapPage($app, $prefix.'/key/index', 'keyIndex', false);
        self::mapPage($app, $prefix.'/key/using', 'keyUsing', false);
        self::mapPage($app, $prefix.'/key/requests', 'keyRequests', true);
        self::mapPage($app, $prefix.'/key/request-detail', 'keyRequestDetail', true);
        self::mapPage($app, $prefix.'/key/key-detail', 'keyDetail', true);
        self::mapPage($app, $prefix.'/key/auto', 'keyAuto', true);
    }

    /**
     * GET (and optional POST) plus trailing-slash and legacy .php URLs.
     */
    private static function mapPage(Application $app, $path, $method, $alsoPost) {
        $handler = 'Settings@'.$method;
        $app->router->get($path, $handler);
        $app->router->get($path.'/', $handler);
        $app->router->get($path.'.php', $handler);
        if ( $alsoPost ) {
            $app->router->post($path, $handler);
            $app->router->post($path.'/', $handler);
            $app->router->post($path.'.php', $handler);
        }
    }

    /**
     * Absolute Settings URL under $CFG->wwwroot (not apphome).
     */
    public static function settingsUrl($suffix = '') {
        global $CFG;
        $base = rtrim((string)$CFG->wwwroot, '/') . self::ROUTE;
        $suffix = ltrim((string)$suffix, '/');
        if ( $suffix === '' ) {
            return $base;
        }
        return $base . '/' . $suffix;
    }

    protected function pageUrl($suffix = '') {
        return U::addSession(self::settingsUrl($suffix));
    }

    /**
     * Site-wide Settings pages always live at wwwroot/settings.
     *
     * @return RedirectResponse|null
     */
    protected function requireSiteRoute($suffix = '') {
        return $this->requireGlobalRoute($this->pageUrl($suffix));
    }

    /**
     * True when the current user may open course Settings (instructor of a manifest course).
     */
    public static function showInMenu() {
        if ( Manifest::activeId() < 1 ) {
            return false;
        }
        $tool = new self();
        return $tool->isInstructor();
    }

    /**
     * Login required, course context not required.
     *
     * @return RedirectResponse|null
     */
    protected function requireLogin($suffix = '') {
        $bounce = $this->requireSiteRoute($suffix);
        if ( $bounce ) {
            return $bounce;
        }
        if ( U::isLoggedIn() ) {
            LTIX::getConnection();
            return null;
        }
        $return = self::settingsUrl($suffix);
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ( is_string($qs) && $qs !== '' ) {
            $return .= (strpos($return, '?') === false ? '?' : '&') . $qs;
        }
        Login::setReturnUrl($return);
        return new RedirectResponse(Login::loginUrl());
    }

    /**
     * @return RedirectResponse|null
     */
    protected function requireKeysEnabled($instructorOnly = false) {
        global $CFG;
        if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
            $msg = $instructorOnly
                ? _m("This service does not accept instructor requests for keys")
                : _m("This service does not accept requests for keys");
            U::flashError($msg);
            return new RedirectResponse($CFG->wwwroot);
        }
        return null;
    }

    public static function tsugiRoot() {
        return dirname(__DIR__, 3);
    }

    public static function keyCount() {
        global $CFG, $PDOX;
        $sql = "SELECT count(key_id) AS count
            FROM {$CFG->dbprefix}lti_key
            WHERE user_id = :UID";
        $key_count = 0;
        $uid = U::loggedInUserId();
        if ( $uid ) {
            $row = $PDOX->rowDie($sql, array(':UID' => $uid));
            $key_count = U::get($row, 'count', 0);
        }
        return $key_count;
    }

    /**
     * HTML status blurb for the App Store (and similar).
     *
     * @return string
     */
    public static function statusHtml($key_count) {
        global $CFG;
        if ( ! U::isLoggedIn() ) {
            if ( $CFG->google_client_id ) {
                return "<p><b>You must log in to use these tools in your learning management system.  You can explore these tools and test them from this page without logging in.</b></p>";
            }
            return "<p><b>To get access to these tools in your learning management system, you will need to contact the owner of this system.</b></p>";
        } else if ( U::get($_SESSION,'gc_count') ) {
            return "<p><b>You have access to ".U::get($_SESSION,'gc_count')." Google Classroom courses.  Use the icons below to install the tools in your classes.</b></p>";
        } else if ( ! U::get($_SESSION,'gc_count') && $key_count < 1 &&
            ( isset($CFG->providekeys) || isset($CFG->google_classroom_secret) ) ) {
            $retval = "<p><b>You need to ";
            if ( $CFG->providekeys ) {
                $retval .= 'have an approved <a href="'.htmlspecialchars(self::settingsUrl(), ENT_QUOTES, 'UTF-8').'">LTI key</a>';
                if ( isset($CFG->google_classroom_secret) ) {
                    $retval .= " or\n";
                }
            }
            if ( isset($CFG->google_classroom_secret) ) {
                $retval .= 'log in to <a href="'.htmlspecialchars(self::settingsUrl('gclass_login'), ENT_QUOTES, 'UTF-8').'">Google Classroom</a>';
            }
            $retval .= " to use these tools.</b></p>\n";
            return $retval;
        }
        return '';
    }

    /**
     * Context row when the current user owns the context or its key.
     *
     * @return array|false
     */
    public static function contextAdministrable($context_id) {
        global $CFG, $PDOX;

        $uid = U::loggedInUserId();
        if ( ! $uid ) {
            return false;
        }

        $row = $PDOX->rowDie("SELECT context_id FROM {$CFG->dbprefix}lti_context
            WHERE context_id = :CID AND (
                key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
                OR user_id = :UID
            )",
            array(
                ':CID' => $context_id,
                ':UID' => $uid
            )
        );

        if ( $row === false || ! isset($row['context_id']) ) {
            return false;
        }

        return $row;
    }

    public function index(Request $request)
    {
        if ( self::isCourseRoute() ) {
            if ( $request->isMethod('POST') ) {
                return $this->coursePost($request);
            }
            return $this->courseGet($request);
        }

        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin();
        if ( $gate ) {
            return $gate;
        }

        $key_count = self::keyCount();

        $sql = "SELECT count(C.context_id) AS count
                FROM {$CFG->dbprefix}lti_context AS C
                LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
                WHERE C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
                 OR C.user_id = :UID";

        $course_count = 0;
        $uid = U::loggedInUserId();
        if ( $uid ) {
            $row = $PDOX->rowDie($sql, array(':UID' => $uid));
            $course_count = U::get($row, 'count', 0);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<h1>My Settings</h1>
<?php
        Supporter::renderThankYou($CFG);
        Supporter::renderInvite($CFG);
        ?>
<p>This page is for instructors to manage their courses and the use of these
applications in their courses.
</p>
<ul>
<li><p><a href="<?= htmlspecialchars($this->pageUrl('context'), ENT_QUOTES, 'UTF-8') ?>" aria-label="View My Contexts (Courses) - <?= (int)$course_count ?> context(s)">View My Contexts (Courses)</a>
(<?= (int)$course_count ?>)
</p>
</li>
<?php if ( $CFG->providekeys ) { ?>
<li><p><a href="<?= htmlspecialchars($this->pageUrl('key'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Manage LMS Access Keys - <?= (int)$key_count ?> key(s)">Manage LMS Access Keys</a>
(<?= (int)$key_count ?>)<br/>
These tools can be integrated into Learning Management Systems
that support the Learning Tools Interoperability specification.
</p>
</li>
<?php } ?>
<li><p><a href="<?= htmlspecialchars($this->pageUrl('expire'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Manage Data Expiry">Manage Data Expiry</a>
<br/>
This allows you to manage Personally Identifiable Information (PII) for your learners in this system.
</p>
</li>
<li><p><a href="<?= htmlspecialchars($this->pageUrl('encrypt'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Encrypt Strings">Encrypt Strings</a>
<br/>
Use this tool to encrypt strings using LTIX encryption methods. Note: This tool only supports encryption - decryption is only available to administrators.
</p>
</li>
<li><p><a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/cc/" target="_blank" rel="noopener noreferrer" aria-label="Download a copy of this course as an IMS Common Cartridge (opens in new window)">Download a copy of this course as an IMS Common Cartridge</a>
</br>
You can import this content into an LMS like Sakai, Canvas, Blackboard, D2L or Moodle.
</p>
</li>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<li><p>(Experimental) <a href="<?= htmlspecialchars($this->pageUrl('gclass_login'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Connect to Google Classroom">Connect to Google Classroom</a>
<?php
        $count = U::get($_SESSION,'gc_count');
        if ( $count ) {
            echo('(Connected to ' . (int)$count . ' classroom(s))');
        } else {
            echo('(Not connected)');
        }
        ?>
<br/>
These
<?php
        if ( isset($_SESSION['gc_count']) ) {
            echo('<a href="'.htmlspecialchars($CFG->wwwroot.'/store', ENT_QUOTES, 'UTF-8').'" aria-label="Browse tools in the store">tools</a>');
        } else {
            echo('tools');
        }
        ?>
 can be used in Google Classroom courses.
</p>
</li>
<li>
<p>
<a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer" aria-label="Manage my Google Account (opens in new window)">Manage my Google Account</a> (new window)<br/>
Use this page to view and manage which applications (including this one) that have access to your
Google information.
</p>
</li>
<?php } ?>
</ul>
<p>If you are an administrator for the overall site, you
can visit the administrator dashboard.
</p>
<p>
<strong>Note:</strong> The modal popups in this screen work best in the FireFox browser.
<?php
        Supporter::renderRenew($CFG);
        $OUTPUT->footer();
        return '';
    }

    public function encrypt(Request $request)
    {
        global $OUTPUT;

        $gate = $this->requireLogin('encrypt');
        if ( $gate ) {
            return $gate;
        }

        $encrypted_result = null;
        $error_message = null;
        $encrypt_value = '';
        $encrypt_secret = '';

        if (U::isKeyNotEmpty($_POST, "encrypt") && U::isKeyNotEmpty($_POST, "encrypt_value")) {
            $csrf = self::requireCsrf($this->pageUrl('encrypt'));
            if ( $csrf ) {
                return $csrf;
            }
            $plaintext = U::get($_POST, "encrypt_value");
            $secret = U::get($_POST, "encrypt_secret", "");

            $encrypt_value = $plaintext;
            $encrypt_secret = $secret;

            if ( !empty($plaintext) ) {
                try {
                    if ( !empty($secret) ) {
                        $encrypted_result = 'AES::' . AesOpenSSL::encrypt($plaintext, $secret);
                    } else {
                        $encrypted_result = LTIX::encrypt_secret($plaintext);
                    }
                    $_SESSION['encrypt_result'] = $encrypted_result;
                    $_SESSION['encrypt_value'] = $encrypt_value;
                    $_SESSION['encrypt_secret'] = $encrypt_secret;
                } catch (\Exception $e) {
                    $error_message = "Encryption error: " . htmlentities($e->getMessage());
                    $_SESSION['encrypt_error'] = $error_message;
                    $_SESSION['encrypt_value'] = $encrypt_value;
                    $_SESSION['encrypt_secret'] = $encrypt_secret;
                }
            } else {
                $error_message = "Please enter a value to encrypt";
                $_SESSION['encrypt_error'] = $error_message;
                $_SESSION['encrypt_value'] = $encrypt_value;
                $_SESSION['encrypt_secret'] = $encrypt_secret;
            }

            return new RedirectResponse($this->pageUrl('encrypt'));
        }

        if ( isset($_SESSION['encrypt_result']) ) {
            $encrypted_result = $_SESSION['encrypt_result'];
            unset($_SESSION['encrypt_result']);
        }
        if ( isset($_SESSION['encrypt_error']) ) {
            $error_message = $_SESSION['encrypt_error'];
            unset($_SESSION['encrypt_error']);
        }
        if ( isset($_SESSION['encrypt_value']) ) {
            $encrypt_value = $_SESSION['encrypt_value'];
            unset($_SESSION['encrypt_value']);
        }
        if ( isset($_SESSION['encrypt_secret']) ) {
            $encrypt_secret = $_SESSION['encrypt_secret'];
            unset($_SESSION['encrypt_secret']);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<h1>Encrypt Strings</h1>
<p>Use this tool to encrypt strings using LTIX encryption methods. This tool only supports encryption - decryption is only available to administrators.</p>

<?php if ( $error_message ) { ?>
<div class="alert alert-danger" role="alert"><?= $error_message ?></div>
<?php } ?>

<div class="form-group" style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
<h2>Encrypt String</h2>
<form method="POST" autocomplete="off">
<?= self::csrfField() ?>
<label for="encrypt_value">Enter plaintext to encrypt:</label><br/>
<textarea name="encrypt_value" id="encrypt_value" class="form-control" style="width: 100%; min-height: 100px; padding: 8px; font-family: monospace; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;" placeholder="Enter text to encrypt..."><?= htmlentities($encrypt_value) ?></textarea><br/>
<label for="encrypt_secret" style="margin-top: 10px;">Secret (optional - if provided, uses AesOpenSSL::encrypt):</label><br/>
<textarea name="encrypt_secret" id="encrypt_secret" class="form-control" style="width: 100%; min-height: 40px; max-height: 80px; padding: 8px; font-family: monospace; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; margin-top: 5px; resize: vertical;" autocomplete="off" placeholder="Leave empty to use default LTIX encryption"><?= htmlentities($encrypt_secret) ?></textarea><br/>
<input type="submit" name="encrypt" value="Encrypt" class="btn btn-primary" style="margin-top: 10px;">
</form>
<?php if ( $encrypted_result !== null ) { ?>
<div class="alert alert-success" style="margin-top: 15px; padding: 10px; background-color: #e8f5e9; border: 1px solid #4CAF50; border-radius: 3px;">
<div style="font-weight: bold; color: #2e7d32; margin-bottom: 5px;">Encrypted Result:</div>
<div style="font-family: monospace; word-break: break-all; white-space: pre-wrap;"><?= htmlentities($encrypted_result) ?></div>
</div>
<?php } ?>
</div>

<p>
<a href="<?= htmlspecialchars($this->pageUrl(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="Back to Settings">Back to Settings</a>
</p>
<?php
        $OUTPUT->footer();
        return '';
    }

    public function gclassLogin(Request $request)
    {
        global $CFG;
        Login::setReturnUrl(self::settingsUrl());
        return new RedirectResponse($CFG->wwwroot . '/gclass/login');
    }

    public function recent(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! defined('TSUGI_RECENT_ENABLED') || ! TSUGI_RECENT_ENABLED ) {
            die('This needs more work in lti_membership');
        }

        $gate = $this->requireLogin('recent');
        if ( $gate ) {
            return $gate;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();

        $query_parms = array();
        $searchfields = array("email", "displayname", "ipaddr");
        $orderfields = array("login_at", "login_count");
        $params = $_GET;
        if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
            $params['order_by'] = 'login_at';
            $params['desc'] = '1';
        }
        $params['page_length'] = 15;
        $user_sql =
            "SELECT email, displayname, login_at, login_count, ipaddr FROM {$CFG->dbprefix}lti_user";

        Table::pagedAuto($user_sql, $query_parms, $searchfields, $orderfields, false, $params);

        $OUTPUT->footer();
        return '';
    }

    public function privacy(Request $request)
    {
        global $CFG, $OUTPUT;

        $gate = $this->requireLogin('privacy');
        if ( $gate ) {
            return $gate;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<h1>Privacy Management</h1>
<p>
  <a href="<?= htmlspecialchars($this->pageUrl(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default">My Settings</a>
</p>
<p>Privacy management is not yet implemented.</p>
<?php
        $OUTPUT->footer();
        return '';
    }

    public function contextIndex(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('context');
        if ( $gate ) {
            return $gate;
        }

        header('Content-Type: text/html; charset=utf-8');

        $query_parms = array(":UID" => U::loggedInUserId());
        $searchfields = array("C.context_id", "title", "C.created_at", "C.updated_at", "C.login_at", "C.login_count");
        $sql = "SELECT C.context_id AS context_id, title, count(M.user_id) AS members, C.key_id AS key_value,
                    C.login_at, C.login_count, C.created_at, C.updated_at
                FROM {$CFG->dbprefix}lti_context AS C
                LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
                WHERE C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
                 OR C.user_id = :UID
                GROUP BY C.context_id";
        $orderfields = array("C.context_id", "key_value", "title", "C.created_at", "C.updated_at", "C.login_at", "C.login_count");

        $newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        $newrows = array();
        foreach ( $rows as $row ) {
            $newrows[] = $row;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
<h1>Contexts</h1>
<?php
        $OUTPUT->flashMessages();

        $extra_buttons = array(__("My Settings") => htmlspecialchars(self::settingsUrl(), ENT_QUOTES, 'UTF-8'));
        Table::pagedTable($newrows, $searchfields, $orderfields, self::settingsUrl('context/membership'), false, $extra_buttons);

        $OUTPUT->footer();
        return '';
    }

    public function contextSettings(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('context/context-settings');
        if ( $gate ) {
            return $gate;
        }

        header('Content-Type: text/html; charset=utf-8');

        if ( ! isset($_REQUEST['context_id']) ) {
            U::flashError("No context_id provided");
            return new RedirectResponse($this->pageUrl('context'));
        }

        $context_id = $_REQUEST['context_id'];

        $context_check = self::contextAdministrable($context_id);
        if ( $context_check === false ) {
            U::flashError("You do not have access to this context");
            return new RedirectResponse($this->pageUrl('context'));
        }

        if ( isset($_POST['settings_json']) ) {
            $csrf = self::requireCsrf($this->pageUrl('context/context-settings').'?context_id='.urlencode((string)$context_id));
            if ( $csrf ) {
                return $csrf;
            }
            $settings_json = trim($_POST['settings_json']);

            if ( !empty($settings_json) ) {
                $decoded = json_decode($settings_json, true);
                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    U::flashError("Invalid JSON: " . json_last_error_msg());
                } else {
                    $compact_json = json_encode($decoded);
                    $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_context
                        SET settings = :SETTINGS, updated_at = NOW()
                        WHERE context_id = :CID",
                        array(
                            ':SETTINGS' => $compact_json,
                            ':CID' => $context_id
                        )
                    );

                    if ( $stmt->success ) {
                        U::flashSuccess("Context settings updated successfully");
                    } else {
                        U::flashError("Failed to update settings");
                    }
                }
            } else {
                $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_context
                    SET settings = NULL, updated_at = NOW()
                    WHERE context_id = :CID",
                    array(':CID' => $context_id)
                );

                if ( $stmt->success ) {
                    U::flashSuccess("Context settings cleared successfully");
                } else {
                    U::flashError("Failed to clear settings");
                }
            }
            return new RedirectResponse($this->pageUrl('context/context-settings').'?context_id='.urlencode((string)$context_id));
        }

        $context_row = $PDOX->rowDie("SELECT context_id, title, settings FROM {$CFG->dbprefix}lti_context
            WHERE context_id = :CID",
            array(':CID' => $context_id)
        );

        if ( $context_row === false ) {
            U::flashError("Context not found");
            return new RedirectResponse($this->pageUrl('context'));
        }

        $context_title = $context_row['title'] ? $context_row['title'] : "Context ID: $context_id";
        $current_settings = $context_row['settings'];

        $settings_display = '';
        if ( !empty($current_settings) ) {
            $decoded = json_decode($current_settings, true);
            if ( $decoded !== null ) {
                $settings_display = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $settings_display = $current_settings;
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<h1>Context Settings</h1>
<p>
  <a href="<?= htmlspecialchars($this->pageUrl('context'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="View Contexts">View Contexts</a>
  <a href="<?= htmlspecialchars($this->pageUrl('context/membership').'?context_id='.urlencode((string)$context_id), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="View Memberships">View Memberships</a>
</p>

<h2><?= htmlentities($context_title) ?></h2>

<form method="post">
    <?= self::csrfField() ?>
    <div class="form-group">
        <label for="settings_json">Context Settings (JSON)</label>
        <textarea class="form-control" id="settings_json" name="settings_json" rows="20"
            style="font-family: monospace; font-size: 12px;"><?= htmlentities($settings_display) ?></textarea>
        <small class="form-text text-muted">
            Enter valid JSON data. Leave empty to clear all settings. The settings are stored in the
            <code>settings</code> column of the <code>lti_context</code> table.
        </small>
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="<?= htmlspecialchars($this->pageUrl('context'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="Cancel and return to contexts">Cancel</a>
</form>
<?php
        $OUTPUT->footer();
        return '';
    }

    public function contextMembership(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('context/membership');
        if ( $gate ) {
            return $gate;
        }

        header('Content-Type: text/html; charset=utf-8');

        if ( ! isset($_REQUEST['context_id']) ) {
            U::flashError("No context_id provided");
            return new RedirectResponse($this->pageUrl('context'));
        }

        $context_id = $_REQUEST['context_id'];

        $context_check = self::contextAdministrable($context_id);
        if ( $context_check === false ) {
            U::flashError("You do not have access to this context");
            return new RedirectResponse($this->pageUrl('context'));
        }

        $query_parms = array(":CID" => $context_id);

        $searchfields = array("M.membership_id", "C.context_id", "M.user_id", "role", "role_override",
            "M.created_at", "M.updated_at", "email", "displayname", "user_key");

        $sql = "SELECT membership_id, 'detail' AS 'Membership', M.context_id AS Context, M.user_id as User,
                    role, role_override, M.created_at, M.updated_at, email, displayname, user_key
                FROM {$CFG->dbprefix}lti_membership as M
                JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
                JOIN {$CFG->dbprefix}lti_context AS C ON M.context_id = C.context_id
                WHERE M.context_id = :CID";

        $newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        $newrows = array();
        foreach ( $rows as $row ) {
            $newrows[] = $row;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<p>
  <a href="<?= htmlspecialchars($this->pageUrl('context'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="View Contexts">View Contexts</a>
  <a href="<?= htmlspecialchars($this->pageUrl('context/context-settings').'?context_id='.urlencode((string)$context_id), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success">View/Edit Context Settings</a>
  <a href="<?= htmlspecialchars($this->pageUrl('context/mailing-list').'?context_id='.urlencode((string)$context_id), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Generate Mailing List</a>
</p>
<h1>Roster / Membership</h1>
<?php
        $extra_buttons = array(__("All Contexts") => htmlspecialchars(self::settingsUrl('context'), ENT_QUOTES, 'UTF-8'));
        Table::pagedTable($newrows, $searchfields, $searchfields, self::settingsUrl('context/member-detail'), false, $extra_buttons);

        $OUTPUT->footer();
        return '';
    }

    public function contextMemberDetail(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('context/member-detail');
        if ( $gate ) {
            return $gate;
        }

        header('Content-Type: text/html; charset=utf-8');

        if ( ! isset($_REQUEST['membership_id']) ) {
            die('No membership_id');
        }

        $uid = U::loggedInUserId();
        $row = $PDOX->rowDie("SELECT M.context_id
            FROM {$CFG->dbprefix}lti_membership AS M
            JOIN {$CFG->dbprefix}lti_context AS C ON M.context_id = C.context_id
            WHERE membership_id = :MID AND (
                C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
                OR C.user_id = :UID2
            )",
            array(
                ':MID' => $_REQUEST['membership_id'],
                ':UID' => $uid,
                ':UID2' => $uid)
        );

        if ( $row === false || ! isset($row['context_id']) ) {
            die('Bad membership_id');
        }

        $tablename = "{$CFG->dbprefix}lti_membership";
        $current = self::settingsUrl('context/member-detail');
        $from_location = $this->pageUrl('context/membership').'?context_id='.$row['context_id'];
        $allow_delete = true;
        $allow_edit = true;
        $where_clause = "context_id IN (
            SELECT context_id FROM {$CFG->dbprefix}lti_context
            WHERE (
                key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID)
                OR user_id = :UID2
            )
        )";
        $query_fields = array(':UID' => $uid, ':UID2' => $uid);
        $fields = array("membership_id", "context_id", "user_id", "role_override", "created_at", "updated_at");

        $row = CrudForm::handleUpdate($tablename, $fields, $where_clause,
            $query_fields, $allow_edit, $allow_delete);

        if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
            return new RedirectResponse($from_location);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $title = "Membership";
        echo("<h1>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>\n<p>\n");
        $retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
        if ( is_string($retval) ) die($retval);
        echo("</p>\n");

        $OUTPUT->footer();
        return '';
    }

    public function contextMailingList(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('context/mailing-list');
        if ( $gate ) {
            return $gate;
        }

        require_once self::tsugiRoot() . '/admin/context/mail_audience.php';

        header('Content-Type: text/html; charset=utf-8');

        if ( ! isset($_REQUEST['context_id']) ) {
            U::flashError("No context_id provided");
            return new RedirectResponse($this->pageUrl('context'));
        }

        if ( ! is_numeric($_REQUEST['context_id']) ) {
            U::flashError("Invalid context_id");
            return new RedirectResponse($this->pageUrl('context'));
        }

        $context_id = $_REQUEST['context_id'] + 0;

        $context_check = self::contextAdministrable($context_id);
        if ( $context_check === false ) {
            U::flashError("You do not have access to this context");
            return new RedirectResponse($this->pageUrl('context'));
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['days']) ) {
            $csrf = self::requireCsrf($this->pageUrl('context/mailing-list').'?context_id='.$context_id);
            if ( $csrf ) {
                return $csrf;
            }
            $days = $_POST['days'] + 0;
            if ( !is_numeric($_POST['days']) || $days < 1 || $days > 365 ) {
                U::flashError("Days must be between 1 and 365");
                return new RedirectResponse($this->pageUrl('context/mailing-list').'?context_id='.$context_id);
            }
            $include_opted_out = isset($_POST['include_opted_out']) ? 1 : 0;
            $premium_only = isset($_POST['premium_only']) ? 1 : 0;
            return new RedirectResponse($this->pageUrl('context/mailing-list').'?context_id='.$context_id.'&days='.$days
                .'&include_opted_out='.$include_opted_out.'&premium_only='.$premium_only);
        }

        $days = null;
        $include_opted_out = false;
        $premium_only = false;
        if ( isset($_REQUEST['days']) && is_numeric($_REQUEST['days']) ) {
            $days = $_REQUEST['days'] + 0;
            if ( $days < 1 || $days > 365 ) {
                U::flashError("Days must be between 1 and 365");
                $days = null;
            }
        }
        if ( isset($_REQUEST['include_opted_out']) && $_REQUEST['include_opted_out'] == '1' ) {
            $include_opted_out = true;
        }
        if ( isset($_REQUEST['premium_only']) && $_REQUEST['premium_only'] == '1' ) {
            $premium_only = true;
        }

        $context_row = $PDOX->rowDie(
            "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        $context_title = $context_row ? $context_row['title'] : "Context #$context_id";

        $rows = array();
        if ( $days !== null ) {
            $rows = mail_context_audience($context_id, $days, $include_opted_out, $premium_only);
        }

        $membership_url = $this->pageUrl('context/membership').'?context_id='.$context_id;
        $form_url = $this->pageUrl('context/mailing-list');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Settings/mailing-list.inc.php';
        $OUTPUT->footer();
        return '';
    }

    public function expireIndex(Request $request)
    {
        global $CFG, $OUTPUT;

        $gate = $this->requireLogin('expire');
        if ( $gate ) {
            return $gate;
        }

        $user_count = Expire::countTable('lti_user');
        $pii_days = isset($CFG->expire_pii_days) ? $CFG->expire_pii_days : 180;
        $pii_days = U::get($_GET,'pii_days',$pii_days);
        Expire::$pii_days = $pii_days;
        $pii_expire = Expire::piiCount($pii_days);

        $check = Expire::sanityCheckDays();
        if ( is_string($check) ) U::flashError($check);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;" role="dialog" aria-modal="true" aria-label="Read only content">
   <div id="iframe-spinner" role="status" aria-live="polite">
   <img src="<?= htmlspecialchars($OUTPUT->getSpinnerUrl(), ENT_QUOTES, 'UTF-8') ?>" alt="" role="presentation"><br/>
   <span class="sr-only">Loading content</span>
   </div>
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame" title="Data expiry content viewer"
    onload="document.getElementById('iframe-spinner').style.display='none';">
   </iframe>
</div>
<h1>Manage Data Expiry</h1>
<p>
  <a href="<?= htmlspecialchars($this->pageUrl(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="My Settings">My Settings</a>
</p>
<form>
<ul>
<li>User count: <?= (int)$user_count ?>  <br/>
<ul>
<li>
<label for="pii_days">Users with PII and no activity in</label>
<input type="text" name="pii_days" id="pii_days" size=5 class="auto_days" value="<?= htmlspecialchars($pii_days, ENT_QUOTES, 'UTF-8') ?>"> days:
<?= (int)$pii_expire ?>
<?php if ( $pii_expire > 0 ) { ?>
  <br/>
  <a href="<?= htmlspecialchars($this->pageUrl('expire/pii-detail').'?pii_days='.urlencode((string)$pii_days), ENT_QUOTES, 'UTF-8') ?>" class="auto_expire btn btn-xs btn-default" aria-label="View PII expiry details">View</a>
  <a href="#" title="Expire PII" class="auto_expire btn btn-xs btn-danger" role="button" aria-label="Expire PII older than <?= (int)$pii_days ?> days"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', <?= htmlspecialchars(json_encode($this->pageUrl('expire/pii-expire').'?pii_days='.urlencode((string)$pii_days)), ENT_QUOTES, 'UTF-8') ?>, _TSUGI.spinnerUrl, true); return false;" >
  Expire PII &gt; <?= (int)$pii_days ?> Days
  </a>
<?php } ?>
</li>
</ul>
</ul>
<input type="submit" value="Update">
</form>
<?php
        $OUTPUT->footerStart();
        ?>
<script>
$('.auto_days').on('change', function() {
  $(".auto_expire").hide();
  $(this).closest('form').submit();
});
</script>
<?php
        $OUTPUT->footerEnd();
        return '';
    }

    public function expirePiiDetail(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('expire/pii-detail');
        if ( $gate ) {
            return $gate;
        }

        if ( ! isset($_GET['pii_days']) ) die('Required parameter pii_days');
        if ( ! is_numeric($_GET['pii_days']) ) die('pii_days must be a number');
        $days = $_GET['pii_days'] + 0;
        if ($days < 1 ) die('bad value for pii_days');

        $fields = array('login_at', 'user_id', 'email', 'displayname', 'created_at');

        $where = Expire::piiWhere($days);
        $sql = "SELECT login_at, user_id, email, displayname, email, created_at
                FROM {$CFG->dbprefix}lti_user " . $where['sql'];

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $extra_buttons = array(
            "Summary" => $this->pageUrl('expire')
        );

        $query_parms = $where['params'];
        $searchfields = $fields;
        $orderfields = $fields;
        $newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        $newrows = array();
        foreach ( $rows as $row ) {
            $newrows[] = $row;
        }

        Table::pagedTable($newrows, $searchfields, $orderfields, false, false, $extra_buttons);

        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
        return '';
    }

    public function expirePiiExpire(Request $request)
    {
        global $CFG, $PDOX;

        $gate = $this->requireLogin('expire/pii-expire');
        if ( $gate ) {
            return $gate;
        }

        $limit = 5000;
        if ( !is_numeric($limit) || $limit < 1 ) die('Invalid limit value');
        $limit = (int)$limit;

        if ( ! isset($_REQUEST['pii_days']) ) die('Required parameter pii_days');
        if ( ! is_numeric($_REQUEST['pii_days']) ) die('pii_days must be a number');
        $days = $_REQUEST['pii_days'] + 0;
        if ($days < 1 ) die('bad value for pii_days');

        $check = Expire::sanityCheckDays('PII', $days);
        if ( is_string($check) ) die($check);

        $pii_count = Expire::piiCount($days);

        $where = Expire::piiWhere($days);
        $sql = "UPDATE {$CFG->dbprefix}lti_user
            SET displayname=NULL, email=NULL " . $where['sql'] . "
            ORDER BY login_at LIMIT " . $limit;
        $params = $where['params'];

        $sql_display = \Tsugi\Util\PDOX::sqlDisplay($sql, $params);

        if ( isset($_POST['doDelete']) && isset($_POST['pii_days']) ) {
            $csrf = self::requireCsrf($this->pageUrl('expire/pii-expire').'?pii_days='.urlencode((string)$days));
            if ( $csrf ) {
                return $csrf;
            }
            echo("<pre>\n");
            $start = time();

            $stmt = $PDOX->prepare($sql);
            $stmt->execute($params);

            $count = $stmt->rowCount();
            echo("Rows updated: $count\n");
            $delta = time() - $start;
            echo("\nEllapsed time: $delta seconds\n");
            echo("</pre>\n");
            echo("<p>Process complete - you can close this window.</p>\n");
            return '';
        }

        ?>
<h1>Expire Personally Identifable Information</h1>
<p>
Preparing to delete PII &gt; <?= $days ?>  days old for <?= $pii_count ?> users
using the following SQL:
<pre>
<?= htmlspecialchars($sql_display) ?>
</pre>
<form method="post">
<?= self::csrfField() ?>
<input type="hidden" name="pii_days" value="<?= $days ?>">
<input type="submit" name="doDelete" value="Delete PII for <?= $pii_count ?> Users">
</form>
<p>
Note that online we limit the number of records that an be deleted per request to
keep requests from timing out.   Server administrators have offline commands they
can run on the server to expire more data at once or even automate the expiry
of this type of data.
</p>
<?php
        return '';
    }

    public function keyIndex(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $keys = $this->requireKeysEnabled();
        if ( $keys ) {
            return $keys;
        }

        header('Content-Type: text/html; charset=utf-8');

        $gate = $this->requireLogin('key');
        if ( $gate ) {
            return $gate;
        }

        $query_parms = array(":UID" => U::loggedInUserId());
        $searchfields = array("key_id", "key_key", "created_at", "updated_at", "user_id");
        $sql = "SELECT key_id, key_key, secret, login_at, created_at, updated_at, user_id
                FROM {$CFG->dbprefix}lti_key
                WHERE user_id = :UID";

        $newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        $newrows = array();
        foreach ( $rows as $row ) {
            $newrow = $row;
            $newrow['secret'] = '****';
            $newrows[] = $newrow;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $this->keyNav('keys');
        if ( count($newrows) < 1 ) { ?>
<p>
You have no LTI Keys for this system.
</p>
<p>
If you want to use the tools / content in this system
in an LMS like Sakai, Moodle, Canvas, Blackboard or BrightSpace
you will need to request a key and have it approved.
</p>
<?php } else {
            Table::pagedTable($newrows, $searchfields, false, self::settingsUrl('key/key-detail'), false);
        }

        $OUTPUT->footer();
        return '';
    }

    public function keyDetail(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $gate = $this->requireLogin('key/key-detail');
        if ( $gate ) {
            return $gate;
        }

        require_once self::tsugiRoot() . '/admin/key/key-util.php';

        header('Content-Type: text/html; charset=utf-8');

        $tablename = "{$CFG->dbprefix}lti_key";
        $current = self::settingsUrl('key/key-detail');
        $from_location = $this->pageUrl('key');
        $allow_delete = true;
        $allow_edit = true;
        $where_clause = '';
        $query_fields = array();
        $fields = array('key_id', 'key_title', 'key_key', 'secret', 'deploy_key', 'updated_at');

        $titles = array(
            'key_key' => 'LTI 1.1: OAuth Consumer Key',
            'secret' => 'LTI 1.1: OAuth Consumer Secret',
            'deploy_key' => 'LTI 1.3: Deployment ID (leave blank to accept any value from the LMS)',
        );

        $where_clause .= "user_id = :UID";
        $query_fields[":UID"] = U::loggedInUserId();

        if ( ! isset($_REQUEST['key_id']) || ! is_numeric($_REQUEST['key_id']) ) {
            U::flashError("Required key_id parameter");
            return new RedirectResponse($from_location);
        }
        $sql = CrudForm::selectSql($tablename, $fields, $where_clause . " AND key_id = :KID");
        $oldrow = $PDOX->rowDie($sql, $query_fields + array(':KID' => $_REQUEST['key_id'] + 0));
        if ( $oldrow === false ) {
            U::flashError("Unable to retrieve row");
            return new RedirectResponse($from_location);
        }

        if ( U::get($_POST,'key_key') && U::get($_POST,'key_key') != $oldrow['key_key'] ) {
            U::flashError("Cannot change key value");
            return new RedirectResponse($from_location);
        }

        if ( isset($_POST['deploy_key']) ) {
            $_POST['deploy_key'] = normalize_deploy_key_input($_POST['deploy_key']);
        }

        $row = CrudForm::handleUpdate($tablename, $fields, $where_clause,
            $query_fields, $allow_edit, $allow_delete);

        if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
            return new RedirectResponse($from_location);
        }
        if ( ! is_array($row) ) {
            U::flashError('Unable to load key details');
            return new RedirectResponse($from_location);
        }

        $key = new \Tsugi\Core\Key();
        $key->id = $row['key_id'];
        $key->title = $row['key_title'];
        $launch = new \Tsugi\Core\Launch();
        $launch->pdox = $PDOX;
        $key->launch = $launch;

        $settingsDialog = new SettingsDialog($key);
        $settingsDialog->instructor_override = true;
        $settingsDialog->ready_override = true;
        if ( $settingsDialog->handleSettingsPost() ) {
            U::flashSuccess(__('Settings updated'));
            unset($_SESSION['key_settings']);
            return new RedirectResponse($this->pageUrl('key/key-detail').'?key_id='.htmlentities($_REQUEST['key_id']));
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $title = __("Key Detail");
        echo("<h1>$title</h1>\n<p>\n");
        $extra_buttons = array(
            __('Settings') => '<a href="#" class="btn btn-success" '.$settingsDialog->attr().'>'.__('Settings').'</a>'."\n",
        );

        $settingsDialog->start();
        $settingsDialog->color('primary-menu',__('Menu background color.'));
        $settingsDialog->end();

        $retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, $extra_buttons,$titles);
        if ( is_string($retval) ) die($retval);
        echo("</p>\n");

        $autoConfigUrl = self::settingsUrl('key/auto.php') . "?tsugi_key=" . $row['key_id'];
        ?>
<p>
<b>LTI Advantage Auto Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $autoConfigUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
<br/><?= htmlentities($autoConfigUrl) ?>
<p>
To use the auto configuration URL in your Learning Management System,
keep this window open in a separate tab while using the LMS in another tab
as the LTI Advantage auto configuration process requires that you are logged in to this system
in order to complete the auto configuration process.
</p>
<?php
        $OUTPUT->footerStart();
        ?>
<script>
$(document).ready( function() {
        $( "#key_key_label" ).after(
            ' <button onclick="copyToClipboardNoScroll(this, $(\'#key_key\').text());return false;">' +
            '<i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button>' +
            '</p>'
        );
        $( "#secret_label" ).after(
            ' <button onclick="copyToClipboardNoScroll(this, $(\'#text_3\').text());return false;">' +
            '<i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button>' +
            '</p>'
        );
        document.getElementById("key_key").readOnly = true;
        document.getElementById("key_key").disabled = true;
});
</script>
<?php
        $OUTPUT->footerEnd();
        return '';
    }

    public function keyRequests(Request $request)
    {
        global $CFG, $PDOX, $OUTPUT;

        $keys = $this->requireKeysEnabled();
        if ( $keys ) {
            return $keys;
        }

        header('Content-Type: text/html; charset=utf-8');

        $gate = $this->requireLogin('key/requests');
        if ( $gate ) {
            return $gate;
        }

        $goodsession = U::isLoggedIn() && isset($_SESSION['email']) && isset($_SESSION['displayname']) &&
            U::strlen($_SESSION['email']) > 0 && U::strlen($_SESSION['displayname']) > 0 ;

        if ( $goodsession && isset($_POST['title']) && isset($_POST['notes']) ) {
            $csrf = self::requireCsrf($this->pageUrl('key/requests'));
            if ( $csrf ) {
                return $csrf;
            }
            if ( U::strlen($_POST['title']) < 1 ) {
                U::flashError(_m("Requests must have titles"));
                return new RedirectResponse($this->pageUrl('key/requests'));
            }
            if ( U::strlen($_POST['notes']) < 1 ) {
                U::flashError(_m("You must include a reason (i.e. what course you are teaching) in this request."));
                return new RedirectResponse($this->pageUrl('key/requests'));
            }
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}key_request
                (user_id, title, notes, state, lti, created_at, updated_at)
                VALUES ( :UID, :TITLE, :NOTES, 0, :LTI, NOW(), NOW() )",
                array(":UID" => U::loggedInUserId(), ":TITLE" => $_POST['title'],
                    ":NOTES" => $_POST['notes'], ":LTI" => 1)
            );

            $request_id = $PDOX->lastInsertId();
            if ( isset($CFG->autoapprovekeys) && U::strlen($CFG->autoapprovekeys) > 0 &&
                preg_match($CFG->autoapprovekeys, $_SESSION['email']) == 1) {
                $user_id = U::loggedInUserId();
                $token = Mail::computeCheck($user_id);
                $to = $_SESSION['email'];

                $subject = false;
                $message = '';
                if ( $CFG->owneremail ) {
                    $subject = "Key Created on ".$CFG->servicename." for ".$_SESSION['email'];
                    $message = "Key Created on ".$CFG->servicename." for ".$_SESSION['email'].
                        "\nSystem Admin: ".$CFG->ownername." (".$CFG->owneremail.")\n";
                }

                $oauth_consumer_key = 'lti1i_'.bin2hex(openssl_random_pseudo_bytes(256/8));
                $oauth_secret = bin2hex(openssl_random_pseudo_bytes(256/8));
                $key_sha256 = lti_sha256($oauth_consumer_key);
                $PDOX->queryDie(
                    "INSERT INTO {$CFG->dbprefix}lti_key
                        (key_sha256, key_key, secret, user_id, created_at, updated_at)
                        VALUES ( :k256, :key, :secret, :uid, NOW(), NOW() )",
                    array(
                    'k256' => $key_sha256,
                        'key' => $oauth_consumer_key,
                        'secret' => $oauth_secret,
                        'uid' => $user_id
                    )
                );

                $admin_message = $message;
                $message .= "\n\nKey: $oauth_consumer_key\n";
                $message .= "\nSecret: $oauth_secret\n";
                $message .= "\nInstructions for using your LTI key are at\n\n";
                $message .= self::settingsUrl('key/using')."\n\n";
                error_log("New LTI Key Inserted: $oauth_consumer_key User: ".$_SESSION['email']);

                $PDOX->queryDie(
                    "UPDATE {$CFG->dbprefix}key_request SET state=1 WHERE request_id = :rid",
                    array('rid' => $request_id)
                );

                U::flashSuccess("Key Approved");
                if ( $subject ) {
                    U::flashSuccess("Key Approved - Check your email ".$to);
                    error_log("Email sent to $to, Subject: $subject");
                    Mail::sendTransactional($to, $subject, $message, $user_id, $token);
                    if ( $CFG->owneremail ) {
                        $subject = '[admin] ' . $subject;
                        error_log("Email sent to $CFG->owneremail, Subject: $subject");
                        Mail::sendTransactional($CFG->owneremail, $subject, $admin_message);
                    }
                }
                return new RedirectResponse($this->pageUrl('key'));
            }

            if ( $CFG->owneremail && $CFG->OFFLINE === false) {
                $user_id = U::loggedInUserId();
                $token = Mail::computeCheck($user_id);
                $to = $CFG->owneremail;
                $subject = "Key Request from ".$_SESSION['displayname'].' ('.$_SESSION['email'].' )';
                $message = "Key Request from ".$_SESSION['displayname'].' ('.$_SESSION['email'].' )\n'.
                    "\nNotes\n".$_POST['notes']."\n\n".
                    "Link: ".$CFG->wwwroot."/admin/key\n";

                Mail::sendTransactional($to, $subject, $message, $user_id, $token);
            }
            U::flashSuccess("Record inserted");
            return new RedirectResponse($this->pageUrl('key'));
        }

        $query_parms = array(":UID" => U::loggedInUserId());
        $searchfields = array("request_id", "title", "notes", "state", "admin", "email", "displayname", "R.created_at", "R.updated_at");
        $sql = "SELECT request_id, title, notes, state, admin,
                R.created_at, R.updated_at, email, displayname
                FROM {$CFG->dbprefix}key_request  as R
                JOIN {$CFG->dbprefix}lti_user AS U ON R.user_id = U.user_id
                WHERE R.user_id = :UID";

        $newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
        $rows = $PDOX->allRowsDie($newsql, $query_parms);
        $newrows = array();
        foreach ( $rows as $row ) {
            $newrow = $row;
            $state = $row['state'];
            if ( $state == 0 ) {
                $newrow['state'] = "0 (Waiting)";
            } else if ( $state == 1 ) {
                $newrow['state'] = "1 (Approved)";
            } else if ( $state == 2 ) {
                $newrow['state'] = "2 (Not approved)";
            }
            $newrows[] = $newrow;
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $this->keyNav('requests');
        ?>
<p>
If you are a teacher and want to use the interactive elements on this web
site using Learning Tools Interoperability, you can request a key
from this page.  Please include a description of how you are
planning on using your key.
</p>
<?php if ( isset($CFG->google_classroom_secret) ) { ?>
<p>
If you are using Google Classroom, there is no need to request a key, simply
connect to Google Classroom and install tools.
</p>
<?php } ?>
<?php if ( $goodsession ) { ?>
<div class="modal fade" id="request">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="request_form" method="post">
      <?= self::csrfField() ?>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Request an API Key</h4>
      </div>
      <div class="modal-body">
            <p>Please indicate how you will be using the key below (i.e. the school where you are
            teaching and if applicable the course you are teaching).
            Students do not need a key to use this site.  Keys are for instructors to use in an LMS.</p>
            </p>
            <div class="form-group">
                <label for="request_name">Name:</label>
                <input type="name" class="form-control" id="request_name" disabled
                value="<?php echo(htmlent_utf8($_SESSION['displayname'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_email">Email:</label>
                <input type="name" class="form-control" id="request_email" disabled
                value="<?php echo(htmlent_utf8($_SESSION['email'])); ?>">
            </div>
            <div class="form-group">
                <label for="request_title">Key Title: (Required)</label>
                <input type="name" class="form-control" id="request_title" name="title" required="required">
            </div>

            <label for="request_reason">Comments: (required)</label>
            <textarea class="form-control" id="request_reason" name="notes" rows="6"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <input type="submit" id="request_save" class="btn btn-primary" value="Submit Request">
      </div>
      </form>
    </div>
  </div>
</div>
<?php
            Table::pagedTable($newrows, $searchfields, false, self::settingsUrl('key/request-detail'));
            ?>
<p>
<button type="button" class="btn btn-default" onclick="$('#request').modal();return false;">New Key Request</button>
</p>
<?php
        }
        $OUTPUT->footer();
        return '';
    }

    public function keyRequestDetail(Request $request)
    {
        global $CFG, $OUTPUT;

        $gate = $this->requireLogin('key/request-detail');
        if ( $gate ) {
            return $gate;
        }

        require_once self::tsugiRoot() . '/admin/admin_util.php';

        header('Content-Type: text/html; charset=utf-8');

        $tablename = "{$CFG->dbprefix}key_request";
        $current = self::settingsUrl('key/request-detail');
        $title = "Request Entry";
        $from_location = $this->pageUrl('key/requests');
        $allow_delete = true;
        $allow_edit = true;
        $fields = array("request_id", "title", "notes", "admin", "state", "lti", "created_at", "updated_at");
        $where_clause = "user_id = :UID";
        $query_fields = array();
        $query_fields[":UID"] = U::loggedInUserId();

        $row = CrudForm::handleUpdate($tablename, $fields, $where_clause,
            $query_fields, $allow_edit, $allow_delete);

        if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
            return new RedirectResponse($from_location);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        echo("<h1>$title</h1>\n<p>\n");
        $extra_buttons = false;
        $retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, $extra_buttons);
        if ( is_string($retval) ) die($retval);
        echo("</p>\n");

        $OUTPUT->footer();
        return '';
    }

    public function keyUsing(Request $request)
    {
        global $CFG, $OUTPUT;

        $bounce = $this->requireSiteRoute('key/using');
        if ( $bounce ) {
            return $bounce;
        }

        $keys = $this->requireKeysEnabled(true);
        if ( $keys ) {
            return $keys;
        }

        require_once self::tsugiRoot() . '/admin/admin_util.php';

        header('Content-Type: text/html; charset=utf-8');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        include __DIR__ . '/templates/Settings/using.inc.php';
        $OUTPUT->footer();
        return '';
    }

    public function keyAuto(Request $request)
    {
        global $CFG, $OUTPUT;

        $openid_configuration = U::get($_REQUEST, 'openid_configuration');
        $registration_token = U::get($_REQUEST, 'registration_token');
        $tsugi_key = U::get($_REQUEST, 'tsugi_key');
        $unlock_code = U::get($_REQUEST, 'unlock_code');

        $LTI = U::get($_SESSION, TSUGI_SESSION_LTI);
        $user_id = U::get($LTI, 'user_id');

        $OUTPUT->header();
        $OUTPUT->bodyStart();

        if ( ! $user_id ) {
            ?>
<p>You are not logged in.
</p>
<p>
<a href="<?= htmlspecialchars($CFG->apphome, ENT_QUOTES, 'UTF-8') ?>" target="_blank"><?= htmlspecialchars($CFG->apphome ?? '', ENT_QUOTES, 'UTF-8') ?></a>
</p>
<p>
Open this in a new tab, login, and come back to this tab and
re-check your login status.
</p>
<p>
<form>
<input type="hidden" name="openid_configuration" value="<?= htmlentities($openid_configuration ?? '') ?>">
<input type="hidden" name="registration_token" value="<?= htmlentities($registration_token ?? '') ?>">
<input type="hidden" name="tsugi_key" value="<?= htmlentities($tsugi_key ?? '') ?>">
<?php if ( is_string($unlock_code) && $unlock_code !== '' ) { ?>
<input type="hidden" name="unlock_code" value="<?= htmlentities($unlock_code) ?>">
<?php } ?>
<input type="submit" name="Re-Check Login Status" value="Re-Check Login Status">
</form>
<?php
            $OUTPUT->footer();
            return '';
        }

        return DynamicRegistration::run($user_id, $tsugi_key, $unlock_code, $openid_configuration, $registration_token);
    }

    private function keyNav($active) {
        $keys_active = $active === 'keys' ? ' active' : '';
        $using_active = $active === 'using' ? ' active' : '';
        $requests_active = $active === 'requests' ? ' active' : '';
        ?>
<h1><?= $active === 'requests' ? 'LTI Key Requests' : 'LTI Keys' ?></h1>
<p>
  <a href="<?= htmlspecialchars($this->pageUrl('key'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default<?= $keys_active ?>" aria-label="LTI Keys<?= $active === 'keys' ? ' (current page)' : '' ?>">LTI Keys</a>
  <a href="<?= htmlspecialchars($this->pageUrl('key/using'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default<?= $using_active ?>" aria-label="Using Your Key<?= $active === 'using' ? ' (current page)' : '' ?>">Using Your Key</a>
  <a href="<?= htmlspecialchars($this->pageUrl('key/requests'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default<?= $requests_active ?>" aria-label="Key Requests">Key Requests</a>
  <a href="<?= htmlspecialchars($this->pageUrl(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="My Settings">My Settings</a>
</p>
<?php
    }

    /**
     * Course-mounted Settings: theme picker for the current manifest.
     */
    private function courseGet(Request $request)
    {
        global $OUTPUT;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        $theme_current = Manifest::currentThemeKey();
        $theme_palettes = Manifest::palettes();
        $theme_site_primary = Manifest::siteDefaultPrimary();
        $save_url = $setup_url;
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $import_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'import'));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $setup_tab = 'theme';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Settings') ?></h1>
            <?php include __DIR__ . '/templates/Settings/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <p><?= __('Theme is stored with each manifest version. New courses start with the site default until you pick one.') ?></p>
            <form method="post" action="<?= htmlspecialchars($save_url) ?>">
                <?= self::csrfField() ?>
                <?php include __DIR__ . '/templates/Settings/theme_picker.inc.php'; ?>
                <p>
                    <button type="submit" class="btn btn-primary"><?= __('Save theme') ?></button>
                    <a href="<?= htmlspecialchars($save_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
                </p>
            </form>
            </div>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * Course-mounted Settings: teacher-edited top navigation.
     */
    public function navigation(Request $request)
    {
        if ( ! self::isCourseRoute() ) {
            return new RedirectResponse($this->pageUrl());
        }
        if ( $request->isMethod('POST') ) {
            return $this->navigationPost($request);
        }

        global $OUTPUT;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $import_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'import'));
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        $doc = Manifest::navigationDocumentForContext(U::currentContextId());
        $nav_rows = CourseNav::editorRows($doc);
        $save_url = $navigation_url;
        $setup_tab = 'navigation';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Settings') ?></h1>
            <?php include __DIR__ . '/templates/Settings/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <?php include __DIR__ . '/templates/Settings/navigation.inc.php'; ?>
            </div>
        </main>
        <?php
        $OUTPUT->footer();
        return '';
    }

    /**
     * Save course navigation onto a new manifest version.
     */
    private function navigationPost(Request $request)
    {
        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf($navigation_url);
        if ( $csrf ) {
            return $csrf;
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            U::flashError(__('Cannot load course manifest.'));
            return new RedirectResponse($navigation_url);
        }
        $decoded = json_decode($doc['json'], true);
        if ( ! is_array($decoded) ) {
            U::flashError(__('Invalid manifest JSON.'));
            return new RedirectResponse($navigation_url);
        }

        $nav = CourseNav::fromPost($_POST);
        $context_id = U::currentContextId();
        try {
            Manifest::saveNewVersion(
                $context_id,
                $decoded,
                U::loggedInUserId(),
                'Set navigation',
                null,
                $nav
            );
        } catch ( \InvalidArgumentException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($navigation_url);
        } catch ( \Exception $e ) {
            U::flashError(__('Failed to save navigation.'));
            return new RedirectResponse($navigation_url);
        }

        U::flashSuccess(__('Navigation saved.'));
        return new RedirectResponse($navigation_url);
    }

    /**
     * Course 16×9 hero and square icon (stored on context_images).
     */
    public function images(Request $request)
    {
        if ( ! self::isCourseRoute() ) {
            return new RedirectResponse($this->pageUrl());
        }
        if ( $request->isMethod('POST') ) {
            return $this->imagesPost($request);
        }

        global $OUTPUT, $PDOX;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $import_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'import'));
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }

        $context_id = U::currentContextId();
        $meta = ContextImages::metadata($context_id);
        $hero_spec = ContextImages::spec(ContextImages::KIND_HERO);
        $icon_spec = ContextImages::spec(ContextImages::KIND_ICON);
        $hero_url = '';
        $icon_url = '';
        if ( ! empty($meta['has_hero']) ) {
            $hero_url = U::addSession(ContextImages::url($context_id, ContextImages::KIND_HERO, $meta['hero_updated_at']));
        }
        if ( ! empty($meta['has_icon']) ) {
            $icon_url = U::addSession(ContextImages::url($context_id, ContextImages::KIND_ICON, $meta['icon_updated_at']));
        }
        $save_url = $images_url;
        $setup_tab = 'images';
        $js_url = $this->staticUrl('tsugi-image-upload.js');

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Settings') ?></h1>
            <?php include __DIR__ . '/templates/Settings/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <?php include __DIR__ . '/templates/Settings/images.inc.php'; ?>
            </div>
        </main>
        <?php
        $OUTPUT->footerStart();
        echo '<script src="'.htmlspecialchars($js_url).'"></script>'."\n";
        $OUTPUT->footerEnd();
        return '';
    }

    /**
     * Save or clear one course image after server-side JPEG reconstruction.
     */
    private function imagesPost(Request $request)
    {
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf($images_url);
        if ( $csrf ) {
            return $csrf;
        }

        global $PDOX;
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }

        $action = U::get($_POST, 'image_action', '');
        $kind = null;
        $clear = false;
        if ( $action === 'save_hero' ) {
            $kind = ContextImages::KIND_HERO;
        } elseif ( $action === 'save_icon' ) {
            $kind = ContextImages::KIND_ICON;
        } elseif ( $action === 'clear_hero' ) {
            $kind = ContextImages::KIND_HERO;
            $clear = true;
        } elseif ( $action === 'clear_icon' ) {
            $kind = ContextImages::KIND_ICON;
            $clear = true;
        } else {
            U::flashError(__('Unknown image action.'));
            return new RedirectResponse($images_url);
        }

        $context_id = U::currentContextId();
        if ( $clear ) {
            $err = ContextImages::clear($context_id, $kind);
            if ( $err ) {
                U::flashError($err);
            } else {
                U::flashSuccess($kind === ContextImages::KIND_HERO
                    ? __('16×9 image removed.')
                    : __('Course icon removed.'));
            }
            return new RedirectResponse($images_url);
        }

        $fdes = isset($_FILES['uploaded_file']) && is_array($_FILES['uploaded_file'])
            ? $_FILES['uploaded_file'] : null;
        if ( $fdes === null || ! isset($fdes['tmp_name']) || ! is_uploaded_file($fdes['tmp_name']) ) {
            U::flashError(__('Please choose an image to upload.'));
            return new RedirectResponse($images_url);
        }
        if ( isset($fdes['error']) && (int) $fdes['error'] !== UPLOAD_ERR_OK ) {
            U::flashError(__('Upload failed.'));
            return new RedirectResponse($images_url);
        }

        $constructed = ContextImages::constructJpeg($fdes['tmp_name'], $kind);
        if ( is_string($constructed) ) {
            U::flashError($constructed);
            return new RedirectResponse($images_url);
        }

        $err = ContextImages::save($context_id, $kind, $constructed['bytes']);
        if ( $err ) {
            U::flashError($err);
            return new RedirectResponse($images_url);
        }

        U::flashSuccess($kind === ContextImages::KIND_HERO
            ? __('16×9 image saved.')
            : __('Course icon saved.'));
        return new RedirectResponse($images_url);
    }

    /**
     * Common Cartridge export form for the current manifest course.
     */
    public function export(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! self::isCourseRoute() ) {
            return new RedirectResponse($this->pageUrl());
        }

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $import_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'import'));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $download_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export/download'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            U::flashError(__('Cannot load course lessons.'));
            return new RedirectResponse($setup_url);
        }

        $counts = LessonsCartridge::summarize($l);
        $youtube_enabled = isset($CFG->youtube_url);
        $localhost_warning = strpos($CFG->wwwroot, '//localhost') !== false;
        $canvas_return_url = U::get($_POST, 'ext_content_return_url', false);
        if ( ! is_string($canvas_return_url) || $canvas_return_url === '' ) {
            $canvas_return_url = false;
        }
        $setup_tab = 'export';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Settings') ?></h1>
            <?php include __DIR__ . '/templates/Settings/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <?php include __DIR__ . '/templates/Settings/export.inc.php'; ?>
            </div>
        </main>
        <?php
        $OUTPUT->footerStart();
        ?>
<script>
function collectExportAnchors() {
    var anchors = [];
    $('#void input.export-module-anchor[type="checkbox"]').each(function(){
         if ( ! $(this).is(':checked') ) return;
         var v = $(this).val();
         if ( v ) {
            anchors.push(v);
         }
    });
    return anchors.join(',');
}
function myfunc(){
    var stuff = collectExportAnchors();
    $("#res").val(stuff);
    $("#tsugi_lms_real").val($("#tsugi_lms_select_partial").val() || 'generic');
    $("#youtube_real").val($("#youtube_select_partial").val() || '');
    $("#topic_real").val($("#topic_select_partial").val() || 'lms');

    if ( stuff.length < 1 ) {
        alert(<?= json_encode(__('Please select at least one module')) ?>);
    } else {
        $("#real").submit();
    }
}
function sendToCanvas() {
    goToCanvas('', $("#youtube_select_full").val() || 'no', $("#topic_select_full").val() || 'lms');
}
function sendToCanvasSelected() {
    var stuff = collectExportAnchors();
    if ( stuff.length < 1 ) {
        alert(<?= json_encode(__('Please select at least one module')) ?>);
        return;
    }
    goToCanvas(stuff, $("#youtube_select_partial").val() || 'no', $("#topic_select_partial").val() || 'lms');
}
function goToCanvas(anchors, youtube, topic) {
    var return_url = <?= json_encode($canvas_return_url ? $canvas_return_url : '') ?>;
    var export_url = <?= json_encode($download_url) ?>;
    export_url = export_url + (export_url.indexOf('?') >= 0 ? '&' : '?') + 'tsugi_lms=canvas';
    export_url = export_url + '&youtube=' + encodeURIComponent(youtube);
    export_url = export_url + '&topic=' + encodeURIComponent(topic);
    if ( anchors ) {
        export_url = export_url + '&anchors=' + encodeURIComponent(anchors);
    }
    return_url = return_url + (return_url.indexOf('?') >= 0 ? '&' : '?');
    return_url = return_url + 'return_type=file&text=' + encodeURIComponent(<?= json_encode(isset($CFG->servicename) ? $CFG->servicename : 'Tsugi') ?>);
    return_url = return_url + '&url=' + encodeURIComponent(export_url);
    window.location.href = return_url;
}
</script>
        <?php
        $OUTPUT->footerEnd();
        return '';
    }

    /**
     * Common Cartridge import form and upload for the current manifest course.
     */
    public function import(Request $request)
    {
        if ( ! self::isCourseRoute() ) {
            return new RedirectResponse($this->pageUrl());
        }
        if ( $request->isMethod('POST') ) {
            return $this->importPost($request);
        }

        global $OUTPUT;

        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $import_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'import'));
        $navigation_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'navigation'));
        $images_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'images'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        $setup_tab = 'import';
        $upload_url = U::addSession(self::cartridgeUploadUrl());
        $upload_limit_label = self::CARTRIDGE_UPLOAD_MAX;
        $pending_token = '';
        $pending_name = '';
        $pending_title = '';
        $pending_modules = array();
        $pending = Pending::load(U::currentContextId(), U::loggedInUserId());
        if ( $pending ) {
            try {
                $pkg = Package::open($pending['path']);
                try {
                    $pending_modules = $pkg->describeModules();
                    $pending_title = $pkg->title;
                } finally {
                    $pkg->close();
                }
                $pending_token = $pending['token'];
                $pending_name = $pending['name'];
            } catch ( ImportException $e ) {
                Pending::clear();
                U::flashError($e->getMessage());
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <main class="container" id="main-content">
            <h1><?= __('Settings') ?></h1>
            <?php include __DIR__ . '/templates/Settings/tabs.inc.php'; ?>
            <div style="margin-top:10px;">
            <?php include __DIR__ . '/templates/Settings/import.inc.php'; ?>
            </div>
        </main>
        <?php
        if ( $pending_token !== '' ) {
            $OUTPUT->footerStart();
            ?>
<script>
function importSelectedModules(){
    var keys = [];
    $('#void input.import-module-key[type="checkbox"]').each(function(){
         if ( ! $(this).is(':checked') ) return;
         var v = $(this).val();
         if ( v ) {
            keys.push(v);
         }
    });
    $("#import_modules_real").val(keys.join(','));
    if ( keys.length < 1 ) {
        alert(<?= json_encode(__('Please select at least one module')) ?>);
        return false;
    }
    $("#import-selected-real").submit();
    return false;
}
</script>
            <?php
            $OUTPUT->footerEnd();
        } else {
            $OUTPUT->footer();
        }
        return '';
    }

    /**
     * True when this request is the dedicated Settings cartridge uploader.
     *
     * That script is a site URL, so restoreSiteLoginContext() would otherwise
     * drop the sandbox course/manifest before import runs.
     */
    public static function isCartridgeUploadRequest()
    {
        $path = self::requestPath();
        return (bool) preg_match('#/Controllers/util/upload(?:/index\.php)?/?$#', $path);
    }

    /**
     * URL of the dedicated cartridge uploader (own PHP upload limits).
     *
     * @param int $contextId Course to import into (query string survives a discarded POST)
     */
    public static function cartridgeUploadUrl($contextId = 0)
    {
        global $CFG;
        $url = rtrim((string) $CFG->wwwroot, '/').self::CARTRIDGE_UPLOAD_PATH;
        $cid = (int) $contextId;
        if ( $cid < 1 ) {
            $cid = U::currentContextId();
        }
        if ( $cid > 0 ) {
            $url .= '?context='.$cid;
        }
        return $url;
    }

    /**
     * Course-mounted Settings import page (not derived from the uploader REQUEST_URI).
     *
     * @param int $contextId
     * @return string
     */
    public static function cartridgeImportPageUrl($contextId)
    {
        global $CFG;
        $cid = (int) $contextId;
        return rtrim((string) $CFG->wwwroot, '/').'/courses/'.$cid.self::ROUTE.'/import';
    }

    /**
     * Target course id from the uploader query, POST, or return URL.
     *
     * @return int
     */
    public static function cartridgeUploadContextIdFromRequest()
    {
        $fromGet = (int) U::get($_GET, 'context', 0);
        if ( $fromGet > 0 ) {
            return $fromGet;
        }
        $fromPost = (int) U::get($_POST, 'context', 0);
        if ( $fromPost > 0 ) {
            return $fromPost;
        }
        $return = U::get($_POST, 'return', '');
        if ( is_string($return) && preg_match('#/courses/(\d+)/settings/import#', $return, $m) ) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Settings import page to return to after upload (session course if possible).
     */
    public function cartridgeImportReturnUrl()
    {
        $posted = U::get($_POST, 'return', '');
        if ( is_string($posted) && self::isSafeImportReturn($posted) ) {
            return $posted;
        }
        $cid = self::cartridgeUploadContextIdFromRequest();
        if ( $cid < 1 ) {
            $cid = U::currentContextId();
        }
        if ( $cid > 0 ) {
            return U::addSession(self::cartridgeImportPageUrl($cid));
        }
        return U::addSession(self::settingsUrl('import'));
    }

    /**
     * Same-site Settings import URL only (hidden form return field).
     *
     * @param mixed $url
     * @return bool
     */
    public static function isSafeImportReturn($url)
    {
        global $CFG;
        if ( ! is_string($url) || $url === '' || strpbrk($url, "\r\n") !== false ) {
            return false;
        }
        if ( ! preg_match('#/settings/import(?:/?$|\?)#', $url) ) {
            return false;
        }
        if ( str_starts_with($url, '/') && ! str_starts_with($url, '//') ) {
            $path = parse_url($url, PHP_URL_PATH);
            return is_string($path) && (bool) preg_match('#/settings/import/?$#', $path);
        }
        $parts = parse_url($url);
        if ( ! is_array($parts) || empty($parts['host']) ) {
            return false;
        }
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        if ( ! preg_match('#/settings/import/?$#', $path) ) {
            return false;
        }
        foreach ( array($CFG->wwwroot ?? '', $CFG->apphome ?? '') as $home ) {
            if ( ! is_string($home) || $home === '' ) {
                continue;
            }
            $hp = parse_url($home);
            if ( ! is_array($hp) || empty($hp['host']) ) {
                continue;
            }
            if ( self::sameOriginParts($parts, $hp) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Scheme, host, and effective port (not host alone).
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     * @return bool
     */
    private static function sameOriginParts(array $a, array $b)
    {
        $hostA = strtolower((string) ($a['host'] ?? ''));
        $hostB = strtolower((string) ($b['host'] ?? ''));
        if ( $hostA === '' || $hostA !== $hostB ) {
            return false;
        }
        $schemeA = strtolower((string) ($a['scheme'] ?? ''));
        $schemeB = strtolower((string) ($b['scheme'] ?? ''));
        if ( $schemeA === '' || $schemeA !== $schemeB ) {
            return false;
        }
        $portA = isset($a['port']) ? (int) $a['port'] : ($schemeA === 'https' ? 443 : 80);
        $portB = isset($b['port']) ? (int) $b['port'] : ($schemeB === 'https' ? 443 : 80);
        return $portA === $portB;
    }

    /**
     * POST handler for Controllers/util/upload/index.php (large cartridge body).
     *
     * Re-binds the sandbox course because this URL is not /courses/{id}/...
     *
     * @return RedirectResponse
     */
    public function handleCartridgeUploadPost()
    {
        $to = $this->cartridgeImportReturnUrl();
        if ( BlobUtil::requestLargerThanPhpPostLimit() ) {
            U::flashError(BlobUtil::phpUploadTooLargeMessage());
            return new RedirectResponse($to);
        }
        $csrf = self::requireCsrf($to);
        if ( $csrf ) {
            return $csrf;
        }
        $cid = self::cartridgeUploadContextIdFromRequest();
        if ( $cid < 1 ) {
            $cid = U::currentContextId();
        }
        if ( $cid > 0 ) {
            $result = Courses::ensureActiveContext($cid);
            if ( $result !== true ) {
                U::flashError(is_string($result) ? $result : __('Could not open that course.'));
                return new RedirectResponse($to);
            }
        }
        return $this->importPost(\Symfony\Component\HttpFoundation\Request::createFromGlobals());
    }

    /**
     * Persist an uploaded Common Cartridge into this course.
     */
    private function importPost(Request $request)
    {
        $import_url = $this->cartridgeImportReturnUrl();
        if ( ! self::isSafeImportReturn($import_url) ) {
            $cid = self::cartridgeUploadContextIdFromRequest();
            if ( $cid < 1 ) {
                $cid = U::currentContextId();
            }
            $import_url = $cid > 0
                ? U::addSession(self::cartridgeImportPageUrl($cid))
                : U::addSession(self::settingsUrl('import'));
        }
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }
        if ( BlobUtil::requestLargerThanPhpPostLimit() ) {
            U::flashError(BlobUtil::phpUploadTooLargeMessage());
            return new RedirectResponse($import_url);
        }
        $csrf = self::requireCsrf($import_url);
        if ( $csrf ) {
            return $csrf;
        }

        $action = U::get($_POST, 'cc_import_action', '');
        if ( is_string($action) && $action !== '' ) {
            return $this->importPendingPost($import_url, $action);
        }

        $fdes = isset($_FILES['cartridge']) && is_array($_FILES['cartridge'])
            ? $_FILES['cartridge'] : null;
        if ( $fdes === null || ! isset($fdes['tmp_name']) || ! is_uploaded_file($fdes['tmp_name']) ) {
            $err = isset($fdes['error']) ? (int) $fdes['error'] : UPLOAD_ERR_NO_FILE;
            if ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE
                || BlobUtil::uploadTooLarge('cartridge') ) {
                $sent = isset($fdes['size']) ? (int) $fdes['size'] : null;
                U::flashError(BlobUtil::phpUploadTooLargeMessage($sent > 0 ? $sent : null));
            } else {
                U::flashError(__('Please choose an .imscc or .zip cartridge to import.'));
            }
            return new RedirectResponse($import_url);
        }
        if ( isset($fdes['error']) && (int) $fdes['error'] !== UPLOAD_ERR_OK ) {
            U::flashError(__('Upload failed.'));
            return new RedirectResponse($import_url);
        }

        $name = isset($fdes['name']) && is_string($fdes['name']) ? $fdes['name'] : '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ( $ext !== 'imscc' && $ext !== 'zip' ) {
            U::flashError(__('Please upload an .imscc or .zip file.'));
            return new RedirectResponse($import_url);
        }

        $dest = tempnam(sys_get_temp_dir(), Pending::FILE_PREFIX);
        if ( $dest === false ) {
            U::flashError(__('Could not create a temporary file for the cartridge.'));
            return new RedirectResponse($import_url);
        }
        if ( ! move_uploaded_file($fdes['tmp_name'], $dest) ) {
            @unlink($dest);
            U::flashError(__('Could not store the uploaded cartridge.'));
            return new RedirectResponse($import_url);
        }

        try {
            $pkg = Package::open($dest);
            $pkg->close();
            Pending::stash($dest, $name, U::currentContextId(), U::loggedInUserId());
        } catch ( ImportException $e ) {
            @unlink($dest);
            U::flashError($e->getMessage());
            return new RedirectResponse($import_url);
        } catch ( \Throwable $e ) {
            @unlink($dest);
            U::flashError(__('Import failed: ').$e->getMessage());
            return new RedirectResponse($import_url);
        }

        return new RedirectResponse($import_url);
    }

    /**
     * Confirm, subset, or cancel a stashed cartridge (second request after upload).
     *
     * @return RedirectResponse
     */
    private function importPendingPost($import_url, $action)
    {
        $token = U::get($_POST, 'cc_pending', '');
        $cid = U::currentContextId();
        $uid = U::loggedInUserId();
        if ( ! Pending::matches($cid, $uid, $token) ) {
            U::flashError(__('The uploaded cartridge expired. Please upload it again.'));
            return new RedirectResponse($import_url);
        }
        $pending = Pending::load($cid, $uid);
        if ( $pending === null ) {
            U::flashError(__('The uploaded cartridge expired. Please upload it again.'));
            return new RedirectResponse($import_url);
        }

        if ( $action === 'cancel' ) {
            Pending::clear();
            U::flashSuccess(__('Upload cancelled.'));
            return new RedirectResponse($import_url);
        }

        $options = array();
        if ( $action === 'selected' ) {
            try {
                $pkg = Package::open($pending['path']);
                try {
                    $described = $pkg->describeModules();
                } finally {
                    $pkg->close();
                }
            } catch ( ImportException $e ) {
                Pending::clear();
                U::flashError($e->getMessage());
                return new RedirectResponse($import_url);
            }
            $keys = self::selectedImportModules($described, U::get($_POST, 'modules', ''));
            if ( $keys === false ) {
                U::flashError(__('Please select at least one module'));
                return new RedirectResponse($import_url);
            }
            $options['modules'] = $keys;
        } else if ( $action !== 'all' ) {
            U::flashError(__('Unknown import action.'));
            return new RedirectResponse($import_url);
        }

        @set_time_limit(120);
        try {
            $row = Importer::run($pending['path'], $cid, $uid, $options);
        } catch ( ImportException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($import_url);
        } catch ( \Throwable $e ) {
            U::flashError(__('Import failed: ').$e->getMessage());
            return new RedirectResponse($import_url);
        }
        $name = $pending['name'];
        Pending::clear();

        $created = (int) ($row['created_count'] ?? 0);
        $dup = (int) ($row['duplicate_count'] ?? 0);
        $copy = (int) ($row['copy_count'] ?? 0);
        $errn = (int) ($row['error_count'] ?? 0);
        $iid = (int) ($row['import_id'] ?? 0);
        $msg = sprintf(
            __('Imported %1$s: %2$d created, %3$d duplicates, %4$d copies, %5$d errors (import #%6$d).'),
            $name !== '' ? $name : 'cartridge',
            $created,
            $dup,
            $copy,
            $errn,
            $iid
        );
        if ( $errn > 0 ) {
            U::flashError($msg);
        } else {
            U::flashSuccess($msg);
        }
        return new RedirectResponse($import_url);
    }

    /**
     * Organization keys from the import Select Content form, or false if none match.
     *
     * @param list<array{key:string}> $described
     * @param mixed $module_str
     * @return list<string>|false
     */
    public static function selectedImportModules(array $described, $module_str) {
        if ( ! is_string($module_str) || $module_str === '' ) {
            return false;
        }
        $wanted = array();
        foreach ( explode(',', $module_str) as $a ) {
            $a = trim($a);
            if ( $a !== '' ) {
                $wanted[$a] = true;
            }
        }
        if ( count($wanted) < 1 ) {
            return false;
        }
        $matched = array();
        foreach ( $described as $row ) {
            $key = isset($row['key']) ? (string) $row['key'] : '';
            if ( $key !== '' && isset($wanted[$key]) ) {
                $matched[] = $key;
            }
        }
        return count($matched) > 0 ? $matched : false;
    }

    /**
     * Module anchors selected on the export form, or false to include every module.
     *
     * Same rule as /cc/export: a missing, empty, or unmatched list is a full export.
     *
     * @param object $l Lessons
     * @param mixed $anchor_str Comma-separated anchors from ?anchors=
     * @return list<string>|false
     */
    public static function selectedExportAnchors($l, $anchor_str) {
        if ( ! is_string($anchor_str) || $anchor_str === '' ) {
            return false;
        }
        $wanted = array();
        foreach ( explode(',', $anchor_str) as $a ) {
            $a = trim($a);
            if ( $a !== '' ) {
                $wanted[] = $a;
            }
        }
        if ( count($wanted) < 1 ) {
            return false;
        }
        if ( ! isset($l->lessons->modules) || ! is_array($l->lessons->modules) ) {
            return false;
        }
        $matched = array();
        foreach ( $l->lessons->modules as $module ) {
            $anchor = isset($module->anchor) ? (string) $module->anchor : '';
            if ( $anchor !== '' && in_array($anchor, $wanted, true) ) {
                $matched[] = $anchor;
            }
        }
        return count($matched) > 0 ? $matched : false;
    }

    /**
     * Download a Common Cartridge built from the current course manifest.
     */
    public function exportDownload(Request $request)
    {
        global $CFG;

        if ( ! self::isCourseRoute() ) {
            return new RedirectResponse($this->pageUrl());
        }

        $export_url = U::addSession(self::joinToolHome($this->toolHome(self::ROUTE), 'export'));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            U::flashError(__('Cannot load course lessons.'));
            return new RedirectResponse($export_url);
        }

        $anchors = self::selectedExportAnchors($l, U::get($_GET, 'anchors', false));

        $filename = tempnam(sys_get_temp_dir(), isset($CFG->servicename) ? $CFG->servicename : 'cc');
        if ( $filename === false ) {
            U::flashError(__('Could not create a temporary file for the cartridge.'));
            return new RedirectResponse($export_url);
        }
        unlink($filename);
        $zip = new \ZipArchive();
        if ( $zip->open($filename, \ZipArchive::CREATE) !== true ) {
            U::flashError(__('Cannot open the cartridge zip file.'));
            return new RedirectResponse($export_url);
        }

        $tsugi_lms = LessonsCartridge::exportFlavor(U::get($_GET, 'tsugi_lms', false));
        try {
            LessonsCartridge::writeZip($l, $zip, array(
                'tsugi_lms' => $tsugi_lms,
                'topic' => LessonsCartridge::exportTopicMode(U::get($_GET, 'topic', false)),
                'youtube' => U::get($_GET, 'youtube', false),
                'anchors' => $anchors,
                'context_id' => U::currentContextId(),
            ));
        } catch ( ExportException $e ) {
            $zip->close();
            @unlink($filename);
            U::flashError($e->getMessage());
            return new RedirectResponse($export_url);
        } catch ( \Exception $e ) {
            $zip->close();
            @unlink($filename);
            error_log('LessonsCartridge export failed: '.$e->getMessage());
            U::flashError(__('Could not create the cartridge.'));
            return new RedirectResponse($export_url);
        }
        $zip->close();

        $download = LessonsCartridge::downloadName($l, $tsugi_lms);
        $download = str_replace(array('\\', '"'), '', $download);
        $response = new BinaryFileResponse($filename);
        $response->headers->set('Content-Type', 'application/x-zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $download);
        $response->deleteFileAfterSend(true);
        return $response;
    }

    /**
     * Save the course theme onto a new manifest version.
     */
    private function coursePost(Request $request)
    {
        $setup_url = U::addSession($this->toolHome(self::ROUTE));
        $gate = $this->courseGate();
        if ( $gate ) {
            return $gate;
        }
        $csrf = self::requireCsrf($setup_url);
        if ( $csrf ) {
            return $csrf;
        }

        $posted = U::get($_POST, 'theme', '');
        $norm = Manifest::normalizeThemeKey($posted);
        if ( $norm === false ) {
            U::flashError(__('Unknown theme.'));
            return new RedirectResponse($setup_url);
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            U::flashError(__('Cannot load course manifest.'));
            return new RedirectResponse($setup_url);
        }
        $decoded = json_decode($doc['json'], true);
        if ( ! is_array($decoded) ) {
            U::flashError(__('Invalid manifest JSON.'));
            return new RedirectResponse($setup_url);
        }

        $context_id = U::currentContextId();
        $store = $norm === null ? '' : $norm;
        try {
            Manifest::saveNewVersion(
                $context_id,
                $decoded,
                U::loggedInUserId(),
                'Set theme',
                $store
            );
        } catch ( \InvalidArgumentException $e ) {
            U::flashError($e->getMessage());
            return new RedirectResponse($setup_url);
        } catch ( \Exception $e ) {
            U::flashError(__('Failed to save theme.'));
            return new RedirectResponse($setup_url);
        }

        U::flashSuccess(__('Theme saved.'));
        return new RedirectResponse($setup_url);
    }

    /**
     * @return RedirectResponse|null
     */
    private function courseGate() {
        $home = U::addSession(self::configuredHomeUrl());
        $this->requireInstructor($home);
        if ( Manifest::activeId() < 1 ) {
            U::flashError(__('Course settings are only available for courses with a manifest.'));
            return new RedirectResponse($home);
        }
        return null;
    }
}
