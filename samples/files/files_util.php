<?php

function getFolderName($LTI)
{
    $foldername = $LTI['link_id'];
    $foldername = md5($foldername);
    $root = sys_get_temp_dir();
    if ( isset($CFG->dataroot) ) $root = $CFG->dataroot;
    $root = $root . '/dropbox';
    if ( !file_exists($root) ) mkdir($root);
    $foldername = $root.'/' . $foldername;
    return $foldername;
}

function getStudentFolder($LTI)
{
    $foldername = $LTI['link_id'];
    $userkey = $LTI['user_id'];
    $foldername = md5($foldername);
    $userkey = md5($userkey);
    $foldername = dirname(__FILE__).'/upload/' . $foldername . '-students/' . $userkey . '/';
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
