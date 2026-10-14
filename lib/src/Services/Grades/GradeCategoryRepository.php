<?php

namespace Tsugi\Services\Grades;

use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Course grade categories and the link columns that sit in them.
 *
 * A link belongs to one category or none. None is uncategorized.
 * position orders categories in the grade book. hidden keeps a column
 * off the student grade list.
 */
class GradeCategoryRepository {

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function listForContext($context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        return $PDOX->allRowsDie(
            "SELECT category_id, context_id, title, grade_percent, extra_credit, position
             FROM {$CFG->dbprefix}lti_category
             WHERE context_id = :CID
             ORDER BY position ASC, category_id ASC",
            array(':CID' => (int) $context_id)
        );
    }

    /**
     * Grade columns in this course: links that already have a grade.
     * $user_id adds that student's grade when it is set.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function gradeColumns($context_id, $user_id = 0) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $p = $CFG->dbprefix;
        $params = array(':CID' => (int) $context_id);
        $grade_sql = "NULL AS grade";
        if ( (int) $user_id > 0 ) {
            $grade_sql = "(SELECT R.grade FROM {$p}lti_result R
                WHERE R.link_id = L.link_id AND R.user_id = :UID
                  AND R.grade IS NOT NULL AND R.deleted = 0
                ORDER BY R.updated_at DESC LIMIT 1) AS grade";
            $params[':UID'] = (int) $user_id;
        }

        return $PDOX->allRowsDie(
            "SELECT L.link_id, L.title, L.hidden, L.category_id, {$grade_sql}
             FROM {$p}lti_link L
             WHERE L.context_id = :CID AND L.deleted = 0
               AND EXISTS (
                    SELECT 1 FROM {$p}lti_result G
                    WHERE G.link_id = L.link_id AND G.grade IS NOT NULL AND G.deleted = 0
               )
             ORDER BY L.hidden ASC, L.title ASC, L.link_id ASC",
            $params
        );
    }

    /**
     * @return string|null Error message, or null on success.
     */
    public static function create($context_id, $title, $percent, $extra_credit) {
        $fields = self::fields($title, $percent, $extra_credit);
        if ( is_string($fields) ) {
            return $fields;
        }

        global $CFG, $PDOX;
        LTIX::getConnection();
        $context_id = (int) $context_id;
        $q = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_category
                (context_id, title, grade_percent, extra_credit, position, updated_at)
             VALUES (:CID, :TITLE, :PCT, :EXTRA, 0, NOW())",
            array(
                ':CID' => $context_id,
                ':TITLE' => $fields['title'],
                ':PCT' => $fields['percent'],
                ':EXTRA' => $fields['extra_credit'],
            )
        );
        $error = self::writeError($q, __('A category with that title already exists.'), __('Could not add the category.'));
        if ( $error !== null ) {
            return $error;
        }

