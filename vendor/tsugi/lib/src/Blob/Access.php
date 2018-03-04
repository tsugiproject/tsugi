<?php

namespace Tsugi\Blob;

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

class Access {

    public static function serveContent() {
        global $CFG, $PDOX;
        // Sanity checks
        $LAUNCH = LTIX::requireData();

        $id = $_REQUEST['id'];
        if ( strlen($id) < 1 ) {
            die("File not found");
        }

        // Check to see if we are moving from Blob store to disk store
        if ( isset($CFG->migrateblobs) && $CFG->migrateblobs ) {
            $test_key = BlobUtil::isTestKey($LAUNCH->context->key);
            $retval = self::migrate($id, $test_key);
            if ( is_string($retval) ) error_log($retval);
        }

        $p = $CFG->dbprefix;

        // https://bugs.php.net/bug.php?id=40913
        // Note - the "stream to blob" is still broken in PHP 7 so we do two separate selects
        $lob = false;
        $file_path = false;
        $stmt = $PDOX->prepare("SELECT BF.contenttype, BF.path, BF.file_name, BB.blob_id
            FROM {$p}blob_file AS BF
            LEFT JOIN {$p}blob_blob AS BB ON BF.file_sha256 = BB.blob_sha256
                AND BF.blob_id = BB.blob_id AND BB.content IS NOT NULL
            WHERE file_id = :ID AND context_id = :CID AND (link_id = :LID OR link_id IS NULL)");
        $stmt->execute(array(":ID" => $id, ":CID" => $LAUNCH->context->id, ":LID" => $LAUNCH->link->id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $row === false ) {
            error_log('File not loaded: '.$id);
            die("File not loaded");
        }
        $type = $row['contenttype'];
        $file_name = $row['file_name'];
        $file_path = $row['path'];
        $blob_id = $row['blob_id'];
        $lob = null;
        $source = 'file';

        if ( ! BlobUtil::safeFileSuffix($file_name) )  {
            error_log('Unsafe file suffix: '.$file_name);
            die('Unsafe file suffix');
        }

        // Check to see if the path is there
        if ( $file_path ) {
            if ( ! file_exists($file_path) ) {
                error_log("Missing file path if=$id file_path=$file_path");
                $file_path = false;
            }
        }

        // Is the blob is in the single instance table?
        if ( ! $file_path && $blob_id ) {
            // http://php.net/manual/en/pdo.lobs.php
            $stmt = $PDOX->prepare("SELECT content FROM {$p}blob_blob WHERE blob_id = :ID");
            $stmt->execute(array(":ID" => $blob_id));
            $stmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
            $stmt->fetch(\PDO::FETCH_BOUND);
            $source = 'blob_blob';
        }

        // New installs post 2018-02, will not create a content column in blob_file,
        // Pre 2018-02 systems will have a content column and likely will have most
        // of their blobs in this table (until they migrate the blobs and drop the column)
        // So in this code, we need to expect and handle the case where the content
        // column may or may not be there.

        // Check the "in-row" blob if we have no other source for the blob, handle
        // as non-fatal, not having a content column.   Now it is not a dood situation
        // to fall into this code with no content column because it means we are missing
        // a blob.   But adding the content column is not the solution.
        if ( !$file_path && ! $lob ) {
            try {
                $stmt = $PDOX->prepare("SELECT content FROM {$p}blob_file WHERE file_id = :ID");
                $stmt->execute(array(":ID" => $id));
                $stmt->bindColumn(1, $lob, \PDO::PARAM_LOB);
                $stmt->fetch(\PDO::FETCH_BOUND);
                $source = 'blob_file';
            } catch (\Exception $e) {
                // This is not a bad thing post 2018-02, the column is no longer there
                error_log("Error: No column for legacy blob file_id=$id");
                $lob = false;
            }
        }

        if ( !$file_path && ! $lob ) {
            error_log("No file contents file_id=$id file_path=$file_path blob_id=$blob_id");
            die('Unable to find file contents');
        }

        // Update the access time in the file table
        $stmt = $PDOX->queryDie("UPDATE {$p}blob_file SET accessed_at=NOW()
            WHERE file_id = :ID", array(":ID" => $id)
        );

        // Update the access time in the single instance blob table
        if ( $blob_id ) {
            $stmt = $PDOX->queryDie("UPDATE {$p}blob_blob SET accessed_at=NOW()
                    WHERE blob_id = :BID",
                array(":BID" => $blob_id)
            );
        }

        header('X-Tsugi-Data-Source: '.$source);
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
        if ( isset($CFG->migrateblobs) && $CFG->migrateblobs ) {
            if ( isset($CFG->dataroot) && $CFG->dataroot ) {
                if ( ! $test_key ) {
                    $retval = self::blob2file($file_id);
                }
            } else {
                $retval = self::blob2blob($file_id);
            }
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

        if ( isset($CFG->dataroot) && strlen($CFG->dataroot) > 0 ) return;

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
            error_log("Migration fid=$file_id to existing blob_file row $blob_id sha=$file_sha256");
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
        // error_log("Lob size=".strlen($lob));

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
            error_log("Migration fid=$file_id to new blob_file row $blob_id sha=$file_sha256");
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

        if ( !isset($CFG->dataroot) || strlen($CFG->dataroot) < 1 ) return;

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
            SET path=:PATH, blob_id=NULL, content=NULL WHERE file_id = :ID");
        $lstmt->execute(array(
            ":ID" => $file_id,
            ":PATH" => $blob_name
        ));
        return true;
    }

}

