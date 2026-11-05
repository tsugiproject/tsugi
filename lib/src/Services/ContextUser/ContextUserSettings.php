<?php

namespace Tsugi\Services\ContextUser;

use Tsugi\Core\Cache;
use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;

/**
 * One row per person per course in lti_context_user_settings.
 *
 * note is plain text, at most 500 characters. settings is a JSON object.
 * home_off lists HomeLayout::blockKey values this person hid on the home page.
 * view is lessons, content, or outline when they have picked one way to read
 * the course. steps false hides the progress checkboxes the course turned on.
 *
 * A row exists only after a save. An empty note and empty settings remove it.
 */
class ContextUserSettings {

    const MAX_NOTE = 500;

    const VIEWS = array('lessons', 'content', 'outline');

    /**
     * One session slot for this person's row in the current course.
     * A different course replaces it. A gear save drops it.
     */
    const CACHE_LOCATION = 'context_user_settings';

    /** @var array<string, array{note:string,settings:array<string,mixed>}> */
    private static $cache = array();

    /**
     * Plain text, trimmed, at most 500 characters. Null when the text is longer.
     *
     * @param mixed $raw
     * @return string|null
     */
    public static function plain($raw) {
        $text = is_string($raw) ? $raw : '';
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = trim(strip_tags($text));
        if ( mb_strlen($text, 'UTF-8') > self::MAX_NOTE ) {
            return null;
        }
        return $text;
    }

    /**
     * @param int $context_id
     * @param int $user_id
     * @param bool $fresh True skips the session copy. Saves use this so a write cannot clobber another device.
     * @return array{note:string,settings:array<string,mixed>}
     */
    public static function load($context_id, $user_id, $fresh = false) {
        global $CFG, $PDOX;

        $empty = array('note' => '', 'settings' => array());
        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 || $user_id < 1 ) {
            return $empty;
        }
        $key = $context_id.':'.$user_id;
        if ( ! $fresh && isset(self::$cache[$key]) ) {
            return self::$cache[$key];
        }
        $session = $fresh ? null : self::sessionRow($context_id, $user_id);
        if ( $session !== null ) {
            self::$cache[$key] = $session;
            return $session;
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            LTIX::getConnection();
        }
        $row = $PDOX->rowDie(
            "SELECT note, settings FROM {$CFG->dbprefix}lti_context_user_settings
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( ! is_array($row) ) {
            self::remember($context_id, $user_id, $empty);
            return $empty;
        }
        $loaded = array(
            'note' => isset($row['note']) ? (string) $row['note'] : '',
            'settings' => self::decode(isset($row['settings']) ? $row['settings'] : null),
        );
        self::remember($context_id, $user_id, $loaded);
        return $loaded;
    }

    /**
     * Store the note. An empty note removes the row only when settings are empty too.
     *
     * @param int $context_id
     * @param int $user_id
     * @param string $note Already passed through plain()
     */
    public static function saveNote($context_id, $user_id, $note) {
        $row = self::load($context_id, $user_id, true);
        $row['note'] = (string) $note;
        self::store($context_id, $user_id, $row, 'note');
    }

    /**
     * Replace the home_off list. Other settings keys stay put.
     *
     * @param int $context_id
     * @param int $user_id
     * @param list<string> $off
     */
    public static function saveHomeOff($context_id, $user_id, array $off) {
        $row = self::load($context_id, $user_id, true);
        $clean = array();
        foreach ( $off as $key ) {
            if ( ! is_string($key) || $key === '' || strlen($key) > 512 ) {
                continue;
            }
            $clean[$key] = true;
            if ( count($clean) >= 200 ) {
                break;
            }
        }
        if ( count($clean) === 0 ) {
            unset($row['settings']['home_off']);
        } else {
            $row['settings']['home_off'] = array_keys($clean);
        }
        self::store($context_id, $user_id, $row, 'settings');
    }

