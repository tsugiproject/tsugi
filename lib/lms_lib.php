<?php

require_once("vendor/Tsugi/Core/Cache.php");
require_once("vendor/Tsugi/Core/Debug.php");
require_once('vendor/Tsugi/Util/Net.php');
require_once("vendor/Tsugi/Crypt/Aes.php");
require_once("vendor/Tsugi/Crypt/AesCtr.php");

require_once("oauth.classes.php");
require_once("vendor/Tsugi/Util/LTI.php");
require_once("vendor/Tsugi/Util/Caliper.php");

require_once("vendor/Tsugi/Core/User.php");
require_once("vendor/Tsugi/Core/Context.php");
require_once("vendor/Tsugi/Core/Link.php");
require_once("vendor/Tsugi/Core/LTIX.php");
require_once("vendor/Tsugi/Core/Settings.php");
require_once("vendor/Tsugi/UI/CrudForm.php");
require_once("vendor/Tsugi/UI/Table.php");
require_once("vendor/Tsugi/UI/Output.php");
require_once("vendor/Tsugi/UI/SettingsForm.php");

require_once("vendor/Tsugi/Google/GoogleLogin.php");
require_once("vendor/Tsugi/Google/JWT.php");

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// See if we need to extend our session (heartbeat)
// http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
function checkHeartBeat() {
    if ( session_id() == "" ) return;  // This should not start the session

    if ( isset($CFG->sessionlifetime) ) {
        if (isset($_SESSION['LAST_ACTIVITY']) ) {
            $heartbeat = $CFG->sessionlifetime/4;
            $ellapsed = time() - $_SESSION['LAST_ACTIVITY'];
            if ( $ellapsed > $heartbeat ) {
                $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
                // TODO: Remove this after verification
                $filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                error_log("Heartbeat ".session_id().' '.$ellapsed.' '.$filename);
            }
        } else {
            $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
        }
    }
}

function send403() {
    header("HTTP/1.1 403 Forbidden");
}

// Returns true for a good referrer and false if we could not verify it
function checkReferer() {
    global $CFG;
    return isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$CFG->wwwroot) === 0 ;
}

// Returns true for a good CSRF and false if we could not verify it
function checkCSRF() {
    global $CFG;
    if ( ! isset($_SESSION['CSRF_TOKEN']) ) return false;
    $token = $_SESSION['CSRF_TOKEN'];
    if ( isset($_POST['CSRF_TOKEN']) && $token == $_POST['CSRF_TOKEN'] ) return true;
    $headers = array_change_key_case(apache_request_headers());
    if ( isset($headers['x-csrf-token']) && $token == $headers['x-csrf-token'] ) return true;
    if ( isset($headers['x-csrftoken']) && $token == $headers['x-csrftoken'] ) return true;
    return false;
}

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

// Gets an absolute static path to the specified file
function getLocalStatic($file) {
    global $CFG;
    $path = getPwd($file);
    return $CFG->staticroot . "/" . $path;
}

function getCurrentFile($file) {
    global $CFG;
    $root = $CFG->dirroot;
    $path = realpath($file);
    if ( strlen($path) < strlen($root) ) return false;
    // The root must be the prefix of path
    if ( strpos($path, $root) !== 0 ) return false;
    $retval = substr($path, strlen($root));
    return $retval;
}

function getScriptPath() {
    global $CFG;
    $path = getScriptPathFull();
    if ( strpos($path, $CFG->dirroot) === 0 )  { 
        $x = substr($path, strlen($CFG->dirroot)+1 ) ;
        return $x;
    } else {
        return "";
    }
}

function getScriptPathFull() {
    if ( ! isset( $_SERVER['SCRIPT_FILENAME']) ) return false;
    $script = $_SERVER['SCRIPT_FILENAME'];
    $path = dirname($script);
    return $path;
}

// Get the foldername of the currently called script
// ["SCRIPT_FILENAME"]=> string(52) "/Applications/MAMP/htdocs/tsugi/mod/attend/index.php"
// This function will return "attend"
function getScriptFolder() {
    $path = getScriptPathFull();
    if ( $path === false ) return false;
    $pieces = explode(DIRECTORY_SEPARATOR, $path);
    if ( count($pieces) < 1 ) return false;
    return $pieces[count($pieces)-1];
}

