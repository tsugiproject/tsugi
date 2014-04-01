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
    <link href="<?php echo($CFG->staticroot); ?>/static/css/custom-theme/jquery-ui-1.10.0.custom.css" rel="stylesheet">
    <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">

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
    echo('<script src="'.$CFG->staticroot.'/static/bootstrap/js/bootstrap.min.js"></script>'."\n");
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

function doneBootstrap() {
    $url = false;
	if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
        $url = $_SESSION['lti_post']['custom_done'];
    } else if ( isset($_GET["done"]) ) {
        $url = $_GET['done'];
    }
    if ( $url === false ) return;

    if ( $url == "_close" ) {
        echo("<a href=\"$\" onclick=\"window.close();\" class=\"btn btn-default\">Done</a>\n");
    } else {
        echo("<a href==\"$url\"  class=\"btn btn-default\">Done</button>\n");
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
    $debuglog = array();
    $retval = false;
    try {
        if ( $result === false ) $result = $_SESSION['lti'];
        $retval = sendGradeInternal($grade, null, null, $debuglog, $pdo, $result);
	} catch(Exception $e) {
		$retval = "Grade Exception: ".$e->getMessage();
		error_log($retval);
        $debuglog[] = $retval;
    } 
    if ( $verbose ) dumpGradeDebug($debuglog);
    return $retval;
}

function dumpGradeDebug($debuglog) {
    if ( ! is_array($debuglog) ) return;

    foreach ( $debuglog as $k => $v ) {
        if ( count($v) > 1 ) {
            togglePre($v[0], $v[1]);
        } else { 
            line_out($v[0]);
        }
    }
}

function sendGradeDetail($grade, $note=null, $json=null, &$debuglog, $pdo=false, $result=false) {
	if ( ! isset($_SESSION['lti']) || ! isset($_SESSION['lti']['sourcedid']) ) {
        return "Session not set up for grade return";
    }
    $retval = false;
    try {
        if ( $result === false ) $result = $_SESSION['lti'];
        $retval = sendGradeInternal($grade, $note, $json, $debuglog, $pdo, $result);
	} catch(Exception $e) {
		$retval = "Grade Exception: ".$e->getMessage();
        $debuglog[] = $retval;
		error_log($retval);
    }
    return $retval;
}

function sendGradeInternal($grade, $note, $json, &$debuglog, $pdo,  $result) {
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
    $ONLYSENDONCE = false;
    if ( $ONLYSENDONCE && isset($result['grade']) && $grade == $result['grade'] ) {
        $msg = "Grade result_id=".$result['result_id']." grade= $grade already sent...";
        if ( is_array($debuglog) ) $debuglog[] = $msg;
        error_log($msg);
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
    
	    if ( is_array($debuglog) ) $debuglog[] = array('Sending '.$grade.' to '.$lti['service'].' sourcedid='.$sourcedid);

	    if ( is_array($debuglog) )  $debuglog[] = array('Grade API Request (debug)',$postBody);

	    $response = sendOAuthBodyPOST($method, $lti['service'], $lti['key_key'], $lti['secret'], 
            $content_type, $postBody);
	    global $LastOAuthBodyBaseString;
	    $lbs = $LastOAuthBodyBaseString;
	    if ( is_array($debuglog) )  $debuglog[] = array("Grade API Response (debug)",$response);
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
	        if ( is_array($debuglog) )  $debuglog[] = array("Exception: ".$status);
	    }
        $detail = $status;
        if ( $detail === true ) {
            $detail = 'Success';
            $msg = 'Grade sent '.$grade.' to '.$sourcedid.' by '.$lti['user_id'].' '.$detail;
            error_log($msg);
	        if ( is_array($debuglog) )  $debuglog[] = array($msg);
        } else {
            $msg = 'Grade failure '.$grade.' to '.$sourcedid.' by '.$lti['user_id'].' '.$detail;
            error_log($msg);
	        if ( is_array($debuglog) )  $debuglog[] = array($msg);
            error_log($lti['service']);
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
            $msg = "Grade updated result_id=".$result['result_id']." grade=$grade";
        } else {
            $msg = "Grade NOT updated result_id=".$result['result_id']." grade=$grade";
        }
        error_log($msg);
        if ( is_array($debuglog) )  $debuglog[] = array($msg);
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
    $cacheloc = 'lti_link';
    $row = cacheCheck($cacheloc, $link_id);
    if ( $row != false ) return $row;
    $stmt = pdoQueryDie($pdo,
        "SELECT title FROM {$CFG->dbprefix}lti_link
            WHERE link_id = :LID",
        array(":LID" => $link_id)
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

function pagedPDOTable($pdo, $sql, &$queryvalues, $searchfields=array(), $orderfields=false, $view=false, $params=false) {
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

    $stmt = pdoQueryDie($pdo, $sql, $queryvalues);

    $rows = array();
    $count = 0;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        array_push($rows, $row);
        $count = $count + 1;
    }

    $have_more = false;
    if ( $count > $page_length ) {
        $have_more = true;
        $count = $page_length;
    }

    echo('<div style="float:right">');
    if ( $page_start > 0 ) {
        echo('<form style="display: inline">');
        echo('<input type="submit" value="Back">');
        $page_back = $page_start - $page_length;
        if ( $page_back < 0 ) $page_back = 0;
        do_form($params,Array('page_start' => $page_back));
        echo("</form>\n");
    }
    if ( $have_more ) {
        echo('<form style="display: inline">');
        echo('<input type="submit" value="Next"> ');
        $page_next = $page_start + $page_length;
        do_form($params,Array('page_start' => $page_next));
        echo("</form>\n");
    }
    echo("</div>\n");
    echo('<form>');
    echo('<input type="text" id="paged_search_box" value="'.htmlent_utf8($search).'" name="search_text">');
    do_form($params,Array('search_text' => false, 'page_start' => false));
?>
<input type="submit" value="Search">
<input type="submit" value="Clear Search" 
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

<table border="1">
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
}

function pagedPDO($pdo, $sql, $query_parms, $searchfields, $orderfields=false, $view=false, $params=false) {
    $newsql = pagedPDOQuery($sql, $query_parms, $searchfields, $orderfields, $params);
    // echo("<pre>\n$newsql\n</pre>\n");
    pagedPDOTable($pdo, $newsql, $query_parms, $searchfields, $orderfields, $view, $params);
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


// No trailer
