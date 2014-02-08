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

function uploadFileToBlob($pdo, $LTI, $FILE_DESCRIPTOR) 
{
    global $CFG;
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
        $stmt = pdoQueryDie($pdo,
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
