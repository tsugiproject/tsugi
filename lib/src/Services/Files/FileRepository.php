<?php

namespace Tsugi\Services\Files;

use Tsugi\Blob\BlobUtil;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Course Files tool rows, folders, and hrefs. Does not emit HTTP.
 *
 * Generic blob I/O stays on BlobUtil / Access. This is course Files rules
 * (folders, Public/Student/Private, backref=files) on top of blob_file.
 */
class FileRepository {

    /** Stored URL prefix; must match Controllers\Files::ROUTE. */
    const HREF_PREFIX = '/files';

    const STUDENT_FILES_FOLDER = 'Student';
    const PUBLIC_FOLDER = 'Public';
    const PRIVATE_FOLDER = 'Private';
    const KIND_FILE = 'file';
    const KIND_FOLDER = 'folder';
    const BACKREF = 'files';
    const FOLDER_CONTENTTYPE = 'inode/directory';

    /**
     * Ensure the synthetic Files lti_link for this course. No session wiring.
     *
     * Same row as Tool::lmsEnsureAnalyticsLink (lms:/files). Implemented here so
     * Files pages and cartridge import work without the lms-util.php globals.
     *
     * @param int $context_id
     * @return int|false
     */
    public static function ensureLink($context_id) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return false;
        }
        LTIX::getConnection();
        $link_key = 'lms:'.self::HREF_PREFIX;
        $title = 'Files';
        $path = self::HREF_PREFIX;
        $sha = function_exists('lti_sha256') ? lti_sha256($link_key) : hash('sha256', $link_key);

        $row = $PDOX->rowDie(
            "SELECT link_id, title, path
             FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_sha256 = :SHA",
            array(':CID' => $context_id, ':SHA' => $sha)
        );

        if ( ! $row ) {
            $stmt = $PDOX->queryReturnError(
                "INSERT IGNORE INTO {$CFG->dbprefix}lti_link
                    (link_sha256, link_key, context_id, title, path, updated_at)
                 VALUES
                    (:SHA, :KEY, :CID, :TITLE, :PATH, NOW())",
                array(
                    ':SHA' => $sha,
                    ':KEY' => $link_key,
                    ':CID' => $context_id,
                    ':TITLE' => $title,
                    ':PATH' => $path
                )
            );
            if ( ! $stmt->success ) {
                error_log('Unable to create Files analytics link context='.$context_id.' key='.$link_key);
                return false;
            }
            $row = $PDOX->rowDie(
                "SELECT link_id, title, path
                 FROM {$CFG->dbprefix}lti_link
                 WHERE context_id = :CID AND link_sha256 = :SHA",
                array(':CID' => $context_id, ':SHA' => $sha)
            );
            if ( ! $row ) {
                return false;
            }
        }

        $need_update = false;
        if ( $title != U::get($row, 'title') ) {
            $need_update = true;
        }
        if ( $path != U::get($row, 'path') ) {
            $need_update = true;
        }
        if ( $need_update ) {
            $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_link
                 SET title = COALESCE(:TITLE, title),
                     path = COALESCE(:PATH, path),
                     updated_at = NOW()
                 WHERE link_id = :LID",
                array(
                    ':TITLE' => $title,
                    ':PATH' => $path,
                    ':LID' => $row['link_id']
                )
            );
        }

        return $row['link_id'] + 0;
    }

    public static function ensureReservedFolders($link_id, $context_id) {
        self::ensureTopFolder($link_id, self::STUDENT_FILES_FOLDER, $context_id);
        self::ensureTopFolder($link_id, self::PUBLIC_FOLDER, $context_id);
        self::ensureTopFolder($link_id, self::PRIVATE_FOLDER, $context_id);
    }

    public static function ensureTopFolder($link_id, $name, $context_id) {
        if ( self::nameExists($link_id, '', $name, $context_id) ) {
            return;
        }
        global $CFG, $PDOX;
        $sha = hash('sha256', 'files-folder|'.$context_id.'|'.$link_id.'|'.$name);
        $json = json_encode(array('kind' => self::KIND_FOLDER, 'folder' => ''));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, link_id, file_sha256, file_name, contenttype, backref, json, created_at)
             VALUES
                (:CID, :LID, :SHA, :NAME, :TYPE, :BACKREF, :JSON, NOW())",
            array(
                ':CID' => (int) $context_id,
                ':LID' => $link_id,
                ':SHA' => $sha,
                ':NAME' => $name,
                ':TYPE' => self::FOLDER_CONTENTTYPE,
                ':BACKREF' => self::BACKREF,
                ':JSON' => $json
            )
        );
    }

    /**
     * Create each folder segment so $folder exists (e.g. code3/week1).
     *
     * @param mixed $folder
     */
    public static function ensureFolderPath($link_id, $folder, $context_id) {
        $folder = self::normalizeFolder($folder);
        if ( $folder === false || $folder === '' ) {
            return;
        }
        $parent = '';
        foreach ( explode('/', $folder) as $name ) {
            if ( ! self::nameExists($link_id, $parent, $name, $context_id) ) {
                if ( $parent === '' ) {
                    self::ensureTopFolder($link_id, $name, $context_id);
                } else {
                    self::createFolder($link_id, $parent, $name, $context_id);
                }
            }
            $parent = self::joinFolder($parent, $name);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allItems($link_id, $context_id) {
        global $CFG, $PDOX;
        return $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref
             FROM {$CFG->dbprefix}blob_file
             WHERE context_id = :CID AND link_id = :LID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)
             ORDER BY file_name ASC",
            array(
                ':CID' => (int) $context_id,
                ':LID' => $link_id,
                ':BR' => self::BACKREF
            )
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    public static function getItem($file_id, $context_id) {
        global $CFG, $PDOX;
        return $PDOX->rowDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_id = :ID AND context_id = :CID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':ID' => $file_id,
                ':CID' => (int) $context_id,
                ':BR' => self::BACKREF
            )
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getFileRowsBySha256($sha256, $context_id) {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id, context_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA AND context_id = :CID AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':SHA' => $sha256,
                ':CID' => (int) $context_id,
                ':BR' => self::BACKREF
            )
        );
        $out = array();
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['kind'] === self::KIND_FILE ) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * A Public file with this content hash, in any course. No login required.
     *
     * @return array<string, mixed>|null
     */
    public static function getPublicFileBySha256($sha256) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id, context_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':SHA' => $sha256,
                ':BR' => self::BACKREF
            )
        );
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['kind'] === self::KIND_FILE && self::isPublicPath($meta['folder']) ) {
                return $row;
            }
        }
        return null;
    }

    /**
     * A Public file at this folder path, in any course. No login required.
     *
     * @param string $path
     * @return array<string, mixed>|null
     */
    public static function getPublicFileByPath($path) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $slash = strrpos($path, '/');
        $name = $slash === false ? $path : substr($path, $slash + 1);
        $rows = $PDOX->allRowsDie(
            "SELECT file_id, file_name, file_sha256, contenttype, json, bytelen, created_at, backref, link_id, context_id
             FROM {$CFG->dbprefix}blob_file
             WHERE file_name = :NAME AND backref = :BR
               AND (deleted IS NULL OR deleted = 0)",
            array(
                ':NAME' => $name,
                ':BR' => self::BACKREF
            )
        );
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['kind'] === self::KIND_FILE && self::isPublicPath($meta['folder'])
                && self::pathFromFileRow($row) === $path ) {
                return $row;
            }
        }
        return null;
    }

    /**
     * File row for a folder/name path in this course.
     *
     * @param string $path
     * @return array<string, mixed>|null
     */
    public static function getFileRowByPath($path, $link_id, $context_id) {
        $slash = strrpos($path, '/');
        $folder = $slash === false ? '' : substr($path, 0, $slash);
        $name = $slash === false ? $path : substr($path, $slash + 1);
        foreach ( self::allItems($link_id, $context_id) as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['kind'] !== self::KIND_FILE ) {
                continue;
            }
            if ( $meta['folder'] === $folder && $row['file_name'] === $name ) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listFolder($link_id, $folder, $context_id) {
        $rows = self::allItems($link_id, $context_id);
        $folders = array();
        $files = array();
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['folder'] !== $folder ) {
                continue;
            }
            $item = array(
                'file_id' => $row['file_id'],
                'file_sha256' => $row['file_sha256'],
                'name' => $row['file_name'],
                'kind' => $meta['kind'],
                'bytelen' => $row['bytelen'],
                'created_at' => $row['created_at']
            );
            if ( $meta['kind'] === self::KIND_FOLDER ) {
                $folders[] = $item;
            } else {
                $files[] = $item;
            }
        }
        if ( $folder === '' ) {
            usort($folders, function($a, $b) {
                $rank = function($name) {
                    if ( strcasecmp($name, self::STUDENT_FILES_FOLDER) === 0 ) {
                        return 0;
                    }
                    if ( strcasecmp($name, self::PUBLIC_FOLDER) === 0 ) {
                        return 1;
                    }
                    if ( strcasecmp($name, self::PRIVATE_FOLDER) === 0 ) {
                        return 2;
                    }
                    return 3;
                };
                $ra = $rank($a['name']);
                $rb = $rank($b['name']);
                if ( $ra !== $rb ) {
                    return $ra - $rb;
                }
                return strcasecmp($a['name'], $b['name']);
            });
        } else {
            usort($folders, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        }
        usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        return array_merge($folders, $files);
    }

    public static function nameExists($link_id, $folder, $name, $context_id) {
        $rows = self::allItems($link_id, $context_id);
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['folder'] === $folder && strcasecmp($row['file_name'], $name) === 0 ) {
                return true;
            }
        }
        return false;
    }

    public static function folderHasChildren($link_id, $folder, $context_id) {
        $rows = self::allItems($link_id, $context_id);
        foreach ( $rows as $row ) {
            $meta = self::decodeMeta($row);
            if ( $meta['folder'] === $folder ) {
                return true;
            }
            if ( strpos($meta['folder'], $folder.'/') === 0 ) {
                return true;
            }
        }
        return false;
    }

    public static function tagFileRow($file_id, $folder, $bytelen, $context_id) {
        global $CFG, $PDOX;
        $json = json_encode(array('kind' => self::KIND_FILE, 'folder' => $folder));
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}blob_file
             SET json = :JSON, backref = :BR, bytelen = :LEN
             WHERE file_id = :ID AND context_id = :CID",
            array(
                ':JSON' => $json,
                ':BR' => self::BACKREF,
                ':LEN' => $bytelen,
                ':ID' => $file_id,
                ':CID' => (int) $context_id
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{kind: string, folder: string}
     */
    public static function decodeMeta($row) {
        $kind = self::KIND_FILE;
        $folder = '';
        if ( ! empty($row['json']) ) {
            $data = json_decode($row['json'], true);
            if ( is_array($data) ) {
                if ( isset($data['kind']) && $data['kind'] === self::KIND_FOLDER ) {
                    $kind = self::KIND_FOLDER;
                }
                if ( isset($data['folder']) && is_string($data['folder']) ) {
                    $folder = $data['folder'];
                }
            }
        } else if ( isset($row['contenttype']) && $row['contenttype'] === self::FOLDER_CONTENTTYPE ) {
            $kind = self::KIND_FOLDER;
        }
        return array('kind' => $kind, 'folder' => $folder);
    }

    /**
     * @param mixed $raw
     * @return string|false
     */
    public static function normalizeFolder($raw) {
        $raw = str_replace('\\', '/', (string)$raw);
        $raw = trim($raw, '/');
        if ( $raw === '' ) {
            return '';
        }
        $parts = explode('/', $raw);
        $clean = array();
        foreach ( $parts as $part ) {
            $part = trim($part);
            if ( $part === '' ) {
                continue;
            }
            if ( ! self::isValidName($part) ) {
                return false;
            }
            $clean[] = $part;
        }
        if ( count($clean) === 0 ) {
            return '';
        }
        if ( count($clean) > 12 ) {
            return false;
        }
        if ( strcasecmp($clean[0], self::STUDENT_FILES_FOLDER) === 0 ) {
            $clean[0] = self::STUDENT_FILES_FOLDER;
        } else if ( strcasecmp($clean[0], self::PUBLIC_FOLDER) === 0 ) {
            $clean[0] = self::PUBLIC_FOLDER;
        } else if ( strcasecmp($clean[0], self::PRIVATE_FOLDER) === 0 ) {
            $clean[0] = self::PRIVATE_FOLDER;
        }
        return implode('/', $clean);
    }

    public static function isValidName($name) {
        if ( ! is_string($name) ) {
            return false;
        }
        $name = trim($name);
        if ( $name === '' || $name === '.' || $name === '..' ) {
            return false;
        }
        if ( strlen($name) > 128 ) {
            return false;
        }
        if ( strpos($name, '/') !== false || strpos($name, '\\') !== false ) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9._\\- ]+$/', $name);
    }

    public static function joinFolder($parent, $name) {
        if ( $parent === '' || $parent === null ) {
            return $name;
        }
        return $parent . '/' . $name;
    }

    /**
     * @return string|null
     */
    public static function parentFolder($folder) {
        if ( $folder === '' ) {
            return null;
        }
        $pos = strrpos($folder, '/');
        if ( $pos === false ) {
            return '';
        }
        return substr($folder, 0, $pos);
    }

    public static function firstFolderSegment($folder) {
        if ( $folder === '' || $folder === null ) {
            return '';
        }
        $slash = strpos($folder, '/');
        if ( $slash === false ) {
            return $folder;
        }
        return substr($folder, 0, $slash);
    }

    public static function isStudentFilesPath($folder) {
        return strcasecmp(self::firstFolderSegment($folder), self::STUDENT_FILES_FOLDER) === 0;
    }

    public static function isPublicPath($folder) {
        return strcasecmp(self::firstFolderSegment($folder), self::PUBLIC_FOLDER) === 0;
    }

    public static function isPrivatePath($folder) {
        return strcasecmp(self::firstFolderSegment($folder), self::PRIVATE_FOLDER) === 0;
    }

    public static function isReservedName($name) {
        return strcasecmp($name, self::STUDENT_FILES_FOLDER) === 0
            || strcasecmp($name, self::PUBLIC_FOLDER) === 0
            || strcasecmp($name, self::PRIVATE_FOLDER) === 0;
    }

    public static function isReservedRootFolder($path) {
        return strcasecmp($path, self::STUDENT_FILES_FOLDER) === 0
            || strcasecmp($path, self::PUBLIC_FOLDER) === 0
            || strcasecmp($path, self::PRIVATE_FOLDER) === 0;
    }

    /**
     * True when $sha is a 64-character hex SHA-256 identifier.
     *
     * @param mixed $sha
     * @return bool
     */
    public static function isSha256($sha) {
        return is_string($sha) && (bool) preg_match('/^[a-fA-F0-9]{64}$/', $sha);
    }

    /**
     * Extract a SHA-256 from a Tsugi Files download href, or null.
     *
     * @param mixed $href
     * @return string|null Lowercase hex digest
     */
    public static function sha256FromDownloadHref($href) {
        if ( ! is_string($href) || $href === '' ) {
            return null;
        }
        if ( preg_match('~files/download/([a-fA-F0-9]{64})(?:[/?#]|$)~', $href, $m) ) {
            return strtolower($m[1]);
        }
        return null;
    }

    /**
     * Path form of a course file URL (/files/{folder}/{name}).
     *
     * @param mixed $path
     * @return string|null
     */
    public static function hrefForPath($path) {
        $path = self::normalizeFilePath($path);
        if ( $path === null ) {
            return null;
        }
        $parts = explode('/', $path);
        $parts = array_map('rawurlencode', $parts);
        return self::HREF_PREFIX . '/' . implode('/', $parts);
    }

    /**
     * Folder/name for a sha256 in this context, or null.
     *
     * @param mixed $sha256
     * @param int $context_id
     * @return string|null
     */
    public static function pathForSha256($sha256, $context_id) {
        $row = self::exportRowForSha256($sha256, $context_id);
        if ( ! is_array($row) ) {
            return null;
        }
        return self::pathFromFileRow($row);
    }

    /**
     * Replace files/download/{sha} in stored HTML with files/{path}.
     *
     * @param mixed $html
     * @param int $context_id
     * @return string
     */
    public static function rewriteDownloadHrefsToPaths($html, $context_id) {
        if ( ! is_string($html) || $html === '' ) {
            return is_string($html) ? $html : '';
        }
        return (string) preg_replace_callback(
            '#files/download/([a-fA-F0-9]{64})#',
            function ($m) use ($context_id) {
                $path = self::pathForSha256($m[1], $context_id);
                if ( $path === null || $path === '' ) {
                    return $m[0];
                }
                return 'files/'.$path;
            },
            $html
        );
    }

    /**
     * @param mixed $path
     * @return string|null
     */
    public static function normalizeFilePath($path) {
        if ( ! is_string($path) || $path === '' ) {
            return null;
        }
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        if ( $path === '' ) {
            return null;
        }
        $parts = array();
        foreach ( explode('/', $path) as $seg ) {
            $seg = rawurldecode($seg);
            $seg = trim($seg);
            if ( $seg === '' || $seg === '.' || $seg === '..' ) {
                return null;
            }
            $parts[] = $seg;
        }
        return $parts ? implode('/', $parts) : null;
    }

    /**
     * Lessons author picker fields from a blob_file row.
     * href is the stored /files/{folder}/{name} form.
     *
     * @param array<string, mixed> $row
     * @param string $folder
     * @return array<string, mixed>
     */
    public static function lessonsFilePickerItem($row, $folder = '') {
        $sha = isset($row['file_sha256']) && is_string($row['file_sha256'])
            ? strtolower($row['file_sha256']) : '';
        $name = isset($row['file_name']) && is_string($row['file_name']) ? $row['file_name'] : '';
        $ctype = isset($row['contenttype']) && is_string($row['contenttype']) ? $row['contenttype'] : '';
        if ( $folder === '' || $folder === null ) {
            $path = $name;
        } else {
            $path = $folder . '/' . $name;
        }
        return array(
            'id' => $sha,
            'sha256' => $sha,
            'title' => $name,
            'filename' => $name,
            'folder' => is_string($folder) ? $folder : '',
            'path' => $path,
            'content_type' => $ctype,
            'href' => self::hrefForPath($path) ?: self::downloadHrefForSha256($sha),
        );
    }

    /**
     * Path form of a content-addressed download URL (/files/download/{sha256}).
     *
     * @param mixed $sha256
     * @return string|null
     */
    public static function downloadHrefForSha256($sha256) {
        if ( ! self::isSha256($sha256) ) {
            return null;
        }
        return self::HREF_PREFIX . '/download/' . strtolower($sha256);
    }

    /**
     * File bytes for Common Cartridge export, or null if the blob cannot be read.
     *
     * @param mixed $sha256
     * @param int $context_id
     * @return array{bytes:string,filename:string,content_type:string,path?:string}|null
     */
    public static function readExportPayload($sha256, $context_id) {
        $row = self::exportRowForSha256($sha256, $context_id);
        if ( ! is_array($row) ) {
            return null;
        }
        $sha = strtolower((string) $sha256);
        $filename = isset($row['file_name']) && is_string($row['file_name']) && $row['file_name'] !== ''
            ? $row['file_name']
            : $sha;
        $ctype = isset($row['contenttype']) && is_string($row['contenttype'])
            ? $row['contenttype']
            : '';
        $storedPath = isset($row['path']) && is_string($row['path']) ? $row['path'] : '';
        $bytes = self::readBlobBytesBySha256($sha, $storedPath);
        if ( ! is_string($bytes) ) {
            return null;
        }
        $coursePath = self::pathFromFileRow($row);
        $out = array(
            'bytes' => $bytes,
            'filename' => $filename,
            'content_type' => $ctype,
        );
        if ( $coursePath ) {
            $out['path'] = $coursePath;
        }
        return $out;
    }

    /**
     * @param mixed $sha256
     * @param int $context_id
     * @return array<string, mixed>|null
     */
    private static function exportRowForSha256($sha256, $context_id) {
        global $CFG, $PDOX;
        if ( ! self::isSha256($sha256) ) {
            return null;
        }
        $sha = strtolower($sha256);
        $cid = (int) $context_id;
        try {
            LTIX::getConnection();
        } catch ( \Throwable $e ) {
            return null;
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            return null;
        }
        $p = $CFG->dbprefix;
        $row = null;
        if ( $cid > 0 ) {
            $row = $PDOX->rowDie(
                "SELECT file_name, contenttype, path, json
                 FROM {$p}blob_file
                 WHERE file_sha256 = :SHA AND context_id = :CID AND backref = :BR
                   AND (deleted IS NULL OR deleted = 0)
                 ORDER BY file_id DESC LIMIT 1",
                array(':SHA' => $sha, ':CID' => $cid, ':BR' => self::BACKREF)
            );
        }
        if ( ! is_array($row) ) {
            $row = $PDOX->rowDie(
                "SELECT file_name, contenttype, path, json
                 FROM {$p}blob_file
                 WHERE file_sha256 = :SHA AND backref = :BR
                   AND (deleted IS NULL OR deleted = 0)
                 ORDER BY file_id DESC LIMIT 1",
                array(':SHA' => $sha, ':BR' => self::BACKREF)
            );
        }
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return string|null
     */
    public static function pathFromFileRow($row) {
        $name = isset($row['file_name']) && is_string($row['file_name']) ? $row['file_name'] : '';
        if ( $name === '' ) {
            return null;
        }
        $folder = '';
        if ( ! empty($row['json']) && is_string($row['json']) ) {
            $data = json_decode($row['json'], true);
            if ( is_array($data) && isset($data['folder']) && is_string($data['folder']) ) {
                $folder = $data['folder'];
            }
        }
        return $folder === '' ? $name : $folder.'/'.$name;
    }

    /**
     * @param string $sha
     * @param string $storedPath
     * @return string|null
     */
    private static function readBlobBytesBySha256($sha, $storedPath) {
        global $CFG, $PDOX;
        if ( is_string($storedPath) && $storedPath !== '' ) {
            $disk = BlobUtil::resolveDiskBlobPath($storedPath);
            if ( $disk !== false ) {
                $bytes = @file_get_contents($disk);
                if ( is_string($bytes) ) {
                    return $bytes;
                }
            }
        }
        $folder = BlobUtil::getBlobFolder($sha);
        if ( is_string($folder) && $folder !== '' ) {
            $disk = $folder . '/' . $sha;
            if ( is_file($disk) ) {
                $bytes = @file_get_contents($disk);
                if ( is_string($bytes) ) {
                    return $bytes;
                }
            }
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) ) {
            return null;
        }
        $p = $CFG->dbprefix;
        $stmt = $PDOX->prepare("SELECT content FROM {$p}blob_blob WHERE blob_sha256 = :SHA LIMIT 1");
        $stmt->execute(array(':SHA' => $sha));
        $stmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
        if ( ! $stmt->fetch(\PDO::FETCH_BOUND) ) {
            return null;
        }
        if ( is_resource($lob) ) {
            $bytes = stream_get_contents($lob);
            return is_string($bytes) ? $bytes : null;
        }
        return is_string($lob) ? $lob : null;
    }

    /**
     * Create a folder row in this course's Files tool.
     */
    public static function createFolder($link_id, $folder, $name, $context_id) {
        global $CFG, $PDOX;
        $full = self::joinFolder($folder, $name);
        $sha = hash('sha256', 'files-folder|'.$context_id.'|'.$link_id.'|'.$full);
        $json = json_encode(array('kind' => self::KIND_FOLDER, 'folder' => $folder));
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, link_id, file_sha256, file_name, contenttype, backref, json, created_at)
             VALUES
                (:CID, :LID, :SHA, :NAME, :TYPE, :BACKREF, :JSON, NOW())",
            array(
                ':CID' => (int) $context_id,
                ':LID' => $link_id,
                ':SHA' => $sha,
                ':NAME' => $name,
                ':TYPE' => self::FOLDER_CONTENTTYPE,
                ':BACKREF' => self::BACKREF,
                ':JSON' => $json
            )
        );
    }

    public static function deleteFolderRow($file_id, $context_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID AND context_id = :CID AND backref = :BR",
            array(':ID' => $file_id, ':CID' => (int) $context_id, ':BR' => self::BACKREF)
        );
    }

    /**
     * Store cartridge file bytes in this course's Files tool.
     *
     * Empty $folder is the course root (obscure). Nested cartridge paths
     * are created as folders from the Files root (no extra Imported wrapper).
     * Public, Student, and Private stay those reserved folders when the
     * cartridge path starts with them.
     *
     * @return array{file_id:int,sha256:string,filename:string,href:string}
     */
    public static function importBytes($bytes, $filename, $folder = '', $contentType = 'application/octet-stream') {
        global $CFG, $PDOX;

        $bytes = (string) $bytes;
        $filename = basename(str_replace('\\', '/', (string) $filename));
        if ( $filename === '' ) {
            $filename = 'file.bin';
        }
        $folder = is_string($folder) ? trim($folder, '/') : '';
        $contentType = is_string($contentType) && $contentType !== ''
            ? $contentType
            : 'application/octet-stream';

        $context_id = U::currentContextId();
        if ( ! $context_id ) {
            die('Context required');
        }
        $link_id = self::ensureLink($context_id);
        if ( ! $link_id ) {
            die('Unable to create Files link');
        }
        self::ensureReservedFolders($link_id, $context_id);
        self::ensureFolderPath($link_id, $folder, $context_id);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $try = $filename;
        $n = 2;
        while ( self::nameExists($link_id, $folder, $try, $context_id) ) {
            $try = $base.'-'.$n.($ext !== '' ? '.'.$ext : '');
            $n++;
        }
        $filename = $try;

        $sha = hash('sha256', $bytes);
        $existing = self::getFileRowsBySha256($sha, $context_id);
        if ( count($existing) > 0 ) {
            $row = $existing[0];
            $href = self::downloadHrefForSha256($sha);
            return array(
                'file_id' => (int) $row['file_id'],
                'sha256' => $sha,
                'filename' => (string) $row['file_name'],
                'href' => is_string($href) ? $href : '',
            );
        }

        $stmt = $PDOX->queryDie(
            "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
            array(':SHA' => $sha)
        );
        $blobRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $blob_id = ($blobRow !== false) ? (int) $blobRow['blob_id'] : null;
        $blob_name = null;
        if ( ! $blob_id ) {
            $tmp = tempnam(sys_get_temp_dir(), 'ccf');
            file_put_contents($tmp, $bytes);
            if ( isset($CFG->dataroot) && $CFG->dataroot ) {
                $blob_folder = BlobUtil::mkdirSha256($sha);
                if ( $blob_folder ) {
                    $blob_name = $blob_folder.'/'.$sha;
                    if ( ! file_exists($blob_name) ) {
                        if ( ! @rename($tmp, $blob_name) ) {
                            $blob_name = null;
                        }
                    } else {
                        @unlink($tmp);
                    }
                }
            }
            if ( ! $blob_id && ! $blob_name ) {
                $fp = fopen($tmp, 'rb');
                $ins = $PDOX->prepare(
                    "INSERT INTO {$CFG->dbprefix}blob_blob (blob_sha256, content, created_at) VALUES (?, ?, NOW())"
                );
                $ins->bindParam(1, $sha);
                $ins->bindParam(2, $fp, \PDO::PARAM_LOB);
                $PDOX->beginTransaction();
                $ins->execute();
                $blob_id = (int) $PDOX->lastInsertId();
                $PDOX->commit();
                @fclose($fp);
                @unlink($tmp);
            } else if ( file_exists($tmp) ) {
                @unlink($tmp);
            }
        }

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, link_id, file_sha256, file_name, contenttype, path, backref, blob_id, created_at)
             VALUES
                (:CID, :LID, :SHA, :NAME, :TYPE, :PATH, :BACKREF, :BID, NOW())",
            array(
                ':CID' => $context_id,
                ':LID' => $link_id,
                ':SHA' => $sha,
                ':NAME' => $filename,
                ':TYPE' => $contentType,
                ':PATH' => $blob_name,
                ':BACKREF' => self::BACKREF,
                ':BID' => $blob_id,
            )
        );
        $file_id = (int) $PDOX->lastInsertId();
        self::tagFileRow($file_id, $folder, strlen($bytes), $context_id);
        $href = self::downloadHrefForSha256($sha);
        return array(
            'file_id' => $file_id,
            'sha256' => $sha,
            'filename' => $filename,
            'href' => is_string($href) ? $href : '',
        );
    }
}