function getCurrentFileUrl($file) {
    global $CFG;
    return $CFG->wwwroot.getCurrentFile($file);
}

function getLoginUrl() {
    global $CFG;
    return $CFG->wwwroot.'/login.php';
}

function getPwd($file) {
    global $CFG;
    $root = $CFG->dirroot;
    $path = realpath(dirname($file));
    $root .= '/'; // Add the trailing slash
    if ( strlen($path) < strlen($root) ) return false;
    // The root must be the prefix of path
    if ( strpos($path, $root) !== 0 ) return false;
    $retval = substr($path, strlen($root));
    return $retval;
}

function getUrlFull($file) {
    global $CFG;
    $path = getPwd($file);
    return $CFG->wwwroot . "/" . $path;
}

function jsonIndent($json) {
    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    $json = str_replace('\/', '/',$json);
    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        // If this character is the end of an element,
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }
    return $result;
}

// http://stackoverflow.com/questions/49547/making-sure-a-web-page-is-not-cached-across-all-browsers
// http://www.php.net/manual/en/function.header.php
function noCacheHeader() {
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    header('Pragma: no-cache'); // HTTP 1.0.
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past - proxies
}

function headerJson() {
    header('Content-type: application/json');
    noCacheHeader();
}

function lmsDie($message=false) {
    global $CFG, $DEBUG_STRING;
    if($message !== false) echo($message);
    if ( $CFG->DEVELOPER === TRUE ) {
        if ( strlen($DEBUG_STRING) > 0 ) {
            echo("\n<pre>\n");
            echo(htmlentities($DEBUG_STRING));
            echo("\n</pre>\n");
        }
    }
    die();  // lmsDie
}

// http://stackoverflow.com/questions/2840755/how-to-determine-the-max-file-upload-limit-in-php
// http://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
/* See also the .htaccess file.   Many MySQL servers are configured to have a max size of a
   blob as 1MB.  if you change the .htaccess you need to change the mysql configuration as well.
   this may not be possible on a low-cst provider.  */

function maxUpload() {
    $maxUpload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($maxUpload, $max_post, $memory_limit);
    return $upload_mb;
}

