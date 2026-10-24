<?php

namespace Tsugi\Services\Plus;

use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Services\CourseNav\CourseNav;
use Tsugi\Util\LTI13;
use Tsugi\Util\U;

/**
 * Course-navigation launch for a Plus course.
 *
 * The Plus course is the context Canvas already created. This attaches an empty
 * Lessons v2 manifest to that row. It does not insert another context.
 *
 * The launch uses a cookie session. If the POST is inside an iframe, the
 * session waits until a button re-posts the launch into a new window.
 */
class PlusShell {

    const TARGET_LINK_CLAIM = 'https://purl.imsglobal.org/spec/lti/claim/target_link_uri';
    const REPOST_FIELD = 'plus_repost';

    /**
     * A Plus course's service URLs stay put unless this request is /plus.
     * Other launches keep today's overwrite. The resource link id is not the check.
     *
     * @param mixed $plusCourse
     */
    public static function mayRefreshCourse($plusCourse) {
        if ( (int) $plusCourse !== 1 ) {
            return true;
        }
        return self::requestIsPlus();
    }

    /**
     * True when this HTTP request is the Plus launch path.
     */
    public static function requestIsPlus() {
        global $CFG;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return self::launchTargetsPlus($uri, ($CFG->wwwroot ?? '') . '/plus');
    }

    /**
     * Canvas title replaces a Plus course title only while the local title is blank.
     *
     * @param mixed $plusCourse
     * @param mixed $currentTitle
     */
    public static function mayReplaceTitle($plusCourse, $currentTitle) {
        if ( (int) $plusCourse !== 1 ) {
            return true;
        }
        return ! is_string($currentTitle) || trim($currentTitle) === '';
    }

    /**
     * True when the launch target path is this site's /plus path.
     *
     * @param mixed $target
     * @param mixed $plusUrl
     */
    public static function launchTargetsPlus($target, $plusUrl) {
        $targetPath = self::requestPath($target);
        $plusPath = self::requestPath($plusUrl);
        if ( $targetPath === null || $plusPath === null ) {
            return false;
        }
        return $targetPath === $plusPath;
    }

    /**
     * IANA zone name, or null when the value is empty or not a known zone.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function ianaZone($value) {
        if ( ! is_string($value) ) {
            return null;
        }
        $value = trim($value);
        if ( $value === '' ) {
            return null;
        }
        static $zones = null;
        if ( $zones === null ) {
            $zones = array_flip(timezone_identifiers_list());
        }
        if ( ! isset($zones[$value]) ) {
            return null;
        }
        return $value;
    }

    /**
     * Canvas course label, trimmed to lti_context.short_title.
     *
     * @param mixed $label
     * @return string|null
     */
    public static function shortTitle($label) {
        $short = Manifest::normalizeShortTitle($label);
        if ( $short === false && is_string($label) ) {
            $cut = function_exists('mb_substr')
                ? mb_substr($label, 0, Manifest::SHORT_TITLE_MAX)
                : substr($label, 0, Manifest::SHORT_TITLE_MAX);
            $short = Manifest::normalizeShortTitle($cut);
        }
        if ( ! is_string($short) || $short === '' ) {
            return null;
        }
        return $short;
    }

