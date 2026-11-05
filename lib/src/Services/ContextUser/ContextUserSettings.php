<?php

namespace Tsugi\Services\ContextUser;

use Tsugi\Core\LTIX;

/**
 * One row per person per course in lti_context_user_settings.
 *
 * note is plain text, at most 500 characters. settings is a JSON object.
 * The first key is home_off: HomeLayout::blockKey values this person hid
 * on the course home page. Later settings can share that object.
 *
 * A row exists only after a save. An empty note and empty settings remove it.
 */
class ContextUserSettings {

    const MAX_NOTE = 500;

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
     * @return array{note:string,settings:array<string,mixed>}
     */
    public static function load($context_id, $user_id) {
        global $CFG, $PDOX;

        $empty = array('note' => '', 'settings' => array());
        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 || $user_id < 1 ) {
            return $empty;
        }
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT note, settings FROM {$CFG->dbprefix}lti_context_user_settings
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( ! is_array($row) ) {
            return $empty;
        }
        return array(
            'note' => isset($row['note']) ? (string) $row['note'] : '',
            'settings' => self::decode(isset($row['settings']) ? $row['settings'] : null),
        );
    }

    /**
     * Store the note. An empty note removes the row only when settings are empty too.
     *
     * @param int $context_id
     * @param int $user_id
     * @param string $note Already passed through plain()
     */
    public static function saveNote($context_id, $user_id, $note) {
        $row = self::load($context_id, $user_id);
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
        $row = self::load($context_id, $user_id);
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
        LTIX::getConnection();
        if ( $note === '' && $settings === null ) {
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}lti_context_user_settings
                 WHERE context_id = :CID AND user_id = :UID",
                array(':CID' => $context_id, ':UID' => $user_id)
            );
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
