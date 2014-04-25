<?php

require_once "lti_util.php";

function getSpinnerUrl() {
    global $CFG;
    return $CFG->staticroot . '/static/img/spinner.gif';
}

function flashMessages() {
    if ( isset($_SESSION['error']) ) {
        echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
        unset($_SESSION['error']);
    }
    if ( isset($_SESSION['success']) ) {
        echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
        unset($_SESSION['success']);
    }
}

function dumpTable($stmt, $view=false) {
    if ( $view !== false ) {
        if ( strpos($view, '?') !== false ) {
            $view .= '&';
        } else {
            $view .= '?';
        }
    }
    echo('<table border="1">');
    $first = true;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        if ( $first ) {
            echo("\n<tr>\n");
            foreach($row as $k => $v ) {
                if ( $view !== false && strpos($k, "_id") !== false && is_numeric($v) ) {
                    continue;
                }
                echo("<th>".htmlent_utf8($k)."</th>\n");
            }
            echo("</tr>\n");
        }
        $first = false;

        $link_name = false;
        echo("\n<tr>\n");
        foreach($row as $k => $v ) {
            if ( $view !== false && strpos($k, "_id") !== false && is_numeric($v) ) {
                $link_name = $k;
                $link_val = $v;
                continue;
            }
            echo("<td>");
            if ( $link_name !== false ) {
                echo('<a href="'.$view.$link_name."=".$link_val.'">');
                if ( strlen($v) < 1 ) $v = $link_name.':'.$link_val;
            }
            echo(htmlent_utf8($v));
            if ( $link_name !== false ) {
                echo('</a>');
            }
            $link_name = false;
            echo("</td>\n");
        }
        echo("</tr>\n");
    }
    echo("</table>\n");
}

function getNameAndEmail($LTI) {
    $display = '';
    if ( isset($LTI['user_displayname']) && strlen($LTI['user_displayname']) > 0 ) {
        $display = $LTI['user_displayname'];
    }
    if ( isset($LTI['user_email']) && strlen($LTI['user_email']) > 0 ) { 
        if ( strlen($display) > 0 ) {
            $display .= ' ('.$LTI['user_email'].')';
        } else {
            $display = $LTI['user_email'];
        }
    }
    $display = trim($display);
    if ( strlen($display) < 1 ) return false;
    return $display;
}

function getFirstName($displayname) {
    if ( $displayname === false ) return false;
    $pieces = explode(' ',$displayname);
    if ( count($pieces) > 0 ) return $pieces[0];
    return false;
}

function welcomeUserCourse($LTI) {
	echo("<p>Welcome");
	if ( isset($LTI['user_displayname']) ) {
		echo(" ");
		echo(htmlent_utf8($LTI['user_displayname']));
	}
	if ( isset($LTI['context_title']) ) {
		echo(" from ");
		echo(htmlent_utf8($LTI['context_title']));
	}

	if ( isInstructor($LTI) ) {
		echo(" (Instructor)");
	}
	echo("</p>\n");
}

function doCSS($context=false) {
    global $CFG;
    echo '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/static/css/default.css" />'."\n";
    if ( $context !== false ) {
        foreach ( $context->getCSS() as $css ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$css.'" />'."\n";
        }
    }
}

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

