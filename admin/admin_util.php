<?php

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

// TODO: deal with headers sent...
function requireLogin() {
    global $CFG, $OUTPUT;
    if ( ! isset($_SESSION['user_id']) ) {
        $_SESSION['error'] = 'Login required';
        $OUTPUT->doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function isAdmin() {
    return isset( $_SESSION['admin']) && $_SESSION['admin'] == 'yes';
}

function requireAdmin() {
    global $CFG, $OUTPUT;
    if ( $_SESSION['admin'] != 'yes' ) {
        $_SESSION['error'] = 'Login required';
        $OUTPUT->doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function findTools($dir, &$retval, $filenames=array("index.php", "tsugi.php")) {
    if ( ! is_array($filenames) ) $filenames = array($filenams);
    if ( is_dir($dir) ) {
        if ($dh = opendir($dir)) {
            while (($sub = readdir($dh)) !== false) {
                if ( strpos($sub, ".") === 0 ) continue;
                if ( in_array($sub, $filenames) ) {
                    $retval[] = $dir  ."/";
                }
                $path = $dir . '/' . $sub;
                if ( ! is_dir($path) ) continue;
                if ( $sh = opendir($path)) {
                    while (($file = readdir($sh)) !== false) {
                        if ( in_array($file, $filenames) ) {
                            $retval[] = $path  ."/" ;
                            break;
                        }
                    }
                    closedir($sh);
                }
            }
            closedir($dh);
        }
    }
}

function findAllFolders($paths)
{
    $folders = array();
    if ( is_string($paths) ) $paths = array($paths);
    foreach( $paths as $path) {
       if ( ! is_dir($path) ) continue;
       $files = scandir($path);
        foreach($files as $file) {
            if ( strpos($file,'.') === 0 ) continue;
            if ( strpos($file,'_') === 0 ) continue;
            $abs = addSlash($path).$file;
            if ( ! is_dir($abs) ) continue;
            $folders[] = $abs;
        }
    }
    return $folders;
}

function addSlash($path)
{
    if ( strlen($path) < 1 ) return $path;
    if ( substr($path,strlen($path)-1) == DIRECTORY_SEPARATOR ) return $path;
    return $path . DIRECTORY_SEPARATOR;
}

function findAllTools()
{
    global $CFG;
    // Load tools from various folders
    $tools = array();
    foreach( $CFG->tool_folders AS $tool_folder) {
        if ( $tool_folder == 'core' ) continue;
        if ( $tool_folder == 'admin' ) continue;
        findTools($CFG->dirroot.'/'.$tool_folder,$tools);
    }
    return $tools;
}

function findAllRegistrations($folders=false, $appStore=false)
{
    global $CFG;
    // Scan the tools folders for registration settings
    if ( $folders == false ) $folders = $CFG->tool_folders;
    if ( is_string($folders) ) $folders = array($folders);
    $tools = array();
    foreach( $folders AS $tool_folder) {
        if ( $tool_folder == 'core' ) continue;
        if ( $tool_folder == 'admin' ) continue;
        $some = findFiles('register.php', $CFG->dirroot . '/');
        foreach($some as $reg_file) {
            // Take off the $CFG->dirroot
            $relative = substr($reg_file,strlen($CFG->dirroot)+1);
            $url = $CFG->wwwroot . '/' . $relative;
            $url = U::remove_relative_path($url);
            $pieces = explode('/', $url);
            if ( $pieces < 2 || $pieces[count($pieces)-1] != 'register.php') {
                error_log('Unable to load tool registration from '.$tool_folder);
                continue;
            }
            $key = $pieces[count($pieces)-2];
            unset($REGISTER_LTI2);
            require($reg_file);
            if ( ! isset($REGISTER_LTI2) ) continue;
            if ( ! is_array($REGISTER_LTI2) ) continue;

            if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) &&
                isset($REGISTER_LTI2['description']) ) {
                // Valid LTI Registration
                // If Appstore - Check if the registration is marked as hidden
                if ($appStore && isset($REGISTER_LTI2['hide_from_store']) && $REGISTER_LTI2['hide_from_store']) {
                    // Skip hidden app
                    continue;
                }
            } else {
                error_log("Missing required name, short_name, and description in ".$tool_folder);
            }

            // Make an icon URL
            $fa_icon = isset($REGISTER_LTI2['FontAwesome']) ? $REGISTER_LTI2['FontAwesome'] : false;
            if ( $fa_icon !== false ) {
                $REGISTER_LTI2['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
            }
            $launch_url = str_replace('/register.php','/',$url);
            $REGISTER_LTI2['url'] = $launch_url;

            $screen_shots = U::get($REGISTER_LTI2, 'screen_shots');
            if ( is_array($screen_shots) && count($screen_shots) > 0 ) {
                $new = array();
                foreach($screen_shots as $screen_shot ) {
                    $new[] = str_replace('/register.php','/'.$screen_shot, $url);
                }
                $REGISTER_LTI2['screen_shots'] = $new;
            }

            $tools[$key] = $REGISTER_LTI2;
        }
    }
    return $tools;
}

function findFiles($filename="index.php", $reldir=false) {
    global $CFG;
    $files = array();
    foreach ( $CFG->tool_folders as $dir ) {
        if ( $reldir !== false ) $dir = $reldir . $dir;
        if ( is_dir($dir) ) {
            if ($dh = opendir($dir)) {
                while (($sub = readdir($dh)) !== false) {
                    if ( strpos($sub, ".") === 0 ) continue;
                    if ( $sub == $filename ) {
                        $files[] = $dir . '/' . $sub;
                        continue;
                    }
                    $path = $dir . '/' . $sub;
                    if ( ! is_dir($path) ) continue;
                    if ( $sh = opendir($path)) {
                        while (($file = readdir($sh)) !== false) {
                            if ( $file == $filename ) {
                                $files[] = $path  ."/" . $file;
                                break;
                            }
                        }
                        closedir($sh);
                    }
                }
                closedir($dh);
            }
        }
    }
    return $files;
}