        $new_id = (int) $PDOX->lastInsertId();
        $ids = self::orderedIds($context_id);
        $kept = array();
        foreach ( $ids as $id ) {
            if ( $id !== $new_id ) {
                $kept[] = $id;
            }
        }
        $kept[] = $new_id;
        self::writePositions($context_id, $kept);
        return null;
    }

    /**
     * @return string|null Error message, or null on success.
     */
    public static function update($context_id, $category_id, $title, $percent, $extra_credit) {
        $fields = self::fields($title, $percent, $extra_credit);
        if ( is_string($fields) ) {
            return $fields;
        }
        if ( ! self::categoryInContext($category_id, $context_id) ) {
            return __('Category not found.');
        }

        global $CFG, $PDOX;
        LTIX::getConnection();
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_category
             SET title = :TITLE, grade_percent = :PCT, extra_credit = :EXTRA, updated_at = NOW()
             WHERE category_id = :ID AND context_id = :CID",
            array(
                ':TITLE' => $fields['title'],
                ':PCT' => $fields['percent'],
                ':EXTRA' => $fields['extra_credit'],
                ':ID' => (int) $category_id,
                ':CID' => (int) $context_id,
            )
        );
        return self::writeError($q, __('A category with that title already exists.'), __('Could not save the category.'));
    }

    /**
     * @return string|null Error message, or null on success.
     */
    public static function delete($context_id, $category_id) {
        if ( ! self::categoryInContext($category_id, $context_id) ) {
            return __('Category not found.');
        }

        global $CFG, $PDOX;
        LTIX::getConnection();
        $context_id = (int) $context_id;
        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}lti_category
             WHERE category_id = :ID AND context_id = :CID",
            array(':ID' => (int) $category_id, ':CID' => $context_id)
        );
        if ( ! $q->success ) {
            return __('Could not delete the category.');
        }
        self::writePositions($context_id, self::orderedIds($context_id));
        return null;
    }

    /**
     * @param string $direction up or down
     * @return string|null Error message, or null on success.
     */
    public static function move($context_id, $category_id, $direction) {
        if ( $direction !== 'up' && $direction !== 'down' ) {
            return __('Invalid move direction.');
        }
        $context_id = (int) $context_id;
        $category_id = (int) $category_id;
        $ids = self::orderedIds($context_id);
        $index = array_search($category_id, $ids, true);
        if ( $index === false ) {
            return __('Category not found.');
        }
        if ( $direction === 'up' && $index === 0 ) {
            return null;
        }
        if ( $direction === 'down' && $index === count($ids) - 1 ) {
            return null;
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        $temp = $ids[$swap];
        $ids[$swap] = $ids[$index];
        $ids[$index] = $temp;
        self::writePositions($context_id, $ids);
        return null;
    }

    /**
     * @param int|null $category_id Null clears the category.
     * @return string|null Error message, or null on success.
     */
    public static function setLinkCategory($context_id, $link_id, $category_id) {
        $link = self::linkInContext($link_id, $context_id);
        if ( ! $link ) {
            return __('Grade column not found.');
        }
        if ( $category_id !== null && ! self::categoryInContext($category_id, $context_id) ) {
            return __('Category not found.');
        }

        global $CFG, $PDOX;
        LTIX::getConnection();
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link
             SET category_id = :CAT, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID",
            array(
                ':CAT' => $category_id,
                ':LID' => (int) $link_id,
                ':CID' => (int) $context_id,
            )
        );
        if ( ! $q->success ) {
            return __('Could not update the grade column.');
        }
        return null;
    }

    /**
     * @return string|null Error message, or null on success.
     */
    public static function setLinkHidden($context_id, $link_id, $hidden) {
        if ( ! self::linkInContext($link_id, $context_id) ) {
            return __('Grade column not found.');
        }

        global $CFG, $PDOX;
        LTIX::getConnection();
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link
             SET hidden = :HIDDEN, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID",
            array(
                ':HIDDEN' => $hidden ? 1 : 0,
                ':LID' => (int) $link_id,
                ':CID' => (int) $context_id,
            )
        );
        if ( ! $q->success ) {
            return __('Could not update the grade column.');
        }
        return null;
    }

    /**
     * @return array{title:string,percent:int,extra_credit:int}|string
     */
    private static function fields($title, $percent, $extra_credit) {
        $title = trim((string) $title);
        if ( $title === '' ) {
            return __('Enter a category title.');
        }
        if ( U::strlen($title) > 255 ) {
            return __('Category title is too long.');
        }
        $percent_text = trim((string) $percent);
        if ( ! preg_match('/^\d+$/', $percent_text) ) {
            return __('Percent must be a whole number from 0 through 100.');
        }
        $percent_int = (int) $percent_text;
        if ( $percent_int > 100 ) {
            return __('Percent must be a whole number from 0 through 100.');
        }
        return array(
            'title' => $title,
            'percent' => $percent_int,
            'extra_credit' => $extra_credit ? 1 : 0,
        );
    }

    /**
     * @return string|null
     */
    private static function writeError($q, $duplicate, $failed) {
        if ( $q && $q->success ) {
            return null;
        }
        $detail = ($q && isset($q->errorImplode)) ? (string) $q->errorImplode : '';
        if ( strpos($detail, '1062') !== false || stripos($detail, 'Duplicate') !== false ) {
            return $duplicate;
        }
        return $failed;
    }

    private static function categoryInContext($category_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT category_id FROM {$CFG->dbprefix}lti_category
             WHERE category_id = :ID AND context_id = :CID",
            array(':ID' => (int) $category_id, ':CID' => (int) $context_id)
        );
        return is_array($row);
    }

    /**
     * @return array<string,mixed>|false
     */
    private static function linkInContext($link_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE link_id = :LID AND context_id = :CID AND deleted = 0",
            array(':LID' => (int) $link_id, ':CID' => (int) $context_id)
        );
        return is_array($row) ? $row : false;
    }

    /**
     * @return int[]
     */
    private static function orderedIds($context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT category_id FROM {$CFG->dbprefix}lti_category
             WHERE context_id = :CID
             ORDER BY position ASC, category_id ASC",
            array(':CID' => (int) $context_id)
        );
        $ids = array();
        foreach ( $rows as $row ) {
            $ids[] = (int) $row['category_id'];
        }
        return $ids;
    }

    /**
     * @param int[] $ids
     */
    private static function writePositions($context_id, $ids) {
        global $CFG, $PDOX;
        LTIX::getConnection();
        $position = 1;
        foreach ( $ids as $id ) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_category
                 SET position = :POS, updated_at = NOW()
                 WHERE category_id = :ID AND context_id = :CID",
                array(
                    ':POS' => $position,
                    ':ID' => (int) $id,
                    ':CID' => (int) $context_id,
                )
            );
            $position++;
        }
    }
}
