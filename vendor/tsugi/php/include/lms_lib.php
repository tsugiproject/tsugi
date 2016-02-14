<?php

require_once($CFG->dirroot."/vendor/autoload.php");

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

function lmsDie($message=false) {
    global $CFG, $DEBUG_STRING;
    if($message !== false) {
        echo($message);
        error_log($message);
    }
    if ( $CFG->DEVELOPER === TRUE ) {
        if ( strlen($DEBUG_STRING) > 0 ) {
            echo("\n<pre>\n");
            echo(htmlentities($DEBUG_STRING));
            echo("\n</pre>\n");
        }
    }
    die();  // lmsDie
}

function line_out($output) {
    echo(htmlent_utf8($output)."<br/>\n");
    flush();
}

function error_out($output) {
    echo('<span style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

function success_out($output) {
    echo('<span style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

function loadUserInfoBypass($user_id)
{
    global $CFG, $PDOX;
    $LTI = LTIX::requireData(LTIX::CONTEXT);
    $cacheloc = 'lti_user';
    $row = Cache::check($cacheloc, $user_id);
    if ( $row != false ) return $row;
    $stmt = $PDOX->queryDie(
        "SELECT displayname, email, user_key FROM {$CFG->dbprefix}lti_user AS U
        JOIN {$CFG->dbprefix}lti_membership AS M
        ON U.user_id = M.user_id AND M.context_id = :CID
        WHERE U.user_id = :UID",
        array(":UID" => $user_id, ":CID" => $LTI['context_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ( strlen($row['displayname']) < 1 && strlen($row['user_key']) > 0 ) {
        $row['displayname'] = 'user_key:'.substr($row['user_key'],0,25);
    }
    Cache::set($cacheloc, $user_id, $row);
    return $row;
}

function loadLinkInfo($link_id)
{
    global $CFG, $PDOX;
    $LTI = LTIX::requireData(LTIX::CONTEXT);

    $cacheloc = 'lti_link';
    $row = Cache::check($cacheloc, $link_id);
    if ( $row != false ) return $row;
    $stmt = $PDOX->queryDie(
        "SELECT title FROM {$CFG->dbprefix}lti_link
            WHERE link_id = :LID AND context_id = :CID",
        array(":LID" => $link_id, ":CID" => $LTI['context_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    Cache::set($cacheloc, $link_id, $row);
    return $row;
}

$OUTPUT = new \Tsugi\UI\Output();

// http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
if ( ! function_exists('startsWith') ) {
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
}

if ( ! function_exists('endsWith') ) {
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
}

// No trailer

