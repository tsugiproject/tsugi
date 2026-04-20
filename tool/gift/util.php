<?php

function curPageURL() {
    $pageURL = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
             ? 'http'
             : 'https';
    $pageURL .= "://";
    $pageURL .= $_SERVER['HTTP_HOST'];
    //$pageURL .= $_SERVER['REQUEST_URI'];
    $pageURL .= $_SERVER['PHP_SELF'];
    return $pageURL;
}

function var_dump_pre($variable, $print=true) {
    ob_start();
    var_dump($variable);
    $result = ob_get_clean();
    if ( $print ) {
        echo("<pre>\n");
        echo(htmlentities($result));
        echo("</pre>\n");
    }
    return $result;
}

function libxml_display_error($error) 
{ 
    $return = "<br/>\n"; 
    switch ($error->level) { 
    case LIBXML_ERR_WARNING: 
        $return .= "<b>Warning $error->code</b>: "; 
        break; 
    case LIBXML_ERR_ERROR: 
        $return .= "<b>Error $error->code</b>: "; 
        break; 
    case LIBXML_ERR_FATAL: 
        $return .= "<b>Fatal Error $error->code</b>: "; 
        break; 
    } 
    $return .= trim($error->message); 
    if ($error->file) { 
        $return .= " in <b>$error->file</b>"; 
    } 
    $return .= " on line <b>$error->line</b>\n"; 

    return $return; 
} 

function get_quiz_files() {
    global $CFG;
    if ( ! isset ($CFG->giftquizzes) || ! is_dir($CFG->giftquizzes) ) return false;
    $files1 = scandir($CFG->giftquizzes);
    $files = array();
    foreach($files1 as $file) {
        if ( is_dir($CFG->giftquizzes . '/' .$file) ) continue;
        if ( $file == '.lock' ) continue;
        if ( strpos($file, '.') === 0 ) continue;
        $files[] = $file;
    }
    sort($files);
    return $files;
}