// Make sure we have the values we need in the LTI session
// This routine will not start a session if none exists.  It will
// die is there if no session_name() (PHPSESSID) cookie or 
// parameter.  No need to create any fresh sessions here.
function requireData($needed) {
    global $CFG;

    // Check to see if the session already exists.
    $sess = session_name();
    if ( ini_get('session.use_cookies') != '0' ) {
        if ( ! isset($_COOKIE[$sess]) ) {
            send403();
            dieWithErrorLog("Missing session cookie - please re-launch");
        }
    } else { // non-cookie session
        if ( isset($_POST[$sess]) || isset($_GET[$sess]) ) {
            // We tried to set a session..
        } else {
            if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                send403();
                dieWithErrorLog('Missing '.$sess.' from POST data');
            } else {
                send403();
                dieWithErrorLog('Missing '.$sess.'= on URL (Missing call to sessionize?)');
            }
        }
    }

    // Start a session if it has not been started..
    if ( session_id() == "" ) {
        session_start();  // Should reassociate
    }

    // This happens from time to time when someone closes and reopens a laptop
    // Or their computer goes to sleep and wakes back up hours later.
    // So it is just a warning - nothing much we can do except tell them.
	if ( !isset($_SESSION['lti']) ) {
        // $debug = safeVarDump($_SESSION);
        // error_log($debug);
        send403(); error_log('Session expired - please re-launch '.session_id()); 
        die('Session expired - please re-launch'); // with error_log
	}

    // Check the referrer...
    $trusted = checkReferer() || checkCSRF();

    // Check to see if we switched browsers or IP addresses
    // TODO: Change these to warnings once we get more data
    if ( (!$trusted) && isset($_SESSION['HTTP_USER_AGENT']) ) {
        if ( (!isset($_SERVER['HTTP_USER_AGENT'])) ||
            $_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT'] ) {
            send403();
            dieWithErrorLog("Session has expired", " ".session_id()." HTTP_USER_AGENT ".
                $_SESSION['HTTP_USER_AGENT'].' ::: '.
                isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Empty user agent',
            'die:');
        }
    }

    // We only check the first three octets as some systems wander throught the addresses on
    // class C - Perhaps it is even NAT - who knows - but we forgive those on the same Class C
    if ( (!$trusted) &&  isset($_SESSION['REMOTE_ADDR']) && isset($_SERVER['REMOTE_ADDR']) ) {
        $sess_pieces = explode('.',$_SESSION['REMOTE_ADDR']);
        $serv_pieces = explode('.',$_SERVER['REMOTE_ADDR']);
        if ( count($sess_pieces) == 4 && count($serv_pieces) == 4 ) {
            if ( $sess_pieces[0] != $serv_pieces[0] || $sess_pieces[1] != $serv_pieces[1] ||
                $sess_pieces[2] != $serv_pieces[2] ) {
                send403();
                dieWithErrorLog('Session address has expired', " ".session_id()." REMOTE_ADDR ".
                    $_SESSION['REMOTE_ADDR'].' '.$_SERVER['REMOTE_ADDR'], 'die:');
            }
        }
    }

	$LTI = $_SESSION['lti'];
	if ( is_string($needed) && ! isset($LTI[$needed]) ) {
		dieWithErrorLog("This tool requires an LTI launch parameter:".$needed);
	}
	if ( is_array($needed) ) {
		foreach ( $needed as $feature ) {
			if ( isset($LTI[$feature]) ) continue;
			dieWithErrorLog("This tool requires an LTI launch parameter:".$feature);
		}
	}

    // Check to see if the session needs to be extended due to this request
    checkHeartBeat();

    // Restart the number of continuous heartbeats
    $_SESSION['HEARTBEAT_COUNT'] = 0;

    return $LTI;
}

function isInstructor($LTI) {
	return isset($LTI['role']) && $LTI['role'] != 0 ;
}