    /**
     * Save the home gear in one write: hidden blocks, reading view, and progress boxes.
     *
     * home_off is always replaced. view and hide_steps are applied only when present.
     *
     * @param int $context_id
     * @param int $user_id
     * @param array{home_off:list<string>,view?:string,hide_steps?:bool} $changes
     */
    public static function saveGear($context_id, $user_id, array $changes) {
        $row = self::load($context_id, $user_id, true);
        $off = isset($changes['home_off']) && is_array($changes['home_off']) ? $changes['home_off'] : array();
        $clean = array();
        foreach ( $off as $key ) {
            if ( ! is_string($key) || $key === '' || strlen($key) > 512 ) {
                continue;
            }
            $clean[$key] = true;
            if ( count($clean) >= 200 ) {
                break;
            }
        }
        if ( count($clean) === 0 ) {
            unset($row['settings']['home_off']);
        } else {
            $row['settings']['home_off'] = array_keys($clean);
        }
        if ( array_key_exists('view', $changes) ) {
            $view = is_string($changes['view']) ? $changes['view'] : '';
            if ( in_array($view, self::VIEWS, true) ) {
                $row['settings']['view'] = $view;
            } else {
                unset($row['settings']['view']);
            }
        }
        if ( array_key_exists('hide_steps', $changes) ) {
            if ( ! empty($changes['hide_steps']) ) {
                $row['settings']['steps'] = false;
            } else {
                unset($row['settings']['steps']);
            }
        }
        self::store($context_id, $user_id, $row, 'settings');
    }

    /**
     * lessons, content, outline, or empty when this person has not picked.
     *
     * @param array<string,mixed> $settings
     * @return string
     */
    public static function contentView(array $settings) {
        $view = isset($settings['view']) ? (string) $settings['view'] : '';
        return in_array($view, self::VIEWS, true) ? $view : '';
    }

    /**
     * A posted view, kept only when the instructor still offers it.
     *
     * @param mixed $raw
     * @param list<string> $offered
     * @return string
     */
    public static function viewFromPost($raw, array $offered) {
        $view = is_string($raw) ? $raw : '';
        if ( ! in_array($view, self::VIEWS, true) || ! in_array($view, $offered, true) ) {
            return '';
        }
        return $view;
    }

    /**
     * True when this person turned the course progress checkboxes off.
     *
     * @param array<string,mixed> $settings
     */
    public static function stepsHidden(array $settings) {
        return isset($settings['steps']) && $settings['steps'] === false;
    }

