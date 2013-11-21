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

// http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
// http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
/* See also the .htaccess file.   Many MySQL servers are configured to have a max size of a 
   blob as 1MB.  if you change the .htaccess you need to change the mysql configuration as well. 
   this may not be possible on a low-cst provider.  */

function maxUpload() {
	$max_upload = (int)(ini_get('upload_max_filesize'));
	$max_post = (int)(ini_get('post_max_size'));
	$memory_limit = (int)(ini_get('memory_limit'));
	$upload_mb = min($max_upload, $max_post, $memory_limit);
	return $upload_mb;
}

?>