// TODO: deal with headers sent...
function requireLogin() {
    global $CFG;
    if ( ! isset($_SESSION['user_id']) ) {
        $_SESSION['error'] = 'Login required';
        doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function requireAdmin() {
    global $CFG;
    if ( $_SESSION['admin'] != 'yes' ) {
        $_SESSION['error'] = 'Login required';
        doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function headerContent($headCSS=false) {
    global $HEAD_CONTENT_SENT, $CFG, $RUNNING_IN_TOOL;
	global $CFG;
    if ( $HEAD_CONTENT_SENT === true ) return;
    header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo($CFG->servicename); ?></title>
    <!-- Le styles -->
    <link href="<?php echo($CFG->staticroot); ?>/static/css/custom-theme/jquery-ui-1.10.0.custom.css" rel="stylesheet">
    <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap-3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap-3.1.1/css/bootstrap-theme.min.css" rel="stylesheet">

<style> <!-- from navbar.css -->
body {
  padding-top: 20px;
  padding-bottom: 20px;
}

.navbar {
  margin-bottom: 20px;
}
</style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="<?php echo($CFG->wwwroot); ?>/static/html5shiv/html5shiv.js"></script>
      <script src="<?php echo($CFG->wwwroot); ?>/static/respond/respond.min.js"></script>
    <![endif]-->

<?php
    if ( isset($_SESSION['CSRF_TOKEN']) ) {
        echo('<script type="text/javascript">CSRF_TOKEN = "'.$_SESSION['CSRF_TOKEN'].'";</script>'."\n");
    } else {
        echo('<script type="text/javascript">CSRF_TOKEN = "TODORemoveThis";</script>'."\n");
    }
    $HEAD_CONTENT_SENT = true;
}

function startBody() {
    echo("\n</head>\n<body style=\"padding: 15px 15px 15px 15px;\">\n");
    if ( count($_POST) > 0 ) {
        $dump = safeVarDump($_POST);
        echo('<p style="color:red">Error - Unhandled POST request</p>');
        echo("\n<pre>\n");
        echo($dump);
        echo("\n</pre>\n");
        error_log($dump);
        dieWithErrorLog("Unhandled POST request");
    }
}
function footerStart() {
    global $CFG;
    echo('<script src="'.$CFG->staticroot.'/static/js/jquery-1.10.2.min.js"></script>'."\n");
?>
<script type="text/javascript">
    $.ajaxSetup({ 
        cache: false,
        headers : {
            'X-CSRF-Token' : CSRF_TOKEN
        }
    });
</script>
<?php
    echo('<script src="'.$CFG->staticroot.'/static/bootstrap-3.1.1/js/bootstrap.min.js"></script>'."\n");
	do_analytics(); 
	echo(togglePreScript());
    if ( isset($CFG->sessionlifetime) ) {
        $heartbeat = ( $CFG->sessionlifetime * 1000) / 2;
        // $heartbeat = 10000;
?>
<script type="text/javascript">
HEARTBEAT_INTERVAL = false;
function doHeartBeat() {
    window.console && console.log('Calling heartbeat to extend session');
    $.getJSON('<?php echo(sessionize($CFG->wwwroot.'/core/util/heartbeat.php')); ?>', function(data) {
        window.console && console.log(data);
        if ( data.lti || data.cookie ) {
            // No problem
        } else {
            clearInterval(HEARTBEAT_INTERVAL);
            HEARTBEAT_INTERVAL = false;
            alert('Your session has expired');
            window.location.href = "about:blank";
        }
    });
}
HEARTBEAT_INTERVAL = setInterval(doHeartBeat, <?php echo($heartbeat); ?>);
</script>
<?php
    }

    // If we are using cookieless sessions - lets see if we are the top frame
    if (  (! isset($_COOKIE[session_name()])) && ini_get('session.use_cookies') == '0' 
            && isset($_SESSION['TOP_CHECK']) && $_SESSION['TOP_CHECK'] > 0 ) {
        $_SESSION['TOP_CHECK']--;  // Only do so many times..
?>
<script type="text/javascript">
// http://www.w3schools.com/js/js_cookies.asp
function getCookie(cname)
{
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) 
    {
        var c = ca[i].trim();
        if (c.indexOf(name)==0) return c.substring(name.length,c.length);
    }
    return "";
}

function checkTopStatus() {
    topframe = 'false';
    try {
        topframe = window.self === window.top ? 'true' : 'false';
    } catch (e) {
        topframe = 'false';
    }

    window.console && console.log('Letting TSUGI know we are the top frame...');
    $.getJSON('<?php echo(sessionize($CFG->wwwroot.'/core/util/topstatus.php')); ?>&top='+topframe, function(data) {
        window.console && console.log(data);
        if ( data.cookie_name && data.cookie_value) {
            if ( getCookie(data.cookie_name).length > 0 ) {
                window.console && console.log('Cookie '+data.cookie_name+' already set.');
            } else {
                cstring = data.cookie_name+'='+data.cookie_value+'; path=/';
                window.console && console.log('Setting cookie '+cstring);
                document.cookie = cstring;
            }
            if ( getCookie(data.session_name).length > 0 ) {
                window.console && console.log('Cookie '+data.session_name+' already set.');
            } else {
                cstring = data.session_name+'='+data.cookie_value+'; path=/';
                window.console && console.log('Setting cookie '+cstring);
                document.cookie = cstring;
            }
        }
    });
}
$(document).ready( function () {
    setTimeout(checkTopStatus,3000);  // Let the page settle after ready
});
</script>


<?php
    }
}

function footerEnd() {
    echo("\n</body>\n</html>\n");
}

function footerContent($onload=false) {
    global $CFG;
    footerStart();
    if ( $onload !== false ) {
        echo("\n".$onload."\n");
    }
    footerEnd();
}

function do_analytics() {
    global $CFG;
    if ( $CFG->analytics_key ) { ?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo($CFG->analytics_key); ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    // ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';

    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

<?php
        if ( isset($_SESSION) && isset($_SESSION['lti']) ) {
            if ( isset($_SESSION['lti']['key_key']) ) {
                echo("_gaq.push(['_setCustomVar', 1, 'consumer_key', '".$_SESSION['lti']['key_key']."', 2]);\n");
            }
            if ( isset($_SESSION['lti']['context_id']) ) {
                echo("_gaq.push(['_setCustomVar', 2, 'context_id', '".$_SESSION['lti']['context_id']."', 2]);\n");
            }
            if ( isset($_SESSION['lti']['context_title']) ) {
                echo("_gaq.push(['_setCustomVar', 3, 'context_title', '".$_SESSION['lti']['context_title']."', 2]);\n");
            }
        }
        echo("</script>\n");
    }  // if analytics is on...
}

// Gets an absolute static path to the specified file
function getLocalStatic($file) {
    global $CFG;
    $path = getPwd($file);
    return $CFG->staticroot . "/" . $path;
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

function addSession($location) {
    if ( stripos($location, '&'.session_name().'=') > 0 ||
         stripos($location, '?'.session_name().'=') > 0 ) return $location;

    if ( strpos($location,'?') > 0 ) {
       $location = $location . '&';
    } else {
       $location = $location . '?';
    }
    $location = $location . session_name() . '=' . session_id();
    return $location;
}

// Forward to a local URL, adding session if necessary - not that hrefs get altered appropriately 
// by PHP itself
function doRedirect($location) {
    if ( headers_sent() ) {
        echo('<a href="'.htmlentities($location).'">Continue</a>'."\n");
    } else {
        if ( ini_get('session.use_cookies') == 0 ) {
            $location = addSession($location);
        }
        header("Location: $location");
    }
}

// Debugging utilities
global $DEBUG_STRING;
$DEBUG_STRING='';

function debugClear() {
    global $DEBUG_STRING;
    unset($_SESSION['__zzz_debug']);
}

function debugLog($text,$mixed=false) {
    global $DEBUG_STRING;
    $sess = (strlen(session_id()) > 0 );
    if ( $sess && isset($_SESSION['__zzz_debug']) ) {
        if ( strlen($DEBUG_STRING) > 0 && strlen($_SESSION['__zzz_debug']) > 0) {
            $_SESSION['__zzz_debug'] = $_SESSION['__zzz_debug'] ."\n" . $DEBUG_STRING;
        } else if ( strlen($DEBUG_STRING) > 0 ) {
            $_SESSION['__zzz_debug'] = $DEBUG_STRING;
        }
        $DEBUG_STRING = $_SESSION['__zzz_debug'];
    }
    if ( strlen($text) > 0 ) {
        if ( strlen($DEBUG_STRING) > 0 ) {
            if ( substr($DEBUG_STRING,-1) != "\n") $DEBUG_STRING .= "\n";
        }
        $DEBUG_STRING .= $text;
    }
    if ( $mixed !== false ) {
        if ( strlen($DEBUG_STRING) > 0 ) {
            if ( substr($DEBUG_STRING,-1) != "\n") $DEBUG_STRING .= "\n";
        }
        if ( $mixed !== $_SESSION ) {
            $DEBUG_STRING .= print_r($mixed, TRUE);
        } else { 
            $tmp = $mixed;
            unset($tmp['__zzz_debug']);
            $DEBUG_STRING .= print_r($tmp, TRUE);
        }
    }
    if ( $sess ) { // Move debug to session.
        $_SESSION['__zzz_debug'] = $DEBUG_STRING;
        $DEBUG_STRING = '';
        // echo("<br/>=== LOG $text ====<br/>".$_SESSION['__zzz_debug']."<br/>\n");flush();
    }
}

// Calling this clears debug buffer...
function debugDump() {
    global $DEBUG_STRING;
    $retval = '';
    $sess = (strlen(session_id()) > 0 );
    if ( $sess ) { 
        // echo("<br/>=== DUMP ====<br/>".$_SESSION['__zzz_debug']."<br/>\n");flush();
        if (strlen($_SESSION['__zzz_debug']) > 0) {
            $retval = $_SESSION['__zzz_debug'];
            unset($_SESSION['__zzz_debug']);
        }
    }
    if ( strlen($retval) > 0 && strlen($DEBUG_STRING) > 0) {
        $retval .= "\n";
    }   
    if (strlen($DEBUG_STRING) > 0) {
        $retval .= $DEBUG_STRING;
        $DEBUG_STRING = '';
    }
    return $retval;
}

function dumpPost() {
        print "<pre>\n";
        print "Raw POST Parameters:\n\n";
        ksort($_POST);
        foreach($_POST as $key => $value ) {
            if (get_magic_quotes_gpc()) $value = stripslashes($value);
            print "$key=$value (".mb_detect_encoding($value).")\n";
        }
        print "</pre>";
}

function json_indent($json) {
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

function header_json() {
    header('Content-type: application/json');
    noCacheHeader();
}

function lmsDie($message=false) {
    global $CFG, $DEBUG_STRING;
    if($message !== false) echo($message);
    if ( $CFG->development === TRUE ) {
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
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit);
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

function getCustom($varname) {
	if ( isset($_SESSION['lti_post']) && 
            isset($_SESSION['lti_post']['custom_'.$varname]) ) {
        return $_SESSION['lti_post']['custom_'.$varname];
    }
    return false;
}

function doneButton() {
    $url = false;
	if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
        $url = $_SESSION['lti_post']['custom_done'];
    } else if ( isset($_GET["done"]) ) {
        $url = $_GET['done'];
    }
    if ( $url === false ) return;

    if ( $url == "_close" ) {
        echo("<button onclick=\"window.close();\" type=\"button\">Done</button>\n");
    } else if ( strpos($url, "http") !== false ) {
        echo("<button onclick=\"window.location='$url';\" type=\"button\">Done</button>\n");
    } else {
        echo("<button onclick=\"window.location='".sessionize($url)."';\" type=\"button\">Done</button>\n");
    }
}

function doneBootstrap($text="Done") {
    $url = false;
	if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
        $url = $_SESSION['lti_post']['custom_done'];
    } else if ( isset($_GET["done"]) ) {
        $url = $_GET['done'];
    }
    if ( $url === false ) return;

    $button = "btn-success";
    if ( $text == "Cancel" ) $button = "btn-warning";

    if ( $url == "_close" ) {
        echo("<a href=\"#\" onclick=\"window.close();\" class=\"btn ".$button."\">".$text."</a>\n");
    } else {
        echo("<a href==\"$url\"  class=\"btn ".$button."\">".$text."</button>\n");
    }
}

function togglePre($title, $html) {
    global $div_id;
    $div_id = $div_id + 1;
    echo('<strong>'.htmlent_utf8($title));
    echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">Toggle</a>)</strong>'."\n");
    echo(' ('.strlen($html).' characters)'."\n");
    echo('<pre id="'.$div_id.'" style="display:none; border: solid 1px">'."\n");
    echo(htmlent_utf8($html));
    echo("</pre><br/>\n");
}

function togglePreScript() {
return '<script language="javascript"> 
function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
} 
</script>';
}

// Looks up a result for a potentially different user_id so we make
// sure they are in the smame key/ context / link as the current user
// hence the complex query to make sure we don't cross silos
function lookupResult($pdo, $LTI, $user_id) {
    global $CFG;
    $stmt = pdoQueryDie($pdo,
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
function safeVarDump($x) {
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

function json_error($message,$detail="") {
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode(array("error" => $message, "detail" => $detail)));
}

function json_output($json_data) {
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($json_data));
}

// No Buffering
function noBuffer() {
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
}

function cacheCheck($cacheloc, $cachekey)
{
    $cacheloc = "cache_" . $cacheloc;
    if ( isset($_SESSION[$cacheloc]) ) {
        $cache_row = $_SESSION[$cacheloc];
        if ( $cache_row[0] == $cachekey ) {
            // error_log("Cache hit $cacheloc");
            return $cache_row[1];
        }
        unset($_SESSION[$cacheloc]);
    }
    return false;
}

// Don't cache the non-existence of something
function cacheSet($cacheloc, $cachekey, $cacheval)
{
    $cacheloc = "cache_" . $cacheloc;
    if ( $cacheval === null || $cacheval === false ) {
        unset($_SESSION[$cacheloc]);
        return;
    }
    $_SESSION[$cacheloc] = array($cachekey, $cacheval);
}

function cacheClear($cacheloc)
{
    $cacheloc = "cache_" . $cacheloc;
    if ( isset($_SESSION[$cacheloc]) ) {
        // error_log("Cache clear $cacheloc");
    }
    unset($_SESSION[$cacheloc]);
}

function loadUserInfo($pdo, $user_id)
{
    global $CFG;
    $cacheloc = 'lti_user';
    $row = cacheCheck($cacheloc, $user_id);
    if ( $row != false ) return $row;
    $stmt = pdoQueryDie($pdo,
        "SELECT displayname, email, user_key FROM {$CFG->dbprefix}lti_user
            WHERE user_id = :UID",
        array(":UID" => $user_id)
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ( strlen($row['displayname']) < 1 && strlen($row['user_key']) > 0 ) {
        $row['displayname'] = 'user_key:'.substr($row['user_key'],0,25);
    }
    cacheSet($cacheloc, $user_id, $row);
    return $row;
}

function loadLinkInfo($pdo, $link_id)
{
    global $CFG;
    $LTI = requireData(array('context_id'));

    $cacheloc = 'lti_link';
    $row = cacheCheck($cacheloc, $link_id);
    if ( $row != false ) return $row;
    $stmt = pdoQueryDie($pdo,
        "SELECT title FROM {$CFG->dbprefix}lti_link 
            WHERE link_id = :LID AND context_id = :CID",
        array(":LID" => $link_id, ":CID" => $LTI['context_id'])
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    cacheSet($cacheloc, $link_id, $row);
    return $row;
}

require_once("crypt/aes.class.php"); 
require_once("crypt/aesctr.class.php");

/*
  // initialise password & plaintesxt if not set in post array (shouldn't need stripslashes if magic_quotes is off)
  $pw = 'L0ck it up saf3';
  $pt = 'pssst ... đon’t tell anyøne!';
  $encr = AesCtr::encrypt($pt, $pw, 256) ;
  $decr = AesCtr::decrypt($encr, $pw, 256);
  echo("E: ".$encr."\n");
  echo("D: ".$decr."\n");
*/

function create_secure_cookie($id,$guid,$debug=false) {
    global $CFG;
    $pt = $CFG->cookiepad.'::'.$id.'::'.$guid;
    if ( $debug ) echo("PT1: $pt\n");
    $ct = AesCtr::encrypt($pt, $CFG->cookiesecret, 256) ;
    return $ct;
}

function extract_secure_cookie($encr,$debug=false) {
    global $CFG;
    $pt = AesCtr::decrypt($encr, $CFG->cookiesecret, 256) ;
    if ( $debug ) echo("PT2: $pt\n");
    $pieces = explode('::',$pt);
    if ( count($pieces) != 3 ) return false;
    if ( $pieces[0] != $CFG->cookiepad ) return false;
    return Array($pieces[1], $pieces[2]);
}

// We also session_unset - because something is not right
// See: http://php.net/manual/en/function.setcookie.php
function delete_secure_cookie() {
    global $CFG;
    setcookie($CFG->cookiename,'',time() - 100); // Expire 100 seconds ago
    session_unset();
}

function test_secure_cookie() {
    $id = 1;
    $guid = 'xyzzy';
    $ct = create_secure_cookie($id,$guid,true);
    echo($ct."\n");
    $pieces = extract_secure_cookie($ct,true);
    if ( $pieces === false ) echo("PARSE FAILURE\n");
    var_dump($pieces);
    if ( $pieces[0] == $id && $pieces[1] == $guid ) {
        echo("Success\n");
    } else {
        echo("FAILURE\n");
    }
}

// test_secure_cookie();

function do_form($values, $override=Array()) {
    foreach (array_merge($values,$override) as $key => $value) {
        if ( $value === false ) continue;
        if ( is_string($value) && strlen($value) < 1 ) continue;
        if ( is_int($value) && $value === 0 ) continue;
        echo('<input type="hidden" name="'.htmlent_utf8($key).
             '" value="'.htmlent_utf8($value).'">'."\n");
    }
}

function do_url($values, $override=Array()) {
    $retval = '';
    foreach (array_merge($values,$override) as $key => $value) {
        if ( $value === false ) continue;
        if ( is_string($value) && strlen($value) < 1 ) continue;
        if ( is_int($value) && $value === 0 ) continue;
        if ( strlen($retval) > 0 ) $retval .= '&';
        $retval .= urlencode($key) . "=" . urlencode($value);
    }
    return $retval;
}

// Function to lookup and match things like R.updated_at to updated_at
function matchColumns($colname, $columns) {
    foreach ($columns as $v) {
        if ( $colname == $v ) return true;
        if ( strlen($v) < 2 ) continue;
        if ( substr($v,1,1) != '.' ) continue;
        if ( substr($v,2) == $colname ) return true;
    }
    return false;
}

$DEFAULT_PAGE_LENGTH = 20;  // Setting this to 2 is good for debugging

// Requires the keyword WHERE to be upper case - if a query has more than one WHERE clause
// they should all be lower case except the one where the LIKE clauses will be added.

// We will add the ORDER BY clause at the end using the first field in $orderfields
// is there is not a 'order_by' in $params

// Normally $params should just default to $_GET
function pagedPDOQuery($sql, &$queryvalues, $searchfields=array(), $orderfields=false, $params=false) {
    global $DEFAULT_PAGE_LENGTH;
    if ( $params == false ) $params = $_GET;
    if ( $orderfields == false ) $orderfields = $searchfields;

    $searchtext = '';
    if ( count($searchfields) > 0 && isset($params['search_text']) ) {
        for($i=0; $i < count($searchfields); $i++ ) {
            if ( $i > 0 ) $searchtext .= " OR ";
            $searchtext .= $searchfields[$i]." LIKE :SEARCH".$i;
            $queryvalues[':SEARCH'.$i] = '%'.$params['search_text'].'%';
        }
    }

    $ordertext = '';
    if ( isset($params['order_by']) && matchColumns($params['order_by'], $orderfields) ) { 
        $ordertext = $params['order_by']." ";
        if ( isset($params['desc']) && $params['desc'] == 1) {
            $ordertext .= "DESC ";
        }
    } else if ( count($orderfields) > 0 ) {
        $ordertext = $orderfields[0]." ";
    }

    $page_start = isset($params['page_start']) ? $params['page_start']+0 : 0;
    if ( $page_start < 0 ) $page_start = 0;
    $page_length = isset($params['page_length']) ? $params['page_length']+0 : $DEFAULT_PAGE_LENGTH;
    if ( $page_length < 0 ) $page_length = 0;

    $desc = '';
    if ( isset($params['desc']) ) { 
        $desc = $params['desc']+0;
    }

    $limittext = '';
    if ( $page_start < 1 ) {
        $limittext = "".($page_length+1);
    } else {
        $limittext = "".$page_start.", ".($page_length+1);
    }

    // Fix up the SQL Query
    $newsql = $sql;
    if ( strlen($searchtext) > 0 ) {
        $newsql = str_replace("WHERE", "WHERE ( ".$searchtext." ) AND ", $newsql);
    }
    if ( strlen($ordertext) > 0 ) {
        $newsql .= "\nORDER BY ".$ordertext." ";
    }
    if ( strlen($limittext) > 0 ) {
        $newsql .= "\nLIMIT ".$limittext." ";
    }
    return $newsql . "\n";
}

function pagedPDOTable($rows, $searchfields=array(), $orderfields=false, $view=false, $params=false) {
    global $DEFAULT_PAGE_LENGTH;
    if ( $params === false ) $params = $_GET;
    if ( $orderfields === false ) $orderfields = $searchfields;

    $page_start = isset($params['page_start']) ? $params['page_start']+0 : 0;
    if ( $page_start < 0 ) $page_start = 0;
    $page_length = isset($params['page_length']) ? $params['page_length']+0 : $DEFAULT_PAGE_LENGTH;
    if ( $page_length < 0 ) $page_length = 0;

    $search = '';
    if ( isset($params['search_text']) ) {
        $search = $params['search_text'];
    }

    $count = count($rows);
    $have_more = false;
    if ( $count > $page_length ) {
        $have_more = true;
        $count = $page_length;
    }

    echo('<div style="float:right">');
    if ( $page_start > 0 ) {
        echo('<form style="display: inline">');
        echo('<input type="submit" value="Back" class="btn btn-default">');
        $page_back = $page_start - $page_length;
        if ( $page_back < 0 ) $page_back = 0;
        do_form($params,Array('page_start' => $page_back));
        echo("</form>\n");
    }
    if ( $have_more ) {
        echo('<form style="display: inline">');
        echo('<input type="submit" value="Next" class="btn btn-default"> ');
        $page_next = $page_start + $page_length;
        do_form($params,Array('page_start' => $page_next));
        echo("</form>\n");
    }
    echo("</div>\n");
    echo('<form>');
    echo('<input type="text" id="paged_search_box" value="'.htmlent_utf8($search).'" name="search_text">');
    do_form($params,Array('search_text' => false, 'page_start' => false));
?>
<input type="submit" value="Search" class="btn btn-default">
<input type="submit" value="Clear Search" class="btn btn-default"
onclick="document.getElementById('paged_search_box').value = '';"
>
</form>
<?php
    if ( $count < 1 ) {
        echo("<p>Nothing to display.</p>\n");
        return;
    }
// print_r($orderfields);
// echo("<hr>\n");
// print_r($rows[0]);
?>

<div style="padding:3px;">
<table border="1" class="table table-hover table-condensed table-responsive">
<tr>
<?php

    $first = true;
    $thispage = basename($_SERVER['PHP_SELF']);
    if ( $view === false ) $view = $thispage;
    foreach ( $rows as $row ) {
        $count--;
        if ( $count < 0 ) break;
        if ( $first ) {
            echo("\n<tr>\n");
            $desc = isset($params['desc']) ? $params['desc'] + 0 : 0;
            $order_by = isset($params['order_by']) ? $params['order_by'] : '';
            foreach($row as $k => $v ) {
                if ( strpos($k, "_") === 0 ) continue;
                if ( $view !== false && strpos($k, "_id") !== false && is_numeric($v) ) {
                    continue;
                }

                if ( ! matchColumns($k, $orderfields ) ) {
                    echo("<th>".ucwords(str_replace('_',' ',$k))."</th>\n");
                    continue;
                }

                $override = Array('order_by' => $k, 'desc' => 0, 'page_start' => false);
                $d = $desc;
                $color = "black";
                if ( $k == $order_by || $order_by == '' && $k == 'id' ) {
                    $d = ($desc + 1) % 2;
                    $override['desc'] = $d;
                    $color = $d == 1 ?  'green' : 'red';
                }
                $stuff = do_url($params,$override);
                echo('<th>');
                echo(' <a href="'.$thispage);
                if ( strlen($stuff) > 0 ) {
                    echo("?");
                    echo($stuff);
                }
                echo('" style="color: '.$color.'">');
                echo(ucwords(str_replace('_',' ',$k)));
                echo("</a></th>\n");
            }
            echo("</tr>\n");
        }

        $first = false;
        $link_name = false;
        echo("<tr>\n");
        foreach($row as $k => $v ) {
            if ( strpos($k, "_") === 0 ) continue;
            if ( $view !== false && strpos($k, "_id") !== false && is_numeric($v) ) {
                $link_name = $k;
                $link_val = $v;
                continue;
            }
            echo("<td>");
            if ( $link_name !== false ) {
                echo('<a href="'.$view.'?'.$link_name."=".$link_val.'">');
                if ( strlen($v) < 1 ) $v = $link_name.':'.$link_val;
            }
            echo(htmlent_utf8($v));
            if ( $link_name !== false ) {
                echo('</a>');
            }
            $link_name = false;
            echo("</td>\n");
        }
        echo("</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

function pagedPDO($pdo, $sql, $query_parms, $searchfields, $orderfields=false, $view=false, $params=false) {
    $newsql = pagedPDOQuery($sql, $query_parms, $searchfields, $orderfields, $params);

    //echo("<pre>\n$newsql\n</pre>\n");

    $rows = pdoAllRowsDie($pdo, $newsql, $query_parms);

    pagedPDOTable($rows, $searchfields, $orderfields, $view, $params);
}

// Check if this has a due date..
function getDueDate() {
    $retval = new stdClass();
    $retval->message = false;
    $retval->penalty = 0;
    $retval->dayspastdue = 0;
    $retval->percent = 0;
    $retval->duedate = false;
    $retval->duedatestr = false;

    $duedatestr = getCustom('due');
    if ( $duedatestr === false ) return $retval;
    $duedate = strtotime($duedatestr);

    $diff = -1;
    $penalty = false;

    date_default_timezone_set('Pacific/Honolulu'); // Lets be generous
    if ( getCustom('timezone') ) {
	    date_default_timezone_set(getCustom('timezone'));
    }

    if ( $duedate === false ) return $retval;

    //  If it is just a date - add nearly an entire day of time...
    if ( strlen($duedatestr) <= 10 ) $duedate = $duedate + 24*60*60 - 1;
    $diff = time() - $duedate;

    $retval->duedate = $duedate;
    $retval->duedatestr = $duedatestr;
    // Should be a percentage off between 0.0 and 1.0
    if ( $diff > 0 ) {
	    $penalty_time = getCustom('penalty_time') ? getCustom('penalty_time') + 0 : 24*60*60;
	    $penalty_cost = getCustom('penalty_cost') ? getCustom('penalty_cost') + 0.0 : 0.2;
	    $penalty_exact = $diff / $penalty_time;
	    $penalties = intval($penalty_exact) + 1;
        $penalty = $penalties * $penalty_cost;
	    if ( $penalty < 0 ) $penalty = 0;
	    if ( $penalty > 1 ) $penalty = 1;
	    $retval->penalty = $penalty;
	    $retval->dayspastdue = $diff / (24*60*60);
	    $retval->percent = intval($penalty * 100);
        $retval ->message = 'It is currently '.sprintf("%10.2f",$retval->dayspastdue)." days\n".
	    'past the due date ('.htmlentities($duedatestr).') so your late penalty is '.$retval->percent." percent.\n";
    }
    return $retval;
}


function compute_mail_check($identity) {
    global $CFG;
    return sha1($CFG->mailsecret . '::' . $identity);
}

function mailSend($to, $subject, $message, $id, $token) {
    global $CFG;

    if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) return;

    if ( isset($CFG->maileol) && isset($CFG->wwwroot) && isset($CFG->maildomain) ) {
        // All good
    } else {
        dieWithErrorLog("Incomplete mail configuration in mailSend");
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


// No trailer
