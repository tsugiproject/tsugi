<?php

require_once "lti_util.php";

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

// Make sure we have the values we need in the LTI session
// and return the LMS Data
function requireData($needed) {
	if ( !isset($_SESSION['lti']) ) {
		die('This tool needs to be launched using LTI');
	}
	$LTI = $_SESSION['lti'];
	if ( is_string($needed) && ! isset($LTI[$needed]) ) {
		die("This tool requires ".$needed);
	}
	if ( is_array($needed) ) {
		foreach ( $needed as $feature ) {
			if ( isset($LTI[$feature]) ) continue;
			die("This tool requires ".$feature);
		}
	}
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
    <link href="<?php echo($CFG->bootstrap); ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo($CFG->bootstrap); ?>/css/bootstrap-theme.min.css" rel="stylesheet">

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
      <script src="<? echo($CFG->wwwroot); ?>/static/html5shiv/html5shiv.js"></script>
      <script src="<? echo($CFG->wwwroot); ?>/static/respond/respond.min.js"></script>
    <![endif]-->

<?php
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
        error_log("Unhandled POST request");
        error_log($dump);
        die();
    }
}
function footerStart() {
    global $CFG;
    echo('<script src="'.$CFG->staticroot.'/static/js/jquery-1.10.2.min.js"></script>'."\n");
    echo('<script src="'.$CFG->bootstrap.'/js/bootstrap.min.js"></script>'."\n");
	do_analytics(); 
	echo(togglePreScript());
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
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
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

function header_json() {
    header('Content-type: application/json');
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
    die();
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

function sendGrade($grade, $verbose=true, $pdo=false, $result=false) {
	if ( ! isset($_SESSION['lti']) || ! isset($_SESSION['lti']['sourcedid']) ) {
        return "Session not set up for grade return";
    }
    try {
        if ( $result === false ) $result = $_SESSION['lti'];
        return sendGradeInternal($grade, null, null, $verbose, $pdo, $result);
	} catch(Exception $e) {
		$msg = "Grade Exception: ".$e->getMessage();
		error_log($msg);
        return $msg;
    } 
}

function sendGradeDetail($grade, $note=null, $json=null, $verbose, $pdo=false, $result=false) {
	if ( ! isset($_SESSION['lti']) || ! isset($_SESSION['lti']['sourcedid']) ) {
        return "Session not set up for grade return";
    }
    try {
        if ( $result === false ) $result = $_SESSION['lti'];
        return sendGradeInternal($grade, $note, $json, false, $pdo, $result);
	} catch(Exception $e) {
		$msg = "Grade Exception: ".$e->getMessage();
		error_log($msg);
        return $msg;
    } 
}

function sendGradeInternal($grade, $note, $json, $verbose, $pdo,  $result) {
    global $CFG;
    global $LastPOXGradeResponse;
    $LastPOXGradeResponse = false;;
	$lti = $_SESSION['lti'];
	if ( ! ( isset($lti['service']) && isset($lti['sourcedid']) &&
		isset($lti['key_key']) && isset($lti['secret']) && 
        array_key_exists('grade', $lti) ) ) {
		error_log('Session is missing required data');
        $debug = safeVarDump($lti);
        error_log($debug);
        return "Missing required session data";
    }

	if ( ! ( isset($result['sourcedid']) && isset($result['result_id']) &&
        array_key_exists('grade', $result) ) ) {
		error_log('Result is missing required data');
        $debug = safeVarDump($result);
        error_log($debug);
        return "Missing required result data";
    }

    // Check if the grade was already sent...
    if ( isset($result['grade']) && $grade == $result['grade'] ) {
        error_log("Grade result_id=".$result['result_id']." grade= $grade already sent...");
        $status = true;
    } else {

	    $method="POST";
	    $content_type = "application/xml";
        $sourcedid = $result['sourcedid'];
	    $sourcedid = htmlspecialchars($sourcedid);
    
	    $operation = 'replaceResultRequest';
	    $postBody = str_replace(
		    array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
		    array($sourcedid, $grade.'', 'replaceResultRequest', uniqid()),
		    getPOXGradeRequest());
    
	    if ( $verbose ) line_out('Sending grade to '.$lti['service']);

	    if ( $verbose ) togglePre("Grade API Request (debug)",$postBody);
        if ( $verbose ) flush();

	    $response = sendOAuthBodyPOST($method, $lti['service'], $lti['key_key'], $lti['secret'], 
            $content_type, $postBody);
	    global $LastOAuthBodyBaseString;
	    $lbs = $LastOAuthBodyBaseString;
	    if ( $verbose ) togglePre("Grade API Response (debug)",$response);
        $LastPOXGradeResponse = $response;
        $status = "Failure to store grade";
	    try {
		    $retval = parseResponse($response);
		    if ( isset($retval['imsx_codeMajor']) && $retval['imsx_codeMajor'] == 'success') {
                $status = true;
		    } else if ( isset($retval['imsx_description']) ) {
                $status = $retval['imsx_description'];
            }
	    } catch(Exception $e) {
		    $status = $e->getMessage();
	    }
        $detail = $status;
        if ( $detail === true ) {
            $detail = 'Success';
            error_log('Grade sent '.$grade.' to '.$sourcedid.' by '.$lti['user_id'].' '.$detail);
        } else {
            error_log('Grade failure:'.$status);
            error_log($response);
            return $status;
        }
    }

    // Update result in the database and in the LTI session area
    $_SESSION['lti']['grade'] = $grade;
    if ( $pdo !== false ) {
        $stmt = pdoQuery($pdo,
            "UPDATE {$CFG->dbprefix}lti_result SET grade = :grade, note = :note, 
                json = :json, updated_at = NOW() WHERE result_id = :RID",
            array(
                ':grade' => $grade,
                ':note' => $note,
                ':json' => $json,
                ':RID' => $result['result_id'])
        );
        if ( $stmt->success ) {
            error_log("Grade updated result_id=".$result['result_id']." grade=$grade");
        } else {
            error_log("Grade NOT updated result_id=".$result['result_id']." grade=$grade");
        }
        cacheClear('lti_result');
    }
    return $status;
}

function updateGradeJSON($pdo, $json) {
    global $CFG;
    $LTI = requireData(array('result_id'));

    $stmt = pdoQueryDie($pdo,
        "UPDATE {$CFG->dbprefix}lti_result SET json = :json, updated_at = NOW() 
            WHERE result_id = :RID",
        array(
            ':json' => $json,
            ':RID' => $LTI['result_id'])
    );
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

function cacheCheck($cacheloc, $cachekey)
{
    $cacheloc = "cache_" . $cacheloc;
    if ( isset($_SESSION[$cacheloc]) ) {
        $cache_row = $_SESSION[$cacheloc];
        if ( $cache_row[0] == $cachekey ) {
            error_log("Cache hit $cacheloc");
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
        error_log("Cache clear $cacheloc");
    }
    unset($_SESSION[$cacheloc]);
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

// No trailer

