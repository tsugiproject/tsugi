<?php

namespace Tsugi\Services\Notes;

use Tsugi\Core\LTIX;

/**
 * One plain-text note per person per course.
 *
 * Rows exist only after a save. An empty save removes the row.
 * Home is the only caller for now.
 */
class StudentNoteRepository {

    const MAX = 500;

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
        if ( mb_strlen($text, 'UTF-8') > self::MAX ) {
            return null;
        }
        return $text;
    }

    /**
     * @param int $context_id
     * @param int $user_id
     * @return string
     */
    public static function load($context_id, $user_id) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 || $user_id < 1 ) {
            return '';
        }
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT body FROM {$CFG->dbprefix}lti_student_note
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        if ( ! is_array($row) || ! isset($row['body']) ) {
            return '';
        }
        return (string) $row['body'];
    }

    /**
     * Store the note, or delete the row when the text is empty.
     *
     * @param int $context_id
     * @param int $user_id
     * @param string $body Already passed through plain()
     */
    public static function save($context_id, $user_id, $body) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 || $user_id < 1 ) {
            return;
        }
        $body = (string) $body;
        LTIX::getConnection();
        if ( $body === '' ) {
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}lti_student_note
                 WHERE context_id = :CID AND user_id = :UID",
                array(':CID' => $context_id, ':UID' => $user_id)
            );
            return;
        }
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_student_note
                /*PDOX pk: note_id lk: context_id,user_id */
                (context_id, user_id, body, updated_at)
             VALUES (:CID, :UID, :BODY, NOW())
             ON DUPLICATE KEY UPDATE body = VALUES(body), updated_at = NOW()",
            array(':CID' => $context_id, ':UID' => $user_id, ':BODY' => $body)
        );
    }
}
