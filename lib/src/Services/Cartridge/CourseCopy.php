<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Services\Lessons\LessonsCartridge;
use Tsugi\Services\Lessons\LessonsLegacyExport;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Services\Quiz1\ExportException;

/**
 * Copy into the current manifest course by exporting a Common Cartridge
 * and letting the import code apply it.
 *
 * Sources are the site file-based course ($CFG->lessons), when that file
 * exists, and other manifest courses this user owns.
 */
class CourseCopy {

    const FILE_SOURCE = 'file';

    /** Separate from an in-progress Import upload. */
    const SESSION_KEY = 'tsugi_cc_copy_pending';

    /**
     * @param mixed $raw
     * @return string|int|false file, a context id, or false
     */
    public static function parseSourceId($raw) {
        if ( is_int($raw) ) {
            return $raw > 0 ? $raw : false;
        }
        if ( ! is_string($raw) ) {
            return false;
        }
        $raw = trim($raw);
        if ( $raw === self::FILE_SOURCE ) {
            return self::FILE_SOURCE;
        }
        if ( preg_match('/^[1-9][0-9]*$/', $raw) ) {
            return (int) $raw;
        }
        return false;
    }

    /**
     * True when $CFG->lessons is a readable course file.
     */
    public static function fileCourseAvailable() {
        global $CFG;
        return isset($CFG->lessons) && is_string($CFG->lessons) && $CFG->lessons !== ''
            && is_readable($CFG->lessons);
    }

    /**
     * Courses the instructor may copy from. The current course is omitted.
     *
     * @return list<array{id:string,title:string,kind:string}>
     */
    public static function sources($userId, $currentContextId) {
        $out = array();
        $file = self::fileSourceRow();
        if ( $file !== null ) {
            $out[] = $file;
        }
        foreach ( self::ownedManifestRows($userId, $currentContextId) as $row ) {
            $id = isset($row['context_id']) ? (int) $row['context_id'] : 0;
            if ( $id < 1 ) {
                continue;
            }
            $title = isset($row['title']) && is_string($row['title']) ? trim($row['title']) : '';
            if ( $title === '' ) {
                $title = 'Course '.$id;
            }
            $out[] = array(
                'id' => (string) $id,
                'title' => $title,
                'kind' => 'manifest',
            );
        }
        return $out;
    }

    /**
     * Export $sourceId into an existing temp path. Returns the source title.
     *
     * @param mixed $sourceId
     * @param string $destPath
     * @return string
     */
    public static function exportTo($sourceId, $destPath, $userId, $currentContextId) {
        $parsed = self::parseSourceId($sourceId);
        if ( $parsed === false ) {
            throw new ImportException('Choose a course to copy from.');
        }
        if ( $parsed === self::FILE_SOURCE ) {
            $l = self::loadFileLessons();
            $title = self::lessonsTitle($l);
            self::writeFileCartridge($l, $destPath, $title);
            return $title !== '' ? $title : 'Site course';
        }
        $cid = (int) $parsed;
        if ( $cid === (int) $currentContextId ) {
            throw new ImportException('Choose a different course to copy from.');
        }
        if ( ! self::ownsManifestCourse($userId, $cid) ) {
            throw new ImportException('That course is not one you can copy from.');
        }
        $l = self::loadManifestLessons($cid);
        $title = self::lessonsTitle($l);
        self::writeManifestCartridge($l, $destPath, $cid);
        return $title !== '' ? $title : ('Course '.$cid);
    }

    /**
     * Thick Tsugi-flavor cartridge of a file-based lessons document.
     *
     * @param object $l
     * @param string $destPath
     * @param string $title
     */
    public static function writeFileCartridge($l, $destPath, $title = '') {
        self::assertHasModules($l);
        $zip = self::beginZip($destPath);
        try {
            $label = is_string($title) && trim($title) !== '' ? trim($title) : self::lessonsTitle($l);
            if ( $label === '' ) {
                $label = 'Site course';
            }
            LessonsLegacyExport::writeZip($l, $zip, array(
                'tsugi_lms' => 'tsugi',
                'topic' => 'lms',
                'anchors' => false,
                'thick' => true,
                'gift_qti' => true,
                'title' => $label.' import',
            ));
        } catch ( \Throwable $e ) {
            $zip->close();
            @unlink($destPath);
            throw $e;
        }
        $zip->close();
    }

    /**
     * Tsugi-flavor cartridge of a manifest course, including its files and pages.
     *
     * @param object $l
     * @param string $destPath
     * @param int $contextId Source course, used to load blobs
     */
    public static function writeManifestCartridge($l, $destPath, $contextId) {
        self::assertHasModules($l);
        $zip = self::beginZip($destPath);
        try {
            LessonsCartridge::writeZip($l, $zip, array(
                'tsugi_lms' => 'tsugi',
                'topic' => 'lms',
                'anchors' => false,
                'context_id' => (int) $contextId,
            ));
        } catch ( ExportException $e ) {
            $zip->close();
            @unlink($destPath);
            throw new ImportException($e->getMessage(), 0, $e);
        } catch ( \Throwable $e ) {
            $zip->close();
            @unlink($destPath);
            throw $e;
        }
        $zip->close();
    }

