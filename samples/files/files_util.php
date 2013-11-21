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
    $new = str_replace("..","-",$name);
    $new = str_replace("/", "-", $new);
    $new = str_replace("\\", "-", $new);
    $new = str_replace("\\", "-", $new);
    $new = str_replace(" ", "-", $new);
    return $new;
}

?>
