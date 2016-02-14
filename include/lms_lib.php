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

// Looks up a result for a potentially different user_id so we make
// sure they are in the smame key/ context / link as the current user
// hence the complex query to make sure we don't cross silos
function lookupResult($LTI, $user_id) {
    global $CFG, $PDOX;
    $stmt = $PDOX->queryDie(
        "SELECT result_id, R.link_id AS link_id, R.user_id AS user_id,
            sourcedid, service_id, grade, note, R.json AS json
        FROM {$CFG->dbprefix}lti_result AS R
        JOIN {$CFG->dbprefix}lti_link AS L
            ON L.link_id = R.link_id AND R.link_id = :LID
        JOIN {$CFG->dbprefix}lti_user AS U
            ON U.user_id = R.user_id AND U.user_id = :UID
        JOIN {$CFG->dbprefix}lti_context AS C
            ON L.context_id = C.context_id AND C.context_id = :CID
        JOIN {$CFG->dbprefix}lti_key AS K
            ON C.key_id = K.key_id AND U.key_id = K.key_id AND K.key_id = :KID
        WHERE R.user_id = :UID AND K.key_id = :KID and U.user_id = :UID AND L.link_id = :LID",
        array(":KID" => $LTI['key_id'], ":LID" => $LTI['link_id'],
            ":CID" => $LTI['context_id'], ":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row;
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

// Check the secure cookie and set login information appropriately
function loginSecureCookie() {
    global $CFG, $PDOX;
    $pieces = false;
    $id = false;

    // Only do this if we are not already logged in...
    if ( isset($_SESSION["id"]) || !isset($_COOKIE[$CFG->cookiename]) ||
            !isset($CFG->cookiepad) || $CFG->cookiepad === false) {
        return;
    }

    $ct = $_COOKIE[$CFG->cookiename];
    // error_log("Cookie: $ct \n");
    $pieces = SecureCookie::extract($ct);
    if ( $pieces === false ) {
        error_log('Decrypt fail:'.$ct);
        SecureCookie::delete();
        return;
    }

    // Convert to an integer and check valid
    $user_id = $pieces[0] + 0;
    $userSHA = $pieces[1];
    if ( $user_id < 1 ) {
        $user_id = false;
        $pieces = false;
        error_log('Decrypt bad ID:'.$pieces[0].','.$ct);
        SecureCookie::delete();
        return;
    }

    // The profile table might not even exist yet.
    $stmt = $PDOX->queryReturnError(
        "SELECT P.profile_id AS profile_id, P.displayname AS displayname,
            P.email as email, U.user_id as user_id
            FROM {$CFG->dbprefix}profile AS P
            LEFT JOIN {$CFG->dbprefix}lti_user AS U
            ON P.profile_id = U.profile_id AND user_sha256 = profile_sha256 AND
                P.key_id = U.key_id
            WHERE profile_sha256 = :SHA AND U.user_id = :UID LIMIT 1",
        array('SHA' => $userSHA, ":UID" => $user_id)
    );

    if ( $stmt->success === false ) return;

    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ( $row === false ) {
        error_log("Unable to load user_id=$user_id SHA=$userSHA");
        SecureCookie::delete();
        return;
    }

    $_SESSION["id"] = $row['user_id'];
    $_SESSION["email"] = $row['email'];
    $_SESSION["displayname"] = $row['displayname'];
    $_SESSION["profile_id"] = $row['profile_id'];

    error_log('Autologin:'.$row['user_id'].','.$row['displayname'].','.
        $row['email'].','.$row['profile_id']);

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

