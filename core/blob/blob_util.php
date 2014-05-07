<?php

function getFolderName($LTI)
{
    global $CFG;
    $foldername = $LTI['context_id'];
    $root = sys_get_temp_dir(); // ends in slash
    if (strlen($root) > 1 && substr($root, -1) == '/') $root = substr($root,0,-1);
    if ( isset($CFG->dataroot) ) $root = $CFG->dataroot;
    $root = $root . '/lti_files';
    if ( !file_exists($root) ) mkdir($root);
    $foldername = $root.'/' . $foldername;
    return $foldername;
}

function fixFileName($name)
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
$BAD_FILE_SUFFIXES = "/(\.|\/)(bat|exe|cmd|sh|php|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh|zip|tar|gz|gzip|rar|ar|cpio|shar|iso|bz2|lz|rz|7z|dmg|z|tbz2|sit|sitx|sea|xar|zipx|py)$/i";

function safeFileSuffix($filename)
{
    global $BAD_FILE_SUFFIXES;

    if ( preg_match($BAD_FILE_SUFFIXES, $filename) ) return false;
    return  true;
}

function checkFileSafety($FILE_DESCRIPTOR, $CONTENT_TYPES=array("image/png", "image/jpeg") ) 
{
    global $BAD_FILE_SUFFIXES;

    $retval = true;
    $filename = false;

    if( $FILE_DESCRIPTOR['error'] == 1) {
        $retval = "General upload failure";
    } else if( $FILE_DESCRIPTOR['error'] == 0) {
        $filename = basename($FILE_DESCRIPTOR['name']);
        if ( preg_match($BAD_FILE_SUFFIXES, $filename) ) $retval = "File suffix not allowed";

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

function uploadFileToBlob($pdo, $LTI, $FILE_DESCRIPTOR, $SAFETY_CHECK=true) 
{
    global $CFG;

    if ( $SAFETY_CHECK && checkFileSafety($FILE_DESCRIPTOR) !== true ) return false;

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
        $stmt = pdo_query_die($pdo,
            "SELECT file_id, file_sha256 from {$CFG->dbprefix}blob_file
            WHERE context_id = :CID AND file_sha256 = :SHA",
            array(":CID" => $LTI['context_id'], ":SHA" => $sha256)
        );
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ( $row !== false ) {
            error_log("Already had instance of $filename");
            $row[0] = $row[0]+0;  // Make sure the id is an integer
            return $row;
        }

        $fp = fopen($FILE_DESCRIPTOR['tmp_name'], "rb");
        $stmt = $pdo->prepare("INSERT INTO {$CFG->dbprefix}blob_file 
            (context_id, file_sha256, file_name, contenttype, content, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())");
    
        $stmt->bindParam(1, $LTI['context_id']);
        $stmt->bindParam(2, $sha256);
        $stmt->bindParam(3, $filename);
        $stmt->bindParam(4, $FILE_DESCRIPTOR['type']);
        $stmt->bindParam(5, $fp, PDO::PARAM_LOB);
        // $stmt->bindParam(5, $data, PDO::PARAM_LOB);
        $pdo->beginTransaction();
        $stmt->execute();
        $id = 0+$pdo->lastInsertId();
        $pdo->commit();
        fclose($fp);
        return array($id, $sha256);
    }
    return false;
}

// Does not do access control checks - access.php does the access
// control checks
function getAccessUrlForBlob($blob_id) 
{
    global $CFG;
    $url = $CFG->wwwroot . '/core/blob/access.php?id='.$blob_id;
    return $url;
}
