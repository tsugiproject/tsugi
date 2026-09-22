<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Blob\BlobUtil;
use Tsugi\Core\LTIX;
use Tsugi\Services\Files\FileRepository;
use Tsugi\Core\Manifest;
use Tsugi\Services\Quiz1\Quiz1Repository;

/**
 * Clear a course's importable content so a cartridge can load onto a blank outline.
 *
 * Files go through BlobUtil::deleteBlob so the last blob_file row for a sha256
 * also removes the single-instance blob (blob_blob or the dataroot file).
 * Reserved Student / Public / Private folders stay; import recreates the rest.
 */
class Wipe {

    /**
     * Pages, Files, Quiz1, gradebook results, resource links, cc_object, empty Lessons.
     *
     * LMS analytics links (`lms:…`) stay so Files and other local tools keep their
     * synthetic link_id. Resource links and all results for this course are removed.
     *
     * @param int $context_id
     * @param int $user_id
     * @return void
     */
    public static function beforeImport($context_id, $user_id) {
        $context_id = (int) $context_id;
        $user_id = (int) $user_id;
        if ( $context_id < 1 ) {
            throw new ImportException('A course is required to replace content before import.');
        }
        LTIX::getConnection();
        self::results($context_id);
        self::resourceLinks($context_id);
        self::files($context_id);
        self::pages($context_id);
        self::quizzes($context_id);
        Store::deleteObjectsForContext($context_id);
        self::lessons($context_id, $user_id);
    }

    /**
     * True when a blob_file row is a Files-tool folder, not a real file.
     *
     * @param array<string, mixed> $row
     * @return bool
     */
    public static function isFolderRow(array $row) {
        if ( isset($row['contenttype']) && $row['contenttype'] === FileRepository::FOLDER_CONTENTTYPE ) {
            return true;
        }
        if ( empty($row['json']) || ! is_string($row['json']) ) {
            return false;
        }
        $data = json_decode($row['json'], true);
        return is_array($data) && isset($data['kind']) && $data['kind'] === FileRepository::KIND_FOLDER;
    }

    /**
     * True for the top-level Student, Public, or Private folder.
     *
     * @param array<string, mixed> $row
     * @return bool
     */
    public static function isReservedRootFolderRow(array $row) {
        if ( ! self::isFolderRow($row) ) {
            return false;
        }
        $parent = '';
        if ( ! empty($row['json']) && is_string($row['json']) ) {
            $data = json_decode($row['json'], true);
            if ( is_array($data) && isset($data['folder']) && is_string($data['folder']) ) {
                $parent = trim($data['folder'], '/');
            }
        }
        if ( $parent !== '' ) {
            return false;
        }
        $name = isset($row['file_name']) ? (string) $row['file_name'] : '';
        return strcasecmp($name, FileRepository::STUDENT_FILES_FOLDER) === 0
            || strcasecmp($name, FileRepository::PUBLIC_FOLDER) === 0
            || strcasecmp($name, FileRepository::PRIVATE_FOLDER) === 0;
    }

    /**
     * Local LMS tool links (Files, Pages, …) use this prefix, not LTI resource_link_id.
     *
     * @param mixed $link_key
     * @return bool
     */
    public static function isLmsAnalyticsLinkKey($link_key) {
        return is_string($link_key) && str_starts_with($link_key, 'lms:');
    }

    /**
     * Lessons v2 with no modules, so import can write the cartridge outline in place.
     *
     * @param string $title
     * @return array<string, mixed>
     */
    public static function emptyLessonsDocument($title) {
        $doc = Manifest::starter($title);
        $doc['modules'] = array();
        $doc['discussions'] = array();
        $doc['badges'] = array();
        return $doc;
    }

    /**
     * @param int $context_id
     * @return void
     */
    private static function files($context_id) {
        global $CFG, $PDOX;

        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, contenttype, json
             FROM {$CFG->dbprefix}blob_file
             WHERE context_id = :CID AND backref = :BR",
            array(
                ':CID' => (int) $context_id,
                ':BR' => FileRepository::BACKREF,
            )
        );
        foreach ( $rows as $row ) {
            $file_id = (int) ($row['file_id'] ?? 0);
            if ( $file_id < 1 ) {
                continue;
            }
            if ( self::isFolderRow($row) ) {
                if ( self::isReservedRootFolderRow($row) ) {
                    continue;
                }
                $PDOX->queryDie(
                    "DELETE FROM {$CFG->dbprefix}blob_file
                     WHERE file_id = :ID AND context_id = :CID AND backref = :BR",
                    array(
                        ':ID' => $file_id,
                        ':CID' => (int) $context_id,
                        ':BR' => FileRepository::BACKREF,
                    )
                );
                continue;
            }
            BlobUtil::deleteBlob($file_id, 'admin_bypass');
        }
    }

    /**
     * @param int $context_id
     * @return void
     */
    private static function pages($context_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}pages WHERE context_id = :CID",
            array(':CID' => (int) $context_id)
        );
    }

    /**
     * @param int $context_id
     * @return void
     */
    private static function quizzes($context_id) {
        foreach ( Quiz1Repository::listForContext($context_id) as $quiz ) {
            $id = isset($quiz->id) ? (int) $quiz->id : 0;
            if ( $id > 0 ) {
                Quiz1Repository::deleteQuiz($id, $context_id);
            }
        }
    }

    /**
     * Remove every gradebook result in this course, including grades on kept LMS tool links.
     *
     * @param int $context_id
     * @return void
     */
    private static function results($context_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "DELETE r FROM {$CFG->dbprefix}lti_result r
             INNER JOIN {$CFG->dbprefix}lti_link l ON l.link_id = r.link_id
             WHERE l.context_id = :CID",
            array(':CID' => (int) $context_id)
        );
    }

    /**
     * Drop LTI resource links. Keep `lms:` analytics rows for local tools.
     *
     * @param int $context_id
     * @return void
     */
    private static function resourceLinks($context_id) {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT link_id, link_key FROM {$CFG->dbprefix}lti_link WHERE context_id = :CID",
            array(':CID' => (int) $context_id)
        );
        foreach ( $rows as $row ) {
            if ( self::isLmsAnalyticsLinkKey($row['link_key'] ?? null) ) {
                continue;
            }
            $link_id = (int) ($row['link_id'] ?? 0);
            if ( $link_id < 1 ) {
                continue;
            }
            $PDOX->queryDie(
                "DELETE FROM {$CFG->dbprefix}lti_link WHERE link_id = :ID AND context_id = :CID",
                array(
                    ':ID' => $link_id,
                    ':CID' => (int) $context_id,
                )
            );
        }
    }

    /**
     * @param int $context_id
     * @param int $user_id
     * @return void
     */
    private static function lessons($context_id, $user_id) {
        $title = '';
        $current = Manifest::currentDocument();
        if ( is_array($current) && isset($current['json']) && is_string($current['json']) ) {
            $doc = json_decode($current['json'], true);
            if ( is_array($doc) && isset($doc['title']) && is_string($doc['title']) ) {
                $title = trim($doc['title']);
            }
        }
        Manifest::saveNewVersion(
            $context_id,
            self::emptyLessonsDocument($title),
            $user_id,
            'Reset before Common Cartridge import'
        );
    }
}
