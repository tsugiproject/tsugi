<?php

namespace Tsugi\Blob;

use \Tsugi\UI\Output;

class BlobUtil {

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

    public static function checkFileSafety($FILE_DESCRIPTOR, $CONTENT_TYPES=array("image/png", "image/jpeg") )
    {
        $retval = true;
        $filename = isset($FILE_DESCRIPTOR['name']) ? basename($FILE_DESCRIPTOR['name']) : false;

        if ( $FILE_DESCRIPTOR['error'] == 1) {
            $retval = "General upload failure";
        } else if ( $FILE_DESCRIPTOR['error'] == 4) {
            $retval = 'Missing file, make sure to select file(s) before pressing submit';
        } else if ( $filename === false ) {
            $retval = "Uploaded file has no name";
        } else if ( $FILE_DESCRIPTOR['size'] < 1 ) {
            $retval = "File is empty: ".$filename;
        } else if ( $FILE_DESCRIPTOR['error'] == 0 ) {
            if ( preg_match(self::BAD_FILE_SUFFIXES, $filename) ) $retval = "File suffix not allowed";

            $contenttype = $FILE_DESCRIPTOR['type'];
            if ( ! in_array($contenttype, $CONTENT_TYPES) ) $retval = "Content type ".$contenttype." not allowed";
        } else {
            $retval = "Upload failure=".$FILE_DESCRIPTOR['error'];
        }
        if ( $retval !== true ) {
            error_log($retval." file=".$filename);
        }
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

    public static function uploadFileToBlob($FILE_DESCRIPTOR, $SAFETY_CHECK=true)
    {
        global $CFG, $CONTEXT, $PDOX;

        if ( $SAFETY_CHECK && self::checkFileSafety($FILE_DESCRIPTOR) !== true ) return false;

        if( $FILE_DESCRIPTOR['error'] == 1) return false;

        if( $FILE_DESCRIPTOR['error'] == 0)
        {
            $filename = basename($FILE_DESCRIPTOR['name']);
            if ( strpos($filename, '.php') !== false ) {
                return false;
            }

            // $data = file_get_contents($FILE_DESCRIPTOR['tmp_name']);
            // $sha256 = lti_sha256($data);

            $sha256 = hash_file('sha256', $FILE_DESCRIPTOR['tmp_name']);
            $stmt = $PDOX->queryDie(
                "SELECT file_id, file_sha256 from {$CFG->dbprefix}blob_file
                WHERE context_id = :CID AND file_sha256 = :SHA",
                array(":CID" => $CONTEXT->id, ":SHA" => $sha256)
            );
            $row = $stmt->fetch(\PDO::FETCH_NUM);
            if ( $row !== false ) {
                error_log("Already had instance of $filename");
                $row[0] = $row[0]+0;  // Make sure the id is an integer
                return $row;
            }

            $fp = fopen($FILE_DESCRIPTOR['tmp_name'], "rb");
            $stmt = $PDOX->prepare("INSERT INTO {$CFG->dbprefix}blob_file
                (context_id, file_sha256, file_name, contenttype, content, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())");

            $stmt->bindParam(1, $CONTEXT->id);
            $stmt->bindParam(2, $sha256);
            $stmt->bindParam(3, $filename);
            $stmt->bindParam(4, $FILE_DESCRIPTOR['type']);
            $stmt->bindParam(5, $fp, \PDO::PARAM_LOB);
            // $stmt->bindParam(5, $data, \PDO::PARAM_LOB);
            $PDOX->beginTransaction();
            $stmt->execute();
            $id = 0+$PDOX->lastInsertId();
            $PDOX->commit();
            fclose($fp);
            return array($id, $sha256);
        }
        return false;
    }

    public static function uploadFileToString($FILE_DESCRIPTOR, $SAFETY_CHECK=true)
    {
        global $CFG, $CONTEXT, $PDOX;

        if ( $SAFETY_CHECK && self::checkFileSafety($FILE_DESCRIPTOR) !== true ) return false;

        if( $FILE_DESCRIPTOR['error'] == 1) return false;

        if( $FILE_DESCRIPTOR['error'] == 0)
        {
            $filename = basename($FILE_DESCRIPTOR['name']);
            if ( strpos($filename, '.php') !== false ) {
                return false;
            }

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
