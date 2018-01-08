<?php

namespace Tsugi\Blob;

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

class Access {

    public static function serveContent() {
        global $CFG, $CONTEXT, $PDOX;
        // Sanity checks
        $LAUNCH = LTIX::requireData(LTIX::CONTEXT);

        $id = $_REQUEST['id'];
        if ( strlen($id) < 1 ) {
            die("File not found");
        }

        $p = $CFG->dbprefix;

        // https://bugs.php.net/bug.php?id=40913
        // Note - the "stream to blob" is still broken in PHP 7 so we do two separate selects
        $lob = false;
        $file_path = false;
        $stmt = $PDOX->prepare("SELECT contenttype, path, file_name FROM {$p}blob_file
                     WHERE file_id = :ID AND context_id = :CID");
        $stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row === false ) {
            error_log('File not loaded: '.$id);
            die("File not loaded");
        }
        $type = $row['contenttype'];
        $file_name = $row['file_name'];
        $file_path = $row['path'];

        // Check to see if the path is real
        if ( $file_path ) {
            if ( ! file_exists($file_path) ) {
                error_log("Missing file path if=$id file_path=$file_path");
                $file_path = false;
            }
        }

        // Fall back to blob
        if ( ! $file_path ) {
            // http://php.net/manual/en/pdo.lobs.php
            $stmt = $PDOX->prepare("SELECT contenttype, content, path, file_name, file_id FROM {$p}blob_file
                    WHERE file_id = :ID AND context_id = :CID");
            $stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
            $stmt->bindColumn(1, $type, \PDO::PARAM_STR, 256);
            $stmt->bindColumn(2, $lob, \PDO::PARAM_LOB);
            $stmt->bindColumn(3, $file_path, \PDO::PARAM_STR, 2048);
            $stmt->bindColumn(4, $file_name, \PDO::PARAM_STR, 2048);
            $stmt->bindColumn(5, $file_id, \PDO::PARAM_INT);
            $stmt->fetch(\PDO::FETCH_BOUND);

            if ( $file_id != $id ) {
                error_log('File not loaded: '.$id);
                die("File not loaded");
            }
        }

        if ( ! BlobUtil::safeFileSuffix($file_name) )  {
            error_log('Unsafe file suffix: '.$file_name);
            die('Unsafe file suffix');
        }

        // Update the access time
        $stmt = $PDOX->queryDie("UPDATE {$p}blob_file SET accessed_at=NOW()
                    WHERE file_id = :ID AND context_id = :CID",
            array(":ID" => $id, ":CID" => $CONTEXT->id)
        );

        if ( strlen($type) > 0 ) header('Content-Type: '.$type );
        // header('Content-Disposition: attachment; filename="'.$file_name.'"');
        // header('Content-Type: text/data');

        if ( $file_path ) {
            error_log("file serve id=$id name=$file_name mime=$type path=$file_path");
            echo readfile($file_path);
        } else if ( is_string($lob) ) {
            error_log("string blob id=$id name=$file_name mime=$type");
            echo($lob);
        } else {
            error_log("resource blob id=$id name=$file_name mime=$type");
            fpassthru($lob);
        }
    }
}