function findTools($dir, &$retval, $filename="index.php") {
    if ( is_dir($dir) ) {
        if ($dh = opendir($dir)) {
            while (($sub = readdir($dh)) !== false) {
                if ( strpos($sub, ".") === 0 ) continue;
                $path = $dir . '/' . $sub;
                if ( ! is_dir($path) ) continue;
                if ( $sh = opendir($path)) {
                    while (($file = readdir($sh)) !== false) {
                        if ( $file == $filename ) {
                            $retval[] = $path  ."/" . $file;
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

function findFiles($filename="index.php", $reldir=false) {
    global $CFG;
    $files = array();
    foreach ( $CFG->tool_folders as $dir ) {
        if ( $reldir !== false ) $dir = $reldir . $dir;
        if ( is_dir($dir) ) {
            if ($dh = opendir($dir)) {
                while (($sub = readdir($dh)) !== false) {
                    if ( strpos($sub, ".") === 0 ) continue;
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

// Clean out the array of 'secret' keys
function safe_var_dump($x) {
        ob_start();
        if ( isset($x['secret']) ) $x['secret'] = MD5($x['secret']);
        if ( is_array($x) ) foreach ( $x as &$v ) {
            if ( is_array($v) && isset($v['secret']) ) $v['secret'] = MD5($v['secret']);
        }
        var_dump($x);
        $result = ob_get_clean();
        return $result;
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

function jsonError($message,$detail="") {
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode(array("error" => $message, "detail" => $detail)));
}

function jsonOutput($json_data) {
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($json_data));
}

// No Buffering
function noBuffer() {
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
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

/*
  // initialise password & plaintesxt if not set in post array (shouldn't need stripslashes if magic_quotes is off)
  $pw = 'L0ck it up saf3';
  $pt = 'pssst ... đon’t tell anyøne!';
  $encr = AesCtr::encrypt($pt, $pw, 256) ;
  $decr = AesCtr::decrypt($encr, $pw, 256);
  echo("E: ".$encr."\n");
  echo("D: ".$decr."\n");
*/

function createSecureCookie($id,$guid,$debug=false) {
    global $CFG;
    $pt = $CFG->cookiepad.'::'.$id.'::'.$guid;
    if ( $debug ) echo("PT1: $pt\n");
    $ct = Tsugi\Crypt\AesCtr::encrypt($pt, $CFG->cookiesecret, 256) ;
    return $ct;
}

function extractSecureCookie($encr,$debug=false) {
    global $CFG;
    $pt = Tsugi\Crypt\AesCtr::decrypt($encr, $CFG->cookiesecret, 256) ;
    if ( $debug ) echo("PT2: $pt\n");
    $pieces = explode('::',$pt);
    if ( count($pieces) != 3 ) return false;
    if ( $pieces[0] != $CFG->cookiepad ) return false;
    return Array($pieces[1], $pieces[2]);
}

// We also session_unset - because something is not right
// See: http://php.net/manual/en/function.setcookie.php
function deleteSecureCookie() {
    global $CFG;
    setcookie($CFG->cookiename,'',time() - 100); // Expire 100 seconds ago
    session_unset();
}

function testSecureCookie() {
    $id = 1;
    $guid = 'xyzzy';
    $ct = createSecureCookie($id,$guid,true);
    echo($ct."\n");
    $pieces = extractSecureCookie($ct,true);
    if ( $pieces === false ) echo("PARSE FAILURE\n");
    var_dump($pieces);
    if ( $pieces[0] == $id && $pieces[1] == $guid ) {
        echo("Success\n");
    } else {
        echo("FAILURE\n");
    }
}

// testSecureCookie();

// We have a user - set their secure cookie
function setSecureCookie($user_id, $userSHA) {
    global $CFG;
    $ct = createSecureCookie($user_id,$userSHA);
    setcookie($CFG->cookiename,$ct,time() + (86400 * 45)); // 86400 = 1 day
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
    $pieces = extractSecureCookie($ct);
    if ( $pieces === false ) {
        error_log('Decrypt fail:'.$ct);
        deleteSecureCookie();
        return;
    }

    // Convert to an integer and check valid
    $user_id = $pieces[0] + 0;
    $userSHA = $pieces[1];
    if ( $user_id < 1 ) {
        $user_id = false;
        $pieces = false;
        error_log('Decrypt bad ID:'.$pieces[0].','.$ct);
        deleteSecureCookie();
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
        deleteSecureCookie();
        return;
    }

    $_SESSION["id"] = $row['user_id'];
    $_SESSION["email"] = $row['email'];
    $_SESSION["displayname"] = $row['displayname'];
    $_SESSION["profile_id"] = $row['profile_id'];

    error_log('Autologin:'.$row['user_id'].','.$row['displayname'].','.
        $row['email'].','.$row['profile_id']);

}

function computeMailCheck($identity) {
    global $CFG;
    return sha1($CFG->mailsecret . '::' . $identity);
}

function mailSend($to, $subject, $message, $id, $token) {
    global $CFG;

    if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) return;

    if ( isset($CFG->maileol) && isset($CFG->wwwroot) && isset($CFG->maildomain) ) {
        // All good
    } else {
        die_with_error_log("Incomplete mail configuration in mailSend");
    }

    if ( strlen($to) < 1 || strlen($subject) < 1 || strlen($id) < 1 || strlen($token) < 1 ) return false;

    $EOL = $CFG->maileol;
    $maildomain = $CFG->maildomain;
    $manage = $CFG->wwwroot . "/profile.php";
    $unsubscribe_url = $CFG->wwwroot . "/unsubscribe.php?id=$id&token=$token";
    $msg = $message;
    if ( substr($msg,-1) != "\n" ) $msg .= "\n";
    // $msg .= "\nYou can manage your mail preferences at $manage \n";
    // TODO: Make unsubscribe work

    // echo $msg;

    $headers = "From: no-reply@$maildomain" . $EOL .
        "Return-Path: <bounced-$id-$token@$maildomain>" . $EOL .
        "List-Unsubscribe: <$unsubscribe_url>" . $EOL .
        'X-Mailer: PHP/' . phpversion();

    error_log("Mail to: $to $subject");
    // echo $headers;
    return mail($to,$subject,$msg,$headers);
}

$OUTPUT = new \Tsugi\UI\Output();

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

// No trailer

