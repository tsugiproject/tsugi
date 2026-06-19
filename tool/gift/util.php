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

function gift_quizzes_locked() {
    global $CFG;
    if ( ! isset($CFG->giftquizzes) || ! is_file($CFG->giftquizzes.'/.lock') ) return false;
    $lock = trim(file_get_contents($CFG->giftquizzes.'/.lock'));
    return strlen($lock) > 0 ? $lock : false;
}

function requested_quiz_file() {
    if ( isset($_GET['quiz']) && strlen($_GET['quiz']) > 0 ) return $_GET['quiz'];
    if ( isset($_SESSION['default_quiz']) && strlen($_SESSION['default_quiz']) > 0 ) return $_SESSION['default_quiz'];
    return false;
}

function gift_is_valid($gift) {
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
    return count($questions) >= 1 && count($errors) == 0;
}

/**
 * When a link has no saved quiz yet, load one from $CFG->giftquizzes if
 * ?quiz=filename was passed (or remembered in session), the folder is not
 * locked, and the file parses as valid GIFT.
 *
 * @return string|false Quiz filename on success, false if unchanged
 */
function try_configure_quiz_from_file(&$gift) {
    global $CFG, $LINK;

    if ( $gift !== false && strlen($gift) > 0 ) return false;
    if ( gift_quizzes_locked() !== false ) return false;

    $files = get_quiz_files();
    if ( ! $files ) return false;

    $name = requested_quiz_file();
    if ( ! is_string($name) || ! in_array($name, $files, true) ) return false;

    $candidate = file_get_contents($CFG->giftquizzes.'/'.$name);
    if ( ! gift_is_valid($candidate) ) return false;

    $LINK->setJson($candidate);
    $gift = $candidate;
    return $name;
}
