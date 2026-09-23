<?php

namespace Tsugi\Blob;

use \Tsugi\Util\U;
use \Tsugi\UI\Output;

class BlobUtil {

    /**
     * Check to see if the $_POST is completely broken in file upload
     * Sometimes, if the maxUpload_SIZE is exceeded, it deletes all of $_POST
     * and we lose our session.
     */
    public static function emptyPostSessionLost()
    {
        return ( self::emptyPost() && !isset($_GET[session_name()]) ) ;
    }

    /**
     * Check to see if the $_POST is completely broken in file upload
     * Sometimes, if the maxUpload_SIZE is exceeded, it deletes all of $_POST
     */
    public static function emptyPost()
    {
        return ( $_SERVER['REQUEST_METHOD'] == 'POST' && count($_POST) == 0 );
    }

    /**
     * True when PHP discarded the POST because it exceeded post_max_size.
     *
     * In that case $_POST and $_FILES are empty, so callers must not treat
     * it as “no file was chosen”.
     *
     * @return bool
     */
    public static function requestLargerThanPhpPostLimit()
    {
        if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
            return false;
        }
        $postMax = self::return_bytes(ini_get('post_max_size'));
        if ( $postMax < 1 ) {
            return false;
        }
        $len = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        return $len > $postMax;
    }

    /**
     * Smaller of upload_max_filesize and post_max_size (bytes).
     *
     * @return int
     */
    public static function phpUploadLimitBytes()
    {
        $upload = self::return_bytes(ini_get('upload_max_filesize'));
        $post = self::return_bytes(ini_get('post_max_size'));
        $vals = array();
        if ( $upload > 0 ) {
            $vals[] = $upload;
        }
        if ( $post > 0 ) {
            $vals[] = $post;
        }
        return $vals ? min($vals) : 0;
    }

    /**
     * Human-readable PHP upload cap, e.g. "8 MB".
     *
     * @return string
     */
    public static function phpUploadLimitLabel()
    {
        $bytes = self::phpUploadLimitBytes();
        if ( $bytes >= 1024 * 1024 ) {
            $mb = $bytes / (1024 * 1024);
            $label = (abs($mb - round($mb)) < 0.05) ? (string) round($mb) : (string) round($mb, 1);
            return $label.' MB';
        }
        if ( $bytes >= 1024 ) {
            return (string) round($bytes / 1024).' KB';
        }
        if ( $bytes > 0 ) {
            return (string) $bytes.' bytes';
        }
        $ini = ini_get('upload_max_filesize');
        return is_string($ini) && $ini !== '' ? $ini : 'unknown';
    }

    /**
     * Flash-ready explanation when PHP rejected an upload for size.
     *
     * @param int|null $sentBytes CONTENT_LENGTH or uploaded file size
     * @return string
     */
    public static function phpUploadTooLargeMessage($sentBytes = null)
    {
        if ( $sentBytes === null && isset($_SERVER['CONTENT_LENGTH']) ) {
            $sentBytes = (int) $_SERVER['CONTENT_LENGTH'];
        }
        $sent = '';
        if ( is_int($sentBytes) && $sentBytes > 0 ) {
            $sentMb = round($sentBytes / (1024 * 1024), 1);
            $sent = ' '.sprintf(__('This request was about %s MB.'), (string) $sentMb);
        }
        return sprintf(
            __('The file is larger than this server allows (%1$s). PHP limits: upload_max_filesize=%2$s, post_max_size=%3$s.'),
            self::phpUploadLimitLabel(),
            (string) ini_get('upload_max_filesize'),
            (string) ini_get('post_max_size')
        ).$sent.' '.__('Raise both values in php.ini, .user.ini, or the Apache php_value settings (post_max_size must be at least as large as the file) and retry.');
    }

    /**
     *
     */
    public static function uploadTooLarge($filename)
    {
        return isset($_FILES[$filename]) && $_FILES[$filename]['error'] == 1 ;
    }

    public static function getFolderName()
    {
        global $CFG, $CONTEXT;
        $foldername = $CONTEXT->id;
        $root = sys_get_temp_dir(); // ends in slash
        if (U::strlen($root) > 1 && substr($root, -1) == '/') $root = substr($root,0,-1);
        if ( isset($CFG->dataroot) ) $root = $CFG->dataroot;
        $root = $root . '/lti_files';
        if ( !file_exists($root) ) mkdir($root);
        $foldername = $root.'/' . $foldername;
        return $foldername;
    }

    public static function fixFileName($name)
    {
        $new = str_replace("..","_",$name);
        $new = str_replace("/", "_", $new);
        $new = str_replace("\\", "_", $new);
        $new = str_replace("\\", "_", $new);
        $new = str_replace(" ", "_", $new);
        $new = str_replace("\n", "", $new);
        $new = str_replace("\r", "", $new);
        return $new;
    }

    // http://stackoverflow.com/questions/3592834/bad-file-extensions-that-should-be-avoided-on-a-file-upload-site
    const BAD_FILE_SUFFIXES = "/(\.|\/)(bat|exe|cmd|sh|php|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|gz|gzip|rar|ar|cpio|shar|iso|bz2|lz|rz|7z|dmg|z|sit|sitx|sea|xar|zipx|py)$/i";

    public static function safeFileSuffix($filename)
    {
        if ( self::zipOrTarKind($filename) !== null ) return true;
        if ( preg_match(self::BAD_FILE_SUFFIXES, $filename) ) return false;
        return  true;
    }

    /**
     * ZIP or TAR when this name is one of those archives. A compressed tar
     * (tar.gz, tgz, tar.bz2) counts. A plain .gz does not.
     *
     * @param mixed $filename
     * @return 'ZIP'|'TAR'|null
     */
    public static function zipOrTarKind($filename)
    {
        if ( ! is_string($filename) || $filename === '' ) {
            return null;
        }
        $base = strtolower(basename(str_replace('\\', '/', $filename)));
        if ( preg_match('/\.tar\.(gz|bz2|xz)$/', $base) ) {
            return 'TAR';
        }
        $ext = pathinfo($base, PATHINFO_EXTENSION);
        if ( $ext === 'zip' ) {
            return 'ZIP';
        }
        if ( $ext === 'tar' || $ext === 'tgz' || $ext === 'tbz' || $ext === 'tbz2' ) {
            return 'TAR';
        }
        return null;
    }

    /**
     * Type to send for a course-file download.
     *
     * Zip and tar are generic bytes so a browser does not unpack them on
     * the way down. The filename stays jquery.zip.
     *
     * HTML and SVG follow the filename. A browser can label an upload as
     * JavaScript when the file starts with an HTML comment, and nosniff
     * would then refuse to show it as a page.
     *
     * @param mixed $filename
     * @param mixed $storedType
     * @return string
     */
    public static function downloadContentType($filename, $storedType)
    {
        if ( self::zipOrTarKind($filename) !== null ) {
            return 'application/octet-stream';
        }
        $ext = '';
        if ( is_string($filename) && $filename !== '' ) {
            $base = strtolower(basename(str_replace('\\', '/', $filename)));
            $ext = pathinfo($base, PATHINFO_EXTENSION);
        }
        if ( $ext === 'html' || $ext === 'htm' || $ext === 'xhtml' || $ext === 'shtml' ) {
            return 'text/html';
        }
        if ( $ext === 'svg' || $ext === 'svgz' ) {
            return 'image/svg+xml';
        }
        return is_string($storedType) ? $storedType : '';
    }

    /**
     * Label when a course-file open should ask for confirmation. Null for
     * ordinary files such as PDF and images.
     *
     * HTML is included because a page served from this site can run script.
     * Zip and tar are legal to upload and still ask on download.
     * The upload block list is included because cartridge import can still
     * store those other files.
     *
     * @param mixed $filename
     * @return string|null
     */
    public static function cautionFileKind($filename)
    {
        if ( ! is_string($filename) || $filename === '' ) {
            return null;
        }
        $base = basename(str_replace('\\', '/', $filename));
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if ( $ext === '' ) {
            return null;
        }
        if ( $ext === 'html' || $ext === 'htm' || $ext === 'xhtml' || $ext === 'shtml' ) {
            return 'HTML';
        }
        if ( $ext === 'svg' || $ext === 'svgz' ) {
            return 'SVG';
        }
        $archive = self::zipOrTarKind($base);
        if ( $archive !== null ) {
            return $archive;
        }
        if ( ! self::safeFileSuffix($base) ) {
            return strtoupper($ext);
        }
        return null;
    }

    /**
     * HTML and SVG can be shown after confirmation. Other caution kinds download.
     *
     * @param string|null $kind
     * @return bool
     */
    public static function cautionFileOpensInline($kind)
    {
        return $kind === 'HTML' || $kind === 'SVG';
    }

    /**
     * Returns true if this is a good upload, an error string if not
     */
    public static function validateUpload($FILE_DESCRIPTOR, $SAFETY_CHECK=true)
    {
        $retval = true;
        $filename = isset($FILE_DESCRIPTOR['name']) ? basename($FILE_DESCRIPTOR['name']) : false;

        if ( $FILE_DESCRIPTOR['error'] == 1) {
            $retval = _m("General upload failure");
        } else if ( $FILE_DESCRIPTOR['error'] == 4) {
            $retval = _m('Missing file, make sure to select file(s) before pressing submit');
        } else if ( $filename === false ) {
            $retval = _m("Uploaded file has no name");
        } else if ( $FILE_DESCRIPTOR['size'] < 1 ) {
            $retval = _m("File is empty: ").$filename;
        } else if ( $FILE_DESCRIPTOR['error'] == 0 ) {
            if ( $SAFETY_CHECK && ! self::safeFileSuffix($filename) ) $retval = _m("File suffix not allowed");
        } else {
            $retval = _m("Upload failure=").$FILE_DESCRIPTOR['error'];
        }
        return $retval;
    }

    public static function checkFileSafety($FILE_DESCRIPTOR, $CONTENT_TYPES=array("image/png", "image/jpeg") )
    {
        $retval = true;
        $filename = isset($FILE_DESCRIPTOR['name']) ? basename($FILE_DESCRIPTOR['name']) : false;

        $retval = self::validateUpload($FILE_DESCRIPTOR, true);
        if ( is_string($retval) ) return $retval;

        $contenttype = $FILE_DESCRIPTOR['type'];
        if ( ! in_array($contenttype, $CONTENT_TYPES) ) $retval = "Content type ".$contenttype." not allowed";

        return $retval;
    }

    /**
      * Make sure the contents of this file are a PNG or JPEG
      */
    public static function isPngOrJpeg($FILE_DESCRIPTOR)
    {
        if ( !isset($FILE_DESCRIPTOR['name']) ) return false;
        if ( !isset($FILE_DESCRIPTOR['tmp_name']) ) return false;
        $info = getimagesize($FILE_DESCRIPTOR['tmp_name']);
        if ( ! is_array($info) ) return false;

        $image_type = $info[2];
        return $image_type == IMAGETYPE_JPEG || $image_type == IMAGETYPE_PNG;
    }

    public static function getBlobFolder($sha_256, $blob_root=false /* Unit Test*/)
    {
        global $CFG;
        if ( ! $blob_root ) {
            if ( ! isset($CFG->dataroot) ) return false;
            $blob_root = $CFG->dataroot;
        }

        $top_dir = substr($sha_256,0,2);
        $sub_dir = substr($sha_256,2,2);
        $top_dir = str_pad($top_dir.'',2,'0',STR_PAD_LEFT);
        $sub_dir = str_pad($sub_dir.'',2,'0',STR_PAD_LEFT);

        $blob_folder = $blob_root . '/' . $top_dir . '/' . $sub_dir ;
        return $blob_folder;
    }

    public static function mkdirSha256($sha_256, $blob_root=false /* Unit Test*/)
    {
        global $CFG;
        if ( ! $blob_root ) {
            if ( ! isset($CFG->dataroot) ) return false;
            $blob_root = $CFG->dataroot;
        }

        if ( ! is_writeable($blob_root) ) {
            error_log('Dataroot is not writeable '.$blob_root);
            return false;
        }

        $blob_folder = self::getBlobFolder($sha_256, $blob_root);

        // error_log("BF=$blob_folder\n");
        if ( file_exists($blob_folder) && is_writeable($blob_folder) ) {
            return $blob_folder;
        }
        if ( mkdir($blob_folder,0770,true) ) {
            return $blob_folder;
        }
        error_log('blob folder failure '.$blob_folder);
        return false;
    }

    /**
     * Turn blob_file.path into an absolute path using only dataroot prefix rules
     * (no legacy last-three-segment remap). Relative paths are under $CFG->dataroot.
     *
     * @param string $stored_path Value from blob_file.path
     * @return string|false Absolute path, or false if $stored_path is empty
     */
    public static function absoluteBlobPathFromStored($stored_path)
    {
        global $CFG;
        if ( ! is_string($stored_path) || $stored_path === '' ) {
            return false;
        }
        $file_path = $stored_path;
        if ( strpos($file_path, '/') !== 0 ) {
            $file_path = $CFG->dataroot . '/' . $file_path;
        }
        return $file_path;
    }

    /**
     * Resolve blob_file.path to an absolute on-disk path if the file exists.
     * Same rules as Tsugi\Blob\Access when serving disk-backed blobs:
     * relative paths are under $CFG->dataroot; if the stored absolute path
     * is outside the current dataroot and missing, try dataroot plus the
     * last three path segments (two-level SHA prefix directory + filename).
     *
     * @param string $stored_path Non-empty path from blob_file.path
     * @param bool $log_remap If true, log when the last-three fallback is used (e.g. blob serve).
     * @return string|false Absolute path, or false if no backing file
     */
    public static function resolveDiskBlobPath($stored_path, $log_remap = false)
    {
        global $CFG;
        $file_path = self::absoluteBlobPathFromStored($stored_path);
        if ( $file_path === false ) {
            return false;
        }

        if ( isset($CFG->dataroot) && $CFG->dataroot
            && strpos($file_path, $CFG->dataroot) !== 0 && ! file_exists($file_path) ) {
            $pieces = explode('/', $file_path);
            if ( count($pieces) > 3 ) {
                $last3 = array_slice($pieces, -3);
                $np = $CFG->dataroot . '/' . implode('/', $last3);
                if ( file_exists($np) ) {
                    if ( $log_remap ) {
                        error_log('Former data root');
                    }
                    $file_path = $np;
                }
            }
        }
        if ( ! file_exists($file_path) ) {
            return false;
        }
        return $file_path;
    }

    /**
     * uploadToBlob - returns blob_id or false
     *
     * Returns false for any number of failures, for better detail, use
     * validateUpload() before calling this to do the actual upload.
     */
    public static function uploadToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK=true, $backref=null)
    {
        $retval = self::uploadFileToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK, $backref);
        if ( is_array($retval) ) $retval = $retval[0];
        return $retval;
    }

    /**
     * isTestKey - Indicate if this is a key that is supposed to stay in blob_file
     */
    public static function isTestKey($key)
    {
        global $CFG;
        $testlist = array('12345');
        if ( isset($CFG->testblobs) && ! $CFG->testblobs ) return false;

        if ( isset($CFG->testblobs) ) {
            if ( is_string($CFG->testblobs) ) {
                $testlist = array($CFG->testblobs);
            } else if ( is_array($CFG->testblobs) ) {
                $testlist = $CFG->testblobs;
            } else {
                $testlist = array('12345');
            }
        }
        return in_array($key, $testlist);
    }

    /**
     * Legacy code - returns array [id, sha256]
     *
     * Returns false for any number of failures, for better detail, use
     * validateUpload() before calling this to do the actual upload.
     */
    public static function uploadFileToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK=true, $backref=null)
    {
        global $CFG, $CONTEXT, $LINK, $PDOX;

        $test_key = self::isTestKey($CONTEXT->key);

        if( $FILE_DESCRIPTOR['error'] == 1) return false;

        if( $FILE_DESCRIPTOR['error'] == 0)
        {
            $filename = basename($FILE_DESCRIPTOR['name']);
            if ( $SAFETY_CHECK && ! self::safeFileSuffix($filename) ) {
                return false;
            }

            $blob_id = null;
            $blob_name = null;
            $sha256 = hash_file('sha256', $FILE_DESCRIPTOR['tmp_name']);

            // Check if the blob is in the single instance store
            $stmt = $PDOX->queryDie(
                "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
                array(":SHA" => $sha256)
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ( $row !== false ) {
                error_log("Already had instance of $filename");
                $blob_id = $row['blob_id']+0;  // Make sure the id is an integer
            }

            // Don't store test_key (i.e. 12345) as new blobs on disk
            if (! $test_key && ! $blob_id && isset($CFG->dataroot) && $CFG->dataroot ) {
                $blob_folder = BlobUtil::mkdirSha256($sha256);
                if ( $blob_folder ) {
                    $blob_name =  $blob_folder . '/' . $sha256;
                    if ( file_exists( $blob_name ) ) {
                        error_log("Already had file on disk $filename => $blob_name");
                    } else { // Put the file into the blob space if we can
                        if ( ! (move_uploaded_file($FILE_DESCRIPTOR['tmp_name'],$blob_name))) {
                            error_log("Move fail $filename to $blob_name ");
                            $blob_name = null;
                        }
                    }
                }
            }

            // If not on disk store in the single instance table
            if (! $blob_id && ! $blob_name ) {
                $fp = fopen($FILE_DESCRIPTOR['tmp_name'], "rb");
                $sql = "INSERT INTO {$CFG->dbprefix}blob_blob
                    (blob_sha256, content, created_at)
                    VALUES (?, ?, NOW())";
                $stmt = $PDOX->prepare($sql);

                $stmt->bindParam(1, $sha256);
                $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
                // $stmt->bindParam(5, $data, \PDO::PARAM_LOB);
                $PDOX->beginTransaction();
                $stmt->execute();
                $blob_id = 0+$PDOX->lastInsertId();
                $PDOX->commit();
                @fclose($fp);
            }

            // Blob is safe somewhere, insert the file record with pointers
            if ( $blob_id || $blob_name ) {
                $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}blob_file
                    (context_id, link_id, file_sha256, file_name, contenttype, path, backref, blob_id, created_at)
                    VALUES (:CID, :LID, :SHA, :NAME, :TYPE, :PATH, :BACKREF, :BID, NOW())",
                array(
                    ":CID" => $CONTEXT->id,
                    ":LID" => $LINK->id,
                    ":SHA" => $sha256,
                    ":NAME" => $filename,
                    ":TYPE" => $FILE_DESCRIPTOR['type'],
                    ":PATH" => $blob_name,
                    ":BACKREF" => $backref,
                    ":BID" => $blob_id
                ));
                $id = 0+$PDOX->lastInsertId();
                return array($id, $sha256);
            }

            // Somehow we were unable to store the blob
            error_log("Error: Unable to store blob $filename ".$CONTEXT->id."\n");
            return false;
        }
        return false;
    }

    /**
     * Set the backref for a file entry
     *
     * This is a soft foreign key in the frorm of:
     *
     *    table:column:value
     *    peer_submit::submit_id::2
     *
     * This is uses when we want to link a blob to a record at an even finer
     * level than context_id and link_id which is done by default.
     *
     * When an application is doing a two-phase commit where it
     * is uploading the file and then creating the record in the
     * table that references the file (i.e. peer_submit in the
     * above example), it can set the back ref to have a value
     * of -1 to indicate that the file is as yet unlinked
     *
     *    peer_submit::submit_id::-1
     *
     * and then once the peer_submit record is committed call this routine to
     * point to the real record in the table.
     */
    public static function setBackref($file_id, $backref)
    {
        global $CFG, $CONTEXT, $LINK, $PDOX;

        $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}blob_file
            SET backref=:BACKREF
            WHERE file_id = :ID AND context_id=:CID AND link_id = :LID",
        array(
            ":ID" => $file_id,
            ":CID" => $CONTEXT->id,
            ":LID" => $LINK->id,
            ":BACKREF" => $backref,
        ));
    }

    /**
     * Read a file off local disk and put it into a blob
     */
    public static function uploadPathToBlob($filename, $content_type, $backref=null)
    {
        global $CFG, $CONTEXT, $LINK, $PDOX;

        $test_key = self::isTestKey($CONTEXT->key);


            $blob_id = null;
            $blob_name = null;
            $sha256 = hash_file('sha256', $filename);

            // Check if the blob is in the single instance store
            $stmt = $PDOX->queryDie(
                "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
                array(":SHA" => $sha256)
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ( $row !== false ) {
                error_log("Already had instance of $filename");
                $blob_id = $row['blob_id']+0;  // Make sure the id is an integer
            }

            // Don't store test_key (i.e. 12345) as new blobs on disk
            if (! $test_key && ! $blob_id && isset($CFG->dataroot) && $CFG->dataroot ) {
                $blob_folder = BlobUtil::mkdirSha256($sha256);
                if ( $blob_folder ) {
                    $blob_name =  $blob_folder . '/' . $sha256;
                    if ( file_exists( $blob_name ) ) {
                        error_log("Already had file on disk $filename => $blob_name");
                    } else { // Put the file into the blob space if we can
                        if ( ! (rename($filename,$blob_name))) {
                            error_log("Move fail $filename to $blob_name ");
                            $blob_name = null;
                        }
                    }
                }
            }

            // If not on disk store in the single instance table
            if (! $blob_id && ! $blob_name ) {
                $fp = fopen($filename, "rb");
                $sql = "INSERT INTO {$CFG->dbprefix}blob_blob
                    (blob_sha256, content, created_at)
                    VALUES (?, ?, NOW())";
                $stmt = $PDOX->prepare($sql);

                $stmt->bindParam(1, $sha256);
                $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
                // $stmt->bindParam(5, $data, \PDO::PARAM_LOB);
                $PDOX->beginTransaction();
                $stmt->execute();
                $blob_id = 0+$PDOX->lastInsertId();
                $PDOX->commit();
                @fclose($fp);
            }

            // Blob is safe somewhere, insert the file record with pointers
            if ( $blob_id || $blob_name ) {
                $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}blob_file
                    (context_id, link_id, file_sha256, file_name, contenttype, path, backref, blob_id, created_at)
                    VALUES (:CID, :LID, :SHA, :NAME, :TYPE, :PATH, :BACKREF, :BID, NOW())",
                array(
                    ":CID" => $CONTEXT->id,
                    ":LID" => $LINK->id,
                    ":SHA" => $sha256,
                    ":NAME" => $filename,
                    ":TYPE" => $content_type,
                    ":PATH" => $blob_name,
                    ":BACKREF" => $backref,
                    ":BID" => $blob_id
                ));
                $id = 0+$PDOX->lastInsertId();
                return $id;
            }

            // Somehow we were unable to store the blob
            error_log("Error: Unable to store blob $filename ".$CONTEXT->id."\n");
            return false;
    }

    public static function uploadFileToString($FILE_DESCRIPTOR)
    {
        global $CFG, $CONTEXT, $PDOX;

        if( $FILE_DESCRIPTOR['error'] == 1) return false;

        if( $FILE_DESCRIPTOR['error'] == 0)
        {
            $filename = basename($FILE_DESCRIPTOR['name']);

            $data = file_get_contents($FILE_DESCRIPTOR['tmp_name']);
            return $data;
        }
        return false;
    }

    /**
     * Point one blob_file row at new bytes.
     *
     * The row keeps file_id, file_name, and contenttype. The new bytes are
     * stored under their own SHA-256. A blob_blob row or dataroot file is
     * removed only when no blob_file row still names the previous digest.
     *
     * @param int $file_id
     * @param string $sourcePath Readable path to the new bytes (upload tmp file)
     * @return true|string True, or an error message
     */
    public static function replaceStoredFile($file_id, $sourcePath)
    {
        global $CFG, $CONTEXT, $PDOX;

        if ( ! is_string($sourcePath) || ! is_file($sourcePath) ) {
            return 'Replacement file is missing';
        }
        $file_id = (int) $file_id;
        $context_id = (isset($CONTEXT) && isset($CONTEXT->id)) ? (int) $CONTEXT->id : 0;
        if ( $file_id < 1 || $context_id < 1 ) {
            return 'File not found';
        }

        $sha256 = hash_file('sha256', $sourcePath);
        if ( ! is_string($sha256) || strlen($sha256) !== 64 ) {
            return 'Could not read the replacement file';
        }
        $bytelen = filesize($sourcePath);
        if ( $bytelen === false || $bytelen < 1 ) {
            return 'File is empty';
        }

        $driver = '';
        try {
            $driver = (string) $PDOX->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            $driver = '';
        }

        $PDOX->beginTransaction();
        try {
            $lock = ($driver === 'mysql') ? ' FOR UPDATE' : '';
            $file_row = $PDOX->rowDie(
                "SELECT * FROM {$CFG->dbprefix}blob_file
                 WHERE file_id = :FID AND context_id = :CID".$lock,
                array(':FID' => $file_id, ':CID' => $context_id)
            );
            if ( ! is_array($file_row) ) {
                $PDOX->rollBack();
                return 'File not found';
            }
            if ( isset($file_row['contenttype']) && $file_row['contenttype'] === 'inode/directory' ) {
                $PDOX->rollBack();
                return 'Folders cannot be replaced';
            }

            $oldSha = isset($file_row['file_sha256']) ? (string) $file_row['file_sha256'] : '';
            $oldBlobId = isset($file_row['blob_id']) ? (int) $file_row['blob_id'] : 0;
            $oldPath = isset($file_row['path']) ? (string) $file_row['path'] : '';

            if ( $oldSha !== '' && hash_equals($oldSha, $sha256) ) {
                $PDOX->queryDie(
                    "UPDATE {$CFG->dbprefix}blob_file
                     SET bytelen = :LEN
                     WHERE file_id = :FID AND context_id = :CID",
                    array(':LEN' => $bytelen, ':FID' => $file_id, ':CID' => $context_id)
                );
                $PDOX->commit();
                return true;
            }

            $placed = self::placeContent($sourcePath, $sha256);
            if ( ! is_array($placed) ) {
                $PDOX->rollBack();
                return 'Could not store the replacement file';
            }

            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}blob_file
                 SET file_sha256 = :SHA, blob_id = :BID, path = :PATH, bytelen = :LEN
                 WHERE file_id = :FID AND context_id = :CID",
                array(
                    ':SHA' => $sha256,
                    ':BID' => $placed['blob_id'],
                    ':PATH' => $placed['path'],
                    ':LEN' => $bytelen,
                    ':FID' => $file_id,
                    ':CID' => $context_id,
                )
            );

            $dropDisk = false;
            if ( $oldSha !== '' ) {
                $count_row = $PDOX->rowDie(
                    "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_file
                     WHERE file_sha256 = :SHA",
                    array(':SHA' => $oldSha)
                );
                $count = is_array($count_row) ? (int) $count_row['count'] : 0;
                if ( $count < 1 ) {
                    if ( $oldBlobId > 0 ) {
                        $PDOX->queryDie(
                            "DELETE FROM {$CFG->dbprefix}blob_blob
                             WHERE blob_id = :BID AND blob_sha256 = :SHA",
                            array(':BID' => $oldBlobId, ':SHA' => $oldSha)
                        );
                    }
                    $dropDisk = ($oldPath !== '');
                }
            }
            $PDOX->commit();
        } catch (\Throwable $e) {
            if ( $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            error_log('replaceStoredFile '.$e->getMessage());
            return 'Could not store the replacement file';
        }

        if ( $dropDisk ) {
            self::unlinkUnreferencedDisk($oldPath, $oldSha);
        }
        return true;
    }

    /**
     * Store bytes under $sha256 without changing any existing blob.
     *
     * Caller holds the transaction. Returns blob_id or path, the other null.
     *
     * @param string $sourcePath
     * @param string $sha256
     * @return array{blob_id: ?int, path: ?string}|false
     */
    private static function placeContent($sourcePath, $sha256)
    {
        global $CFG, $CONTEXT, $PDOX;

        $test_key = (isset($CONTEXT) && isset($CONTEXT->key)) ? self::isTestKey($CONTEXT->key) : true;
        $blob_id = null;
        $blob_name = null;

        $stmt = $PDOX->queryDie(
            "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
            array(':SHA' => $sha256)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ( is_array($row) && isset($row['blob_id']) ) {
            $blob_id = (int) $row['blob_id'];
        }

        if ( ! $test_key && ! $blob_id && isset($CFG->dataroot) && $CFG->dataroot ) {
            $blob_name = self::copyToContentPath($sourcePath, $sha256);
            if ( $blob_name === false ) {
                return false;
            }
        }

        if ( ! $blob_id && ! $blob_name ) {
            $blob_id = self::insertBlobRow($sourcePath, $sha256);
            if ( ! $blob_id ) {
                return false;
            }
        }

        if ( $blob_id ) {
            return array('blob_id' => $blob_id, 'path' => null);
        }
        if ( is_string($blob_name) && $blob_name !== '' ) {
            return array('blob_id' => null, 'path' => $blob_name);
        }
        return false;
    }

    /**
     * Copy bytes to dataroot/aa/bb/$sha256. Never overwrites a different file.
     *
     * @param string $sourcePath
     * @param string $sha256
     * @return string|false|null Absolute path, null when disk storage is unavailable, false on conflict
     */
    private static function copyToContentPath($sourcePath, $sha256)
    {
        $blob_folder = self::mkdirSha256($sha256);
        if ( ! $blob_folder ) {
            return null;
        }
        $dest = $blob_folder.'/'.$sha256;
        if ( is_file($dest) ) {
            $existing = hash_file('sha256', $dest);
            if ( $existing === $sha256 ) {
                return $dest;
            }
            error_log("Refusing to overwrite blob path $dest");
            return false;
        }

        $out = @fopen($dest, 'xb');
        if ( $out === false ) {
            if ( is_file($dest) ) {
                $existing = hash_file('sha256', $dest);
                return ($existing === $sha256) ? $dest : false;
            }
            return null;
        }
        $in = fopen($sourcePath, 'rb');
        if ( $in === false ) {
            fclose($out);
            @unlink($dest);
            return null;
        }
        $copied = stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        $size = filesize($sourcePath);
        if ( $copied === false || $size === false || $copied !== $size ) {
            @unlink($dest);
            return null;
        }
        $written = hash_file('sha256', $dest);
        if ( $written !== $sha256 ) {
            @unlink($dest);
            return false;
        }
        return $dest;
    }

    /**
     * @param string $sourcePath
     * @param string $sha256
     * @return int|null
     */
    private static function insertBlobRow($sourcePath, $sha256)
    {
        global $CFG, $PDOX;

        $existing = $PDOX->rowDie(
            "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
            array(':SHA' => $sha256)
        );
        if ( is_array($existing) && isset($existing['blob_id']) ) {
            return (int) $existing['blob_id'];
        }

        $fp = fopen($sourcePath, 'rb');
        if ( $fp === false ) {
            return null;
        }
        $now = self::sqlNow();
        $sql = "INSERT INTO {$CFG->dbprefix}blob_blob
            (blob_sha256, content, created_at)
            VALUES (?, ?, $now)";
        try {
            $stmt = $PDOX->prepare($sql);
            $stmt->bindParam(1, $sha256);
            $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
            $stmt->execute();
            $blob_id = (int) $PDOX->lastInsertId();
        } catch (\Throwable $e) {
            $blob_id = 0;
            error_log('blob insert '.$e->getMessage());
        }
        if ( is_resource($fp) ) {
            fclose($fp);
        }

        if ( $blob_id > 0 ) {
            return $blob_id;
        }
        $again = $PDOX->rowDie(
            "SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA",
            array(':SHA' => $sha256)
        );
        if ( is_array($again) && isset($again['blob_id']) ) {
            return (int) $again['blob_id'];
        }
        return null;
    }

    /**
     * @param string $storedPath
     * @param string $sha256
     */
    private static function unlinkUnreferencedDisk($storedPath, $sha256)
    {
        global $CFG, $PDOX;

        $count_row = $PDOX->rowDie(
            "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA",
            array(':SHA' => $sha256)
        );
        if ( ! is_array($count_row) || (int) $count_row['count'] > 0 ) {
            return;
        }
        $disk = self::resolveDiskBlobPath($storedPath);
        if ( $disk === false ) {
            return;
        }
        $hash = @hash_file('sha256', $disk);
        if ( ! is_string($hash) || ! hash_equals($sha256, $hash) ) {
            error_log("Refusing to unlink $disk");
            return;
        }
        if ( ! @unlink($disk) ) {
            error_log("Unlink failed: $disk");
        }
    }

    /**
     * SQL clock expression. SQLite is only for unit tests.
     *
     * @return string
     */
    private static function sqlNow()
    {
        global $PDOX;
        try {
            if ( isset($PDOX) && is_object($PDOX)
                && $PDOX->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ) {
                return "datetime('now')";
            }
        } catch (\Throwable $e) {
            // Fall through to MySQL.
        }
        return 'NOW()';
    }

    /**
     * Delete a blob based on its id
     *
     * This cleans up files from the file table and if there
     * are no more references to the file, eliminates the blob.
     * This can only delete blobs in the current CONTEXT - but
     * ultimately it is up to the calling code to decide who
     * (i.e. an instructor) is allowed to delete a file.
     *
     * @param $file_id The file to delete
     * @param $admin_bypass Run outside the context of a launch
     */
    public static function deleteBlob($file_id, $admin_bypass=false)
    {
        global $CFG, $CONTEXT, $PDOX;

        if ( $admin_bypass == "admin_bypass") {
            $file_row = $PDOX->rowDie(
                "SELECT * FROM {$CFG->dbprefix}blob_file
                WHERE file_id = :FID",
                array(":FID" => $file_id)
            );
        } else {
            $file_row = $PDOX->rowDie(
                "SELECT * FROM {$CFG->dbprefix}blob_file
                WHERE file_id = :FID AND context_id = :CID",
                array(":FID" => $file_id, ":CID" => $CONTEXT->id)
            );
        }
        if ( $file_row == false ) return;
        $sha256 = $file_row['file_sha256'];
        $blob_id = $file_row['blob_id'];
        $path = $file_row['path'];
        $count_row = $PDOX->rowDie(
            "SELECT count(*) AS count FROM {$CFG->dbprefix}blob_file
             WHERE file_sha256 = :SHA",
            array(":SHA" => $sha256)
        );
        $count = $count_row['count'];
        error_log("Count=$count blob_id=$blob_id path=$path\n");

        // If this is the last / only reference...
        if ( $count <= 1 ) {
            error_log("Deleting file=$file_id sha=$sha256 last reference to blob_id=$blob_id path=$path\n");
            if ( U::strlen($path) > 0 ) {
                $disk = self::resolveDiskBlobPath($path);
                if ( $disk === false ) {
                    error_log("File was already gone: $path");
                } else {
                    $retval = unlink($disk);
                    if ( ! $retval ) {
                        error_log("Unlink failed: $disk");
                    }
                }
            }
            if ( $blob_id > 0 ) {
                $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}blob_blob
                    WHERE blob_id = :BID",
                    array(':BID' => $blob_id)
                );

                if ( $stmt->rowCount() < 1 ) {
                    error_log("Unable to delete blob_id=$blob_id");
                }
            }
        }

        // Delete the file entry
        error_log("Deleting file=$file_id");
        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}blob_file
            WHERE file_id = :FID",
            array(':FID' => $file_id)
        );

        if ( $stmt->rowCount() < 1 ) {
            error_log("Unable to delete file_id=$file_id");
        }
    }

    // Does not do access control checks - blob_serve.php does the access
    // control checks
    public static function getAccessUrlForBlob($blob_id, $serv_file=false)
    {
        global $CFG;
        if ( $serv_file !== false ) return $serv_file . '?id='.$blob_id;
        $url = Output::getUtilUrl('/blob_serve.php?id='.$blob_id);
        return $url;
    }

    // Legacy maxUpload
    public static function maxUpload() {
        $bytes = self::maxUploadBytes();
        $bytes = (int) (($bytes+1) / (1024*1024));
        return $bytes;
    }

    // http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
    // http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
    /* See also the .htaccess file.   Many MySQL servers are configured to have a max size of a
       blob as 1MB.  if you change the .htaccess you need to change the mysql configuration as well.
       this may not be possible on a low-cost provider.  */
    public static function maxUploadBytes() {
        global $CFG, $CONTEXT;
        // If blobs are going into the database, keep them small
        if ( ! isset($CFG->dataroot) ) return 1024*1024;

        // If this is a test key, we are going into the database
        $test_key = isset($CONTEXT->key) ? self::isTestKey($CONTEXT->key) : true;
        if ( $test_key ) return 1024*1024;

        // We are storing blobs on disk, look at all of the different locations
        $maxUpload = self::return_bytes(ini_get('upload_max_filesize'));
        $max_post = self::return_bytes(ini_get('post_max_size'));
        $memory_limit = self::return_bytes(ini_get('memory_limit'));
        $upload_mb = min($maxUpload, $max_post, $memory_limit);
        return $upload_mb;
    }

    // https://www.php.net/manual/en/function.ini-get.php
    public static function return_bytes ($val)
    {
        if(empty($val))return 0;

        $val = trim($val);

        preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

        $last = '';
        if(isset($matches[2])){
            $last = $matches[2];
        }

        if(isset($matches[1])){
            $val = (int) $matches[1];
        }

        switch (strtolower($last))
        {
            case 'g':
            case 'gb':
                $val *= 1024;
            case 'm':
            case 'mb':
                $val *= 1024;
            case 'k':
            case 'kb':
                $val *= 1024;
        }

        return (int) $val;
    }

    /** Check and migrate a blob from an old place to the right new place
     *
     * @return mixed true if the file was migrated, false if the file
     *      was not migrated, and a string if an error was enountered
     */
    public static function migrate($file_id, $test_key=false)
    {
        global $CFG, $PDOX;

        $retval = false;

        // Check to see where we are moving to...
        if ( isset($CFG->dataroot) && U::strlen($CFG->dataroot) > 0 ) {
            if ( ! $test_key ) {
                $retval = self::blob2file($file_id);
            }
        } else {
            $retval = self::blob2blob($file_id);
        }
        return $retval;
    }

    /** Check and migrate a blob from blob_file to blob_blob
     *
     * @return mixed true if the file was migrated, false if the file
     *      was not migrated, and a string if an error was enountered
     */
    public static function blob2blob($file_id)
    {
        global $CFG, $PDOX;

        if ( isset($CFG->dataroot) && U::strlen($CFG->dataroot) > 0 ) return;

        // Need to deal with the post 2018-02 situation where we don't even
        // have a content column in blob_file
        try {
            $stmt = $PDOX->prepare("SELECT file_sha256
                FROM {$CFG->dbprefix}blob_file
                WHERE blob_id IS NULL AND path IS NULL AND content IS NOT NULL AND file_id = :ID");
            $stmt->execute(array(':ID' => $file_id));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ( ! $row ) return false;
        } catch(\Exception $e) { // No column for old blobs
            // error_log("Content column is not present in blob_file");
            return false;
        }

        $file_sha256 = $row['file_sha256'];

        // Do we already have it in blob_blob?
        $stmt = $PDOX->prepare("SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA");
        $stmt->execute(array(":SHA" => $file_sha256));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row ) {
            $blob_id = $row['blob_id'];
            $stmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file
                SET content=NULL, blob_id=:BID WHERE file_id = :ID");
            $stmt->execute(array(':BID' => $blob_id, ':ID' => $file_id));
            error_log("Migration fid=$file_id to existing blob_blob row $blob_id sha=$file_sha256");
            return true;
        }
        // error_log("No row for $file_sha256");

        $lob = false;
        $stmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID");
        $stmt->execute(array(":ID" => $file_id));
        $stmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
        $stmt->fetch(\PDO::FETCH_BOUND);

        if ( ! is_string($lob) ) {
            $retval = "Error: LOB is not string fid=$file_id sha=$file_sha256";
            error_log($retval);
            return $retval;
        }
        // error_log("Lob size=".U::strlen($lob));

        $stmt = $PDOX->prepare("INSERT INTO {$CFG->dbprefix}blob_blob
            (blob_sha256, content, created_at)
            VALUES (?, ?, NOW())");
        $stmt->bindParam(1, $file_sha256);
        $stmt->bindParam(2, $lob, \PDO::PARAM_STR);
        // $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
        $PDOX->beginTransaction();
        $stmt->execute();
        $blob_id = 0+$PDOX->lastInsertId();
        $PDOX->commit();

        // Check if it made it...
        $stmt = $PDOX->prepare("SELECT blob_id FROM {$CFG->dbprefix}blob_blob WHERE blob_sha256 = :SHA");
        $stmt->execute(array(":SHA" => $file_sha256));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row ) {
            $blob_id = $row['blob_id'];
            $stmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file
                SET content=NULL, blob_id=:BID WHERE file_id = :ID");
            $stmt->execute(array(':BID' => $blob_id, ':ID' => $file_id));
            error_log("Migration fid=$file_id to new blob_blob row $blob_id sha=$file_sha256");
            return true;
        }

        // Bummer if this happens - doubtful but worth double checking.
        $retval = "Error: Could not find new blob_id=$blob_id for file_id=$file_id sha=$file_sha256";
        error_log($retval);
        return $retval;
    }

    /** Check and migrate a blob to its corresponding file
     *
     * @return mixed true if the file was migrated, false if the file
     *      was not migrated, and a string if an error was enountered
     */
    public static function blob2file($file_id)
    {
        global $CFG, $PDOX;

        if ( !isset($CFG->dataroot) || empty($CFG->dataroot) ) return;

        $stmt = $PDOX->prepare("SELECT file_sha256, blob_id
            FROM {$CFG->dbprefix}blob_file
            WHERE path IS NULL AND file_id = :ID");
        $stmt->execute(array(':ID' => $file_id));

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( ! $row ) return false;

        $blob_id = $row['blob_id'];
        $file_sha256 = $row['file_sha256'];
        $blob_folder = BlobUtil::mkdirSha256($file_sha256);
        if ( ! $blob_folder ) {
            return "Error: migrate=$file_id folder failed sha=$file_sha256";
        }
        $blob_name =  $blob_folder . '/' . $file_sha256;

        $lob = false;
        if ( ! $blob_id ) {
            // Cope gracefully when there is no content column in blob_file
            try {
                $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_file WHERE file_id = :ID");
                $lstmt->execute(array(":ID" => $file_id));
                $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
                $lstmt->fetch(\PDO::FETCH_BOUND);
            } catch (\Exception $e) {
                return "Error: No content to migrate for legacy blob file_id=$file_id";
            }
        } else {
            $lstmt = $PDOX->prepare("SELECT content FROM {$CFG->dbprefix}blob_blob WHERE blob_id = :ID");
            $lstmt->execute(array(":ID" => $blob_id));
            $lstmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
            $lstmt->fetch(\PDO::FETCH_BOUND);
        }

        if ( ! is_string($lob) ) {
            return "Error: LOB is not a string. fi=$file_id bi=$blob_id";
        }

        $retval = file_put_contents($blob_name, $lob);
        if ( $retval != strlen($lob) ) {
            return "Error: Failed to write fi=$file_id (".strlen($lob).") to $blob_name";
        }
        error_log("Migrated fi=$file_id (".strlen($lob).") to $blob_name");
        $lstmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file
            SET path=:PATH, blob_id=NULL WHERE file_id = :ID");
        $lstmt->execute(array(
            ":ID" => $file_id,
            ":PATH" => $blob_name
        ));

        // Make sure to handle the fact that content might not be there...
        try {
            $lstmt = $PDOX->prepare("UPDATE {$CFG->dbprefix}blob_file
                SET content=NULL WHERE file_id = :ID");
            $lstmt->execute(array( ":ID" => $file_id));
        } catch (\Exception $e ) {
            // No problem - the column won't be there...
        }
        return true;
    }

}

