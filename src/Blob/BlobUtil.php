<?php

namespace Tsugi\Blob;

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
        if (strlen($root) > 1 && substr($root, -1) == '/') $root = substr($root,0,-1);
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
    const BAD_FILE_SUFFIXES = "/(\.|\/)(bat|exe|cmd|sh|php|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|zip|tar|gz|gzip|rar|ar|cpio|shar|iso|bz2|lz|rz|7z|dmg|z|tbz2|sit|sitx|sea|xar|zipx|py)$/i";

    public static function safeFileSuffix($filename)
    {
        if ( preg_match(self::BAD_FILE_SUFFIXES, $filename) ) return false;
        return  true;
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
            if ( $SAFETY_CHECK && preg_match(self::BAD_FILE_SUFFIXES, $filename) ) $retval = _m("File suffix not allowed");
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
     * uploadToBlob - returns blob_id or false
     *
     * Returns false for any number of failures, for better detail, use
     * validateUpload() before calling this to do the actual upload.
     */
    public static function uploadToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK=true)
    {
        $retval = self::uploadFileToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK);
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
    public static function uploadFileToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK=true)
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
            if (! $blob_id && isset($CFG->dataroot) && $CFG->dataroot ) {
                $blob_folder = BlobUtil::mkdirSha256($sha256);
                if ( $blob_folder ) {
                    $blob_name =  $blob_folder . '/' . $sha256;
                    if ( file_exists( $blob_name ) ) {
                        error_log("Already had file on disk $filename => $blob_name");
                    } else if ( $test_key ) { // Don't add to the disk
                        $blob_name = null;
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
                $stmt = $PDOX->prepare("INSERT INTO {$CFG->dbprefix}blob_blob
                    (blob_sha256, content, created_at)
                    VALUES (?, ?, NOW())");

                $stmt->bindParam(1, $sha256);
                $stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
                // $stmt->bindParam(5, $data, \PDO::PARAM_LOB);
                $PDOX->beginTransaction();
                $stmt->execute();
                $blob_id = 0+$PDOX->lastInsertId();
                $PDOX->commit();
                fclose($fp);
            }

            // Blob is safe somewhere, insert the file record with pointers
            if ( $blob_id || $blob_name ) {
                $stmt = $PDOX->prepare("INSERT INTO {$CFG->dbprefix}blob_file
                    (context_id, link_id, file_sha256, file_name, contenttype, path, blob_id, created_at)
                    VALUES (:CID, :LID, :SHA, :NAME, :TYPE, :PATH, :BID, NOW())");
                $stmt->execute(array(
                    ":CID" => $CONTEXT->id,
                    ":LID" => $LINK->id,
                    ":SHA" => $sha256,
                    ":NAME" => $filename,
                    ":TYPE" => $FILE_DESCRIPTOR['type'],
                    ":PATH" => $blob_name,
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

    // Does not do access control checks - blob_serve.php does the access
    // control checks
    public static function getAccessUrlForBlob($blob_id, $serv_file=false)
    {
        global $CFG;
        if ( $serv_file !== false ) return $serv_file . '?id='.$blob_id;
        $url = Output::getUtilUrl('/blob_serve.php?id='.$blob_id);
        return $url;
    }


    // http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
    // http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
    /* See also the .htaccess file.   Many MySQL servers are configured to have a max size of a
       blob as 1MB.  if you change the .htaccess you need to change the mysql configuration as well.
       this may not be possible on a low-cst provider.  */

    public static function maxUpload() {
        $maxUpload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($maxUpload, $max_post, $memory_limit);
        return $upload_mb;
    }

}
