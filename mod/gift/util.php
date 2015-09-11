<?php

function htmlent_utf8($string) {
    return htmlentities($string,ENT_QUOTES,$encoding = 'UTF-8');
}

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
    if ( $print ) print htmlent_utf8($result);
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
