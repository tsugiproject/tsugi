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

function uploadFileToBlob($db, $LTI, $FILE_DESCRIPTOR) 
{
    global $CFG;
    if( $FILE_DESCRIPTOR['error'] == 1) return false;
    
    if( $FILE_DESCRIPTOR['error'] == 0)
    {
	    $filename = basename($FILE_DESCRIPTOR['name']);
	    if ( strpos($filename, '.php') !== false ) {
            return false;
	    }

	    $fp = fopen($FILE_DESCRIPTOR['tmp_name'], "rb");
	    $stmt = $db->prepare("INSERT INTO {$CFG->dbprefix}blob_file 
		    (context_id, file_name, contenttype, content, created_at) 
		    VALUES (?, ?, ?, ?, NOW())");
    
	    $stmt->bindParam(1, $LTI['context_id']);
	    $stmt->bindParam(2, $filename);
	    $stmt->bindParam(3, $FILE_DESCRIPTOR['type']);
	    $stmt->bindParam(4, $fp, PDO::PARAM_LOB);
	    $db->beginTransaction();
	    $stmt->execute();
        $id = 0+$db->lastInsertId();
	    $db->commit();
        return $id;
    }

    return false;
}