    /**
     * @return array{id:string,title:string,kind:string}|null
     */
    private static function fileSourceRow() {
        if ( ! self::fileCourseAvailable() ) {
            return null;
        }
        global $CFG;
        $json = file_get_contents($CFG->lessons);
        if ( ! is_string($json) || $json === '' ) {
            return null;
        }
        $decoded = json_decode($json);
        if ( ! is_object($decoded) ) {
            return null;
        }
        $title = isset($decoded->title) && is_string($decoded->title) ? trim($decoded->title) : '';
        if ( $title === '' ) {
            $title = 'Site course';
        }
        return array(
            'id' => self::FILE_SOURCE,
            'title' => $title,
            'kind' => 'file',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function ownedManifestRows($userId, $currentContextId) {
        global $CFG, $PDOX;

        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return array();
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! method_exists($PDOX, 'allRowsDie') ) {
            try {
                $PDOX = LTIX::getConnection();
            } catch ( \Throwable $e ) {
                return array();
            }
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! method_exists($PDOX, 'allRowsDie') ) {
            return array();
        }
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT C.context_id,
                    COALESCE(NULLIF(MF.title, ''), NULLIF(C.title, '')) AS title
             FROM {$p}lti_context AS C
             LEFT JOIN {$p}manifest AS MF ON C.manifest_id = MF.manifest_id
             WHERE C.manifest_id IS NOT NULL
               AND (C.deleted IS NULL OR C.deleted = 0)
               AND C.context_id <> :CUR
               AND (
                    C.user_id = :UID
                    OR C.key_id IN (SELECT key_id FROM {$p}lti_key WHERE user_id = :UID2)
               )
             ORDER BY title, C.context_id",
            array(
                ':UID' => $uid,
                ':UID2' => $uid,
                ':CUR' => (int) $currentContextId,
            )
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Same ownership as Settings::contextAdministrable, and the course has a manifest.
     */
    private static function ownsManifestCourse($userId, $contextId) {
        global $CFG, $PDOX;

        $uid = (int) $userId;
        $cid = (int) $contextId;
        if ( $uid < 1 || $cid < 1 ) {
            return false;
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! method_exists($PDOX, 'rowDie') ) {
            try {
                $PDOX = LTIX::getConnection();
            } catch ( \Throwable $e ) {
                return false;
            }
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! method_exists($PDOX, 'rowDie') ) {
            return false;
        }
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT C.context_id
             FROM {$p}lti_context AS C
             WHERE C.context_id = :CID
               AND C.manifest_id IS NOT NULL
               AND (C.deleted IS NULL OR C.deleted = 0)
               AND (
                    C.user_id = :UID
                    OR C.key_id IN (SELECT key_id FROM {$p}lti_key WHERE user_id = :UID2)
               )",
            array(
                ':CID' => $cid,
                ':UID' => $uid,
                ':UID2' => $uid,
            )
        );
        return is_array($row) && isset($row['context_id']);
    }

    /**
     * @return LessonsService
     */
    private static function loadFileLessons() {
        if ( ! self::fileCourseAvailable() ) {
            throw new ImportException('There is no site course to copy.');
        }
        global $CFG;
        $json = file_get_contents($CFG->lessons);
        if ( ! is_string($json) || $json === '' ) {
            throw new ImportException('Could not read the site course.');
        }
        return self::lessonsFromJson($json);
    }

    /**
     * @return LessonsService
     */
    private static function loadManifestLessons($contextId) {
        $mid = Manifest::idForContext($contextId);
        if ( $mid < 1 ) {
            throw new ImportException('That course has no manifest to copy.');
        }
        $json = Manifest::loadJson($mid);
        if ( ! is_string($json) || $json === '' ) {
            throw new ImportException('Cannot load that course.');
        }
        return self::lessonsFromJson($json);
    }

    /**
     * @param string $json
     * @return LessonsService
     */
    private static function lessonsFromJson($json) {
        $loaded = LessonsService::tryFromJson($json);
        if ( ! is_object($loaded) ) {
            $msg = is_string($loaded) && $loaded !== '' ? $loaded : 'Cannot load that course.';
            throw new ImportException($msg);
        }
        return $loaded;
    }

    /**
     * @param object $l
     */
    private static function assertHasModules($l) {
        if ( ! isset($l->lessons->modules) || ! is_array($l->lessons->modules) || count($l->lessons->modules) < 1 ) {
            throw new ImportException('That course has no modules to copy.');
        }
    }

    /**
     * @param object $l
     * @return string
     */
    private static function lessonsTitle($l) {
        if ( isset($l->lessons->title) && is_string($l->lessons->title) ) {
            return trim($l->lessons->title);
        }
        return '';
    }

    /**
     * @param string $destPath Existing tempnam path. Replaced with a new zip.
     * @return \ZipArchive
     */
    private static function beginZip($destPath) {
        if ( is_file($destPath) ) {
            unlink($destPath);
        }
        $zip = new \ZipArchive();
        if ( $zip->open($destPath, \ZipArchive::CREATE) !== true ) {
            throw new ImportException('Cannot open the cartridge zip file.');
        }
        return $zip;
    }
}