    /**
     * The signed-in student hid progress checkboxes. Instructors keep the course setting.
     */
    public static function stepsHiddenForCurrentUser() {
        $context_id = ReqScope::currentContextId();
        $user_id = ReqScope::loggedInUserId();
        if ( $context_id < 1 || $user_id < 1 || self::viewerIsInstructor() ) {
            return false;
        }
        global $PDOX;
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            return false;
        }
        try {
            $row = self::load($context_id, $user_id);
        } catch ( \Throwable $e ) {
            return false;
        }
        return self::stepsHidden($row['settings']);
    }

    /**
     * Keys the person turned off. $offered is the blocks on this course home.
     * $shown is the checkbox values they left checked. Keys in $alreadyOff that
     * are not on this form stay off, so a missing page is still hidden when it returns.
     *
     * @param list<string> $offered
     * @param mixed $shown
     * @param array<string,mixed> $alreadyOff
     * @return list<string>
     */
    public static function homeOffFromPost(array $offered, $shown, array $alreadyOff = array()) {
        $allowed = array();
        foreach ( $offered as $key ) {
            if ( is_string($key) && $key !== '' ) {
                $allowed[$key] = true;
            }
        }
        $kept = array();
        foreach ( $alreadyOff as $key => $flag ) {
            if ( is_int($key) ) {
                $key = $flag;
            }
            if ( ! is_string($key) || $key === '' || isset($allowed[$key]) || strlen($key) > 512 ) {
                continue;
            }
            $kept[$key] = true;
            if ( count($kept) >= 200 ) {
                return array_keys($kept);
            }
        }
        if ( is_string($shown) ) {
            $shown = array($shown);
        }
        if ( ! is_array($shown) ) {
            $shown = array();
        }
        $shownSet = array();
        foreach ( $shown as $key ) {
            if ( is_string($key) && isset($allowed[$key]) ) {
                $shownSet[$key] = true;
            }
        }
        foreach ( array_keys($allowed) as $key ) {
            if ( isset($shownSet[$key]) ) {
                continue;
            }
            $kept[$key] = true;
            if ( count($kept) >= 200 ) {
                break;
            }
        }
        return array_keys($kept);
    }

    /**
     * @param array<string,mixed> $settings
     * @return array<string,bool>
     */
    public static function homeOff(array $settings) {
        $off = isset($settings['home_off']) && is_array($settings['home_off']) ? $settings['home_off'] : array();
        $set = array();
        foreach ( $off as $key ) {
            if ( is_string($key) && $key !== '' ) {
                $set[$key] = true;
            }
        }
        return $set;
    }

    /**
     * @param int $context_id
     * @param int $user_id
     * @param array{note:string,settings:array<string,mixed>} $row
     * @param string $touch note or settings. The other column is left as stored
     *        when the row already exists.
     */
    private static function store($context_id, $user_id, array $row, $touch) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 || $user_id < 1 ) {
            return;
        }
        $note = isset($row['note']) ? (string) $row['note'] : '';
        $settings = self::encode(isset($row['settings']) && is_array($row['settings']) ? $row['settings'] : array());
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            LTIX::getConnection();
        }
        if ( $note === '' && $settings === null ) {
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}lti_context_user_settings
                 WHERE context_id = :CID AND user_id = :UID",
                array(':CID' => $context_id, ':UID' => $user_id)
            );
            self::remember($context_id, $user_id, array('note' => '', 'settings' => array()));
            self::forgetSession($context_id);
            return;
        }
        $assign = $touch === 'settings'
            ? 'settings = VALUES(settings)'
            : 'note = VALUES(note)';
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_context_user_settings
                /*PDOX pk: settings_id lk: context_id,user_id */
                (context_id, user_id, note, settings, updated_at)
             VALUES (:CID, :UID, :NOTE, :SETTINGS, NOW())
             ON DUPLICATE KEY UPDATE {$assign}, updated_at = NOW()",
            array(
                ':CID' => $context_id,
                ':UID' => $user_id,
                ':NOTE' => $note,
                ':SETTINGS' => $settings,
            )
        );
        self::remember($context_id, $user_id, array(
            'note' => $note,
            'settings' => self::decode($settings),
        ));
        self::forgetSession($context_id);
    }

    /**
     * The session copy for this course and this person, or null on a miss.
     * Switching course drops the previous course's slot.
     *
     * @return array{note:string,settings:array<string,mixed>}|null
     */
    private static function sessionRow($context_id, $user_id) {
        if ( ! isset($_SESSION) || ! is_array($_SESSION) ) {
            return null;
        }
        $cached = Cache::getContext(self::CACHE_LOCATION, $context_id);
        if ( ! is_array($cached) || (int) ($cached['user_id'] ?? 0) !== (int) $user_id ) {
            return null;
        }
        $settings = isset($cached['settings']) && is_array($cached['settings']) ? $cached['settings'] : array();
        return array(
            'note' => isset($cached['note']) ? (string) $cached['note'] : '',
            'settings' => $settings,
        );
    }

    /**
     * @param array{note:string,settings:array<string,mixed>} $row
     */
    private static function remember($context_id, $user_id, array $row) {
        self::$cache[$context_id.':'.$user_id] = $row;
        if ( ! isset($_SESSION) || ! is_array($_SESSION) ) {
            return;
        }
        Cache::setContext(self::CACHE_LOCATION, $context_id, array(
            'user_id' => (int) $user_id,
            'note' => $row['note'],
            'settings' => $row['settings'],
        ));
    }

    /**
     * Drop the session copy after a gear save so the next page reads the new row.
     */
    private static function forgetSession($context_id) {
        if ( ! isset($_SESSION) || ! is_array($_SESSION) ) {
            return;
        }
        Cache::invalidateContext(self::CACHE_LOCATION, $context_id);
    }

    private static function viewerIsInstructor() {
        if ( ! empty($_SESSION['isinstructor']) || ! empty($_SESSION['instructor']) ) {
            return true;
        }
        if ( isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes' ) {
            return true;
        }
        try {
            $context_id = ReqScope::currentContextId();
            $user_id = ReqScope::loggedInUserId();
            if ( $context_id < 1 || $user_id < 1 ) {
                return false;
            }
            return \Tsugi\Core\Membership::ensureInSession($context_id, $user_id)->isInstructor();
        } catch ( \Throwable $e ) {
            return false;
        }
    }

    /**
     * @param mixed $raw
     * @return array<string,mixed>
     */
    private static function decode($raw) {
        if ( ! is_string($raw) || $raw === '' ) {
            return array();
        }
        $data = json_decode($raw, true);
        if ( ! is_array($data) ) {
            return array();
        }
        if ( isset($data['home_off']) && is_array($data['home_off']) ) {
            $off = array();
            foreach ( $data['home_off'] as $key ) {
                if ( is_string($key) && $key !== '' && strlen($key) <= 512 ) {
                    $off[$key] = true;
                }
                if ( count($off) >= 200 ) {
                    break;
                }
            }
            $data['home_off'] = array_keys($off);
        } else {
            unset($data['home_off']);
        }
        return $data;
    }

    /**
     * @param array<string,mixed> $settings
     * @return string|null
     */
    private static function encode(array $settings) {
        if ( isset($settings['home_off']) && is_array($settings['home_off']) && count($settings['home_off']) === 0 ) {
            unset($settings['home_off']);
        }
        if ( count($settings) === 0 ) {
            return null;
        }
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : null;
    }
}
