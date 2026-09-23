<?php

namespace Tsugi\Services\Courses;

use Tsugi\Blob\BlobUtil;
use Tsugi\Core\LTIX;
use Tsugi\Services\Cartridge\Wipe;

/**
 * Soft-delete a course, or permanently remove one.
 *
 * delete() only sets lti_context.deleted and deleted_at. It does not remove
 * blob_file rows, lti_message rows, or badge rows.
 *
 * purge() is the later admin hard delete. Keep it. Child tables with
 * ON DELETE CASCADE go away with the context row. blob_file is ON DELETE
 * SET NULL, so file bytes are removed first. lti_message has no foreign key
 * on link_id, so those rows are deleted first. Minted badges have no foreign
 * key: context_id is set to NULL and the denormalized row stays. Until that
 * purge, badge reads join lti_context and do not filter deleted.
 */
class CourseDelete {

    /**
     * Hide a course. Sets deleted and deleted_at together.
     *
     * Does not delete files, lti_message rows, or badges.
     *
     * @param int $context_id
     * @return void
     */
    public static function delete($context_id) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            throw new \InvalidArgumentException('A course is required.');
        }
        LTIX::getConnection();
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }

        $row = $PDOX->rowDie(
            "SELECT context_id, deleted FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course not found.');
        }
        if ( (int) ($row['deleted'] ?? 0) === 1 ) {
            return;
        }

        $stmt = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_context
             SET deleted = 1, deleted_at = NOW(), updated_at = NOW()
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $context_id)
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'delete failed';
            throw new \RuntimeException('Could not delete course: '.$detail);
        }
        $stmt->closeCursor();

        $left = $PDOX->rowDie(
            "SELECT deleted, deleted_at FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( ! is_array($left) || (int) ($left['deleted'] ?? 0) !== 1 || empty($left['deleted_at']) ) {
            throw new \RuntimeException('Course was not marked deleted.');
        }
    }

    /**
     * Permanently remove a course, including files and lti_message rows.
     *
     * Settings delete does not call this. A later admin screen will.
     *
     * @param int $context_id
     * @return void
     */
    public static function purge($context_id) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            throw new \InvalidArgumentException('A course is required.');
        }
        LTIX::getConnection();
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }

        $row = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course not found.');
        }

        self::blobs($context_id);
        self::badges($context_id);
        self::messages($context_id);

        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'delete failed';
            throw new \RuntimeException('Could not delete course: '.$detail);
        }
        $stmt->closeCursor();

        $left = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        if ( is_array($left) ) {
            throw new \RuntimeException('Course row was not deleted.');
        }
    }

    /**
     * @param int $context_id
     * @return void
     */
    private static function blobs($context_id) {
        global $CFG, $PDOX;

        if ( $PDOX->metadata("{$CFG->dbprefix}blob_file") === false ) {
            return;
        }
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, contenttype, json
             FROM {$CFG->dbprefix}blob_file
             WHERE context_id = :CID
                OR link_id IN (
                    SELECT link_id FROM {$CFG->dbprefix}lti_link WHERE context_id = :CID2
                )",
            array(
                ':CID' => (int) $context_id,
                ':CID2' => (int) $context_id,
            )
        );
        if ( ! is_array($rows) ) {
            return;
        }
        $seen = array();
        foreach ( $rows as $row ) {
            $file_id = (int) ($row['file_id'] ?? 0);
            if ( $file_id < 1 || isset($seen[$file_id]) ) {
                continue;
            }
            $seen[$file_id] = true;
            if ( Wipe::isFolderRow($row) ) {
                $PDOX->queryDie(
                    "DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID",
                    array(':ID' => $file_id)
                );
                continue;
            }
            BlobUtil::deleteBlob($file_id, 'admin_bypass');
        }
    }

    /**
     * Keep minted badges. Clear context_id so they no longer point at this course.
     *
     * The public assertion URL is the badge GUID. Name, email, course title,
     * and badge title are already stored on the row.
     *
     * @param int $context_id
     * @return void
     */
    private static function badges($context_id) {
        global $CFG, $PDOX;

        $table = "{$CFG->dbprefix}badges";
        if ( $PDOX->metadata($table) === false ) {
            return;
        }
        if ( $PDOX->columnExists('context_id', $table)
            && $PDOX->columnIsNull('context_id', $table) === false ) {
            $alter = $PDOX->queryReturnError(
                "ALTER TABLE {$table} MODIFY context_id INTEGER NULL"
            );
            if ( ! $alter->success ) {
                $detail = isset($alter->errorImplode) ? (string) $alter->errorImplode : 'badge alter failed';
                throw new \RuntimeException('Could not detach course badges: '.$detail);
            }
            $alter->closeCursor();
        }
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$table} SET context_id = NULL WHERE context_id = :CID",
            array(':CID' => (int) $context_id)
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'badge update failed';
            throw new \RuntimeException('Could not detach course badges: '.$detail);
        }
        $stmt->closeCursor();
    }

    /**
     * Chat rows point at link_id with no foreign key.
     *
     * @param int $context_id
     * @return void
     */
    private static function messages($context_id) {
        global $CFG, $PDOX;

        if ( $PDOX->metadata("{$CFG->dbprefix}lti_message") === false ) {
            return;
        }
        $stmt = $PDOX->queryReturnError(
            "DELETE m FROM {$CFG->dbprefix}lti_message m
             INNER JOIN {$CFG->dbprefix}lti_link l ON l.link_id = m.link_id
             WHERE l.context_id = :CID",
            array(':CID' => (int) $context_id)
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'message delete failed';
            throw new \RuntimeException('Could not delete course messages: '.$detail);
        }
        $stmt->closeCursor();
    }
}