    /**
     * True when this POST is a launch that has not yet left its iframe.
     */
    public static function needsBreakout() {
        if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
            return false;
        }
        if ( (string) ($_POST[self::REPOST_FIELD] ?? '') === '1' ) {
            return false;
        }
        $idToken = $_POST['id_token'] ?? null;
        if ( is_string($idToken) && $idToken !== '' ) {
            return true;
        }
        $consumer = $_POST['oauth_consumer_key'] ?? null;
        return is_string($consumer) && $consumer !== '';
    }

    /**
     * True when this POST is the launch that will open the cookie session.
     *
     * The iframe breakout returns before this. The repost in the new window
     * is the login.
     */
    public static function isSessionLaunch() {
        if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
            return false;
        }
        $idToken = $_POST['id_token'] ?? null;
        if ( is_string($idToken) && $idToken !== '' ) {
            return true;
        }
        $consumer = $_POST['oauth_consumer_key'] ?? null;
        return is_string($consumer) && $consumer !== '';
    }

    /**
     * Drop the previous cookie session before this launch is stored.
     *
     * A Plus launch is a new login. Cached membership and the previous
     * user stay in $_SESSION unless this runs. Another tab that shared
     * the cookie loses that session.
     */
    public static function replaceCookieSession() {
        if ( session_status() !== PHP_SESSION_ACTIVE ) {
            session_start();
        }
        $_SESSION = array();
        session_write_close();
    }

    /**
     * Re-post the launch. A top-level window submits immediately. An iframe
     * shows a button that opens a new window, where the cookie can be set.
     */
    public static function renderBreakout() {
        global $CFG;
        $action = $CFG->wwwroot . '/plus';
        $title = trim((string) ($CFG->servicename ?? 'Tsugi')) . ' Plus';
        $skip = array(
            self::REPOST_FIELD => true,
            session_name() => true,
            '_LTI_TSUGI' => true,
        );
        header('Content-Type: text/html; charset=UTF-8');
        echo "<!DOCTYPE html>\n<html>\n<head>\n";
        echo '<meta charset="UTF-8">' . "\n";
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</title>\n";
        echo "<style>\n";
        echo "body { font-family: sans-serif; margin: 3em auto; max-width: 36em; line-height: 1.4; }\n";
        echo "button { font-size: 1em; padding: 0.4em 0.8em; }\n";
        echo "</style>\n</head>\n<body>\n";
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>\n";
        echo "<p>Tsugi Plus keeps you signed in with a cookie, so it opens in a new window.</p>\n";
        echo '<form id="plus_pop" method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . "\">\n";
        foreach ( $_POST as $key => $value ) {
            if ( ! is_string($key) || ! is_string($value) || isset($skip[$key]) ) {
                continue;
            }
            echo '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\">\n";
        }
        echo '<input type="hidden" name="' . self::REPOST_FIELD . "\" value=\"1\">\n";
        echo '<button type="submit" id="plus_open">Open Tsugi Plus</button>' . "\n";
        echo "</form>\n";
        echo '<p id="plus_done" style="display:none">Tsugi Plus is open in a new window.</p>' . "\n";
        echo "<script>\n";
        echo "var plusForm = document.getElementById('plus_pop');\n";
        echo "var plusOpen = document.getElementById('plus_open');\n";
        echo "if (window === window.top) {\n";
        echo "  plusOpen.style.display = 'none';\n";
        echo "  plusForm.submit();\n";
        echo "} else {\n";
        echo "  plusOpen.addEventListener('click', function (event) {\n";
        echo "    event.preventDefault();\n";
        echo "    var name = 'tsugi_plus_' + Date.now();\n";
        echo "    var popup = window.open('about:blank', name);\n";
        echo "    plusForm.target = popup ? name : '_blank';\n";
        echo "    plusForm.submit();\n";
        echo "    plusOpen.style.display = 'none';\n";
        echo "    document.getElementById('plus_done').style.display = 'block';\n";
        echo "  });\n";
        echo "}\n";
        echo "</script>\n</body>\n</html>\n";
    }

    /**
     * Open the course for the current launch, creating it the first time.
     *
     * @return array{redirect?: string, error?: string}
     */
    public static function openFromSession() {
        global $CFG;
        $plusUrl = $CFG->wwwroot . '/plus';
        $target = self::jwtString(self::TARGET_LINK_CLAIM);
        if ( ! self::launchTargetsPlus($target, $plusUrl) ) {
            return array('error' => 'Open Tsugi Plus from your LMS course navigation.');
        }
        if ( self::jwtString(LTI13::MESSAGE_TYPE_CLAIM) !== LTI13::MESSAGE_TYPE_RESOURCE ) {
            return array('error' => 'Open Tsugi Plus from your LMS course navigation.');
        }
        $contextId = (int) LTIX::ltiParameter('context_id', 0);
        $linkId = (int) LTIX::ltiParameter('link_id', 0);
        if ( $contextId < 1 ) {
            return array('error' => 'This launch has no course.');
        }
        $keyId = (int) LTIX::ltiParameter('key_id', 0);
        if ( ! self::tenantAllowsPlus($keyId) ) {
            return array('error' => 'Tsugi Plus is not enabled for this tenant.');
        }
        $userId = (int) LTIX::ltiParameter('user_id', 0);
        $deployment = LTIX::ltiParameter('deployment_id', '');
        if ( ! is_string($deployment) ) {
            $deployment = '';
        }
        return self::provision($contextId, $linkId, $userId, $deployment, self::contextLabel());
    }

    /**
     * @param int $keyId
     */
    private static function tenantAllowsPlus($keyId) {
        global $CFG;
        if ( $keyId < 1 ) {
            return false;
        }
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT plus FROM {$CFG->dbprefix}lti_key WHERE key_id = :KID",
            array(':KID' => $keyId)
        );
        return is_array($row) && (int) ($row['plus'] ?? 0) === 1;
    }

    /**
     * @param string $deployment
     * @param string|null $label
     * @return array{redirect?: string, error?: string}
     */
    private static function provision($contextId, $linkId, $userId, $deployment, $label) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $PDOX->beginTransaction();
        try {
            $row = $PDOX->rowDie(
                "SELECT context_id, title, short_title, manifest_id, plus_course,
                        plus_launch_link_id, timezone, lti13_deployment_id, key_id
                 FROM {$p}lti_context
                 WHERE context_id = :CID
                 FOR UPDATE",
                array(':CID' => $contextId)
            );
            if ( ! is_array($row) || (int) ($row['context_id'] ?? 0) < 1 ) {
                $PDOX->commit();
                return array('error' => 'This launch has no course.');
            }

            $row['key_timezone'] = self::keyTimezone($PDOX, $p, (int) ($row['key_id'] ?? 0));
            self::absorbLaunchLink($PDOX, $p, $contextId, $linkId, $row);
            $short = self::shortTitle($label);
            if ( (int) $row['plus_course'] === 1 ) {
                if ( (int) $row['manifest_id'] < 1 ) {
                    $PDOX->commit();
                    return array('error' => 'This Plus course has no manifest.');
                }
                self::fillShortTitle($PDOX, $p, $contextId, $row, $short);
                $PDOX->commit();
                self::remember($row);
                return array('redirect' => self::courseUrl($contextId));
            }

            if ( (int) $row['manifest_id'] > 0 ) {
                $PDOX->commit();
                $row['plus_course'] = 1;
                self::remember($row);
                return array('redirect' => self::courseUrl($contextId));
            }

            $navigation = self::starterNavigation($short);
            $starterTitle = is_string($row['title']) ? trim($row['title']) : '';
            $manifestId = Manifest::saveNewVersion(
                $contextId,
                Manifest::starter($starterTitle),
                $userId > 0 ? $userId : null,
                'Plus shell',
                null,
                $navigation
            );

            $sets = array('plus_course = 1', 'updated_at = NOW()');
            $params = array(':CID' => $contextId);
            if ( $deployment !== '' ) {
                $sets[] = 'lti13_deployment_id = :DEP';
                $params[':DEP'] = $deployment;
                $row['lti13_deployment_id'] = $deployment;
            }
            $keyZone = self::ianaZone($row['key_timezone'] ?? null);
            if ( self::ianaZone($row['timezone'] ?? null) === null && $keyZone !== null ) {
                $sets[] = 'timezone = :TZ';
                $params[':TZ'] = $keyZone;
                $row['timezone'] = $keyZone;
            }
            if ( is_string($short) && self::blankText($row['short_title'] ?? null) ) {
                $sets[] = 'short_title = :SHORT';
                $params[':SHORT'] = $short;
                $row['short_title'] = $short;
            }
            if ( self::blankText($row['title'] ?? null) ) {
                $row['title'] = 'Untitled Course';
            }
            $PDOX->queryDie(
                "UPDATE {$p}lti_context SET " . implode(', ', $sets) . "
                 WHERE context_id = :CID AND plus_course = 0",
                $params
            );
            $row['manifest_id'] = $manifestId;
            $row['plus_course'] = 1;
            $PDOX->commit();
            self::remember($row);
            return array('redirect' => self::courseUrl($contextId));
        } catch ( \Exception $e ) {
            if ( $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            error_log('Plus shell: ' . $e->getMessage());
            return array('error' => 'Could not create the course.');
        }
    }

    /**
     * Keep the context's resource link current. A missing id leaves the stored link.
     * Nothing reads this for access, grades, or line items.
     *
     * @param mixed $PDOX
     * @param string $p
     * @param int $contextId
     * @param int $linkId
     * @param array<string, mixed> $row
     */
    private static function absorbLaunchLink($PDOX, $p, $contextId, $linkId, array &$row) {
        if ( $linkId < 1 || (int) ($row['plus_launch_link_id'] ?? 0) === $linkId ) {
            return;
        }
        $PDOX->queryDie(
            "UPDATE {$p}lti_context SET plus_launch_link_id = :LINK, updated_at = NOW()
             WHERE context_id = :CID",
            array(':LINK' => $linkId, ':CID' => $contextId)
        );
        $row['plus_launch_link_id'] = $linkId;
    }

    /**
     * Tenant zone from the launch session, or a read that does not lock the key.
     *
     * @param mixed $PDOX
     * @param string $p
     * @param int $keyId
     * @return mixed
     */
    private static function keyTimezone($PDOX, $p, $keyId) {
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey]) && array_key_exists('key_timezone', $_SESSION[$ltiKey]) ) {
            return $_SESSION[$ltiKey]['key_timezone'];
        }
        if ( $keyId < 1 ) {
            return null;
        }
        $key = $PDOX->rowDie(
            "SELECT timezone FROM {$p}lti_key WHERE key_id = :KID",
            array(':KID' => $keyId)
        );
        return is_array($key) ? ($key['timezone'] ?? null) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @param string|null $short
     */
    private static function fillShortTitle($PDOX, $p, $contextId, array &$row, $short) {
        if ( $short === null || ! self::blankText($row['short_title'] ?? null) ) {
            return;
        }
        $PDOX->queryDie(
            "UPDATE {$p}lti_context SET short_title = :SHORT, updated_at = NOW()
             WHERE context_id = :CID AND (short_title IS NULL OR short_title = '')",
            array(':SHORT' => $short, ':CID' => $contextId)
        );
        $row['short_title'] = $short;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function remember(array $row) {
        Manifest::rememberInSession((int) ($row['manifest_id'] ?? 0));
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        if ( ! isset($_SESSION[$ltiKey]) || ! is_array($_SESSION[$ltiKey]) ) {
            return;
        }
        $_SESSION[$ltiKey]['plus_course'] = (int) ($row['plus_course'] ?? 0);
        $_SESSION[$ltiKey]['plus_launch_link_id'] = $row['plus_launch_link_id'] ?? null;
        $_SESSION[$ltiKey]['plus_context_launch'] = 1;
        if ( is_string($row['title'] ?? null) && trim($row['title']) !== '' ) {
            $_SESSION[$ltiKey]['context_title'] = trim($row['title']);
        }
        $_SESSION[$ltiKey]['lti13_deployment_id'] = $row['lti13_deployment_id'] ?? null;
        $_SESSION[$ltiKey]['context_timezone'] = $row['timezone'] ?? null;
        $_SESSION[$ltiKey]['context_short_title'] = $row['short_title'] ?? null;
        if ( array_key_exists('key_timezone', $row) ) {
            $_SESSION[$ltiKey]['key_timezone'] = $row['key_timezone'];
        }
    }

    /**
     * Default course nav for a new Plus shell, without the courses widget.
     *
     * @param string|null $short
     * @return array<string, mixed>
     */
    private static function starterNavigation($short) {
        $navigation = CourseNav::defaultDocument();
        $items = array();
        foreach ( $navigation['items'] as $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $id = $item['id'] ?? '';
            if ( $id === 'courses_widget' ) {
                continue;
            }
            if ( $id === 'logout' ) {
                $items[] = array('id' => 'leave_course', 'dropdown' => true);
                continue;
            }
            $items[] = $item;
        }
        $navigation['items'] = $items;
        if ( is_string($short) ) {
            $home = CourseNav::homeLabelFromShortTitle($short);
            if ( is_string($home) && $home !== '' ) {
                $navigation['home'] = $home;
            }
        }
        return $navigation;
    }

    private static function courseUrl($contextId) {
        global $CFG;
        $id = (int) $contextId;
        return U::addSession($CFG->wwwroot . '/courses/' . $id);
    }

    /**
     * @param mixed $value
     */
    private static function blankText($value) {
        return ! is_string($value) || trim($value) === '';
    }

    /**
     * @param mixed $url
     * @return string|null
     */
    private static function requestPath($url) {
        if ( ! is_string($url) || trim($url) === '' ) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ( ! is_string($path) || $path === '' ) {
            return null;
        }
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * @return string|null
     */
    private static function jwtString($claim) {
        $body = self::jwtBody();
        if ( $body === null || ! isset($body->{$claim}) || ! is_string($body->{$claim}) ) {
            return null;
        }
        return $body->{$claim};
    }

    /**
     * @return string|null
     */
    private static function contextLabel() {
        $body = self::jwtBody();
        $claim = LTI13::CONTEXT_ID_CLAIM;
        if ( $body === null || ! isset($body->{$claim}) || ! is_object($body->{$claim}) ) {
            return null;
        }
        $label = $body->{$claim}->label ?? null;
        return is_string($label) ? $label : null;
    }

    /**
     * @return object|null
     */
    private static function jwtBody() {
        if ( ! isset($_SESSION['tsugi_jwt']) || ! is_object($_SESSION['tsugi_jwt']) ) {
            return null;
        }
        $body = $_SESSION['tsugi_jwt']->body ?? null;
        return is_object($body) ? $body : null;
    }

}
