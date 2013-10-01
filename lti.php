<?php 
require_once 'setup.php';
require_once 'config.php';
require_once 'lti_db.php';

$post = extractPost();
if ( $post === false ) {
	error_log("Missing post data");
	die("Missing data");
}
$session_id = getCompositeKey($post, $CFG->sessionsalt);
session_id($session_id);
session_start();
header('Content-Type: text/html; charset=utf-8'); 

try {
	$db = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex){
	error_log("DB connection: "+$ex->getMessage());
	die($ex->getMessage());
}

// $row = checkKey($db, $CFG->dbprefix, "sample_profile", $post);
$row = checkKey($db, $CFG->dbprefix, false, $post);

$valid = verifyKeyAndSecret($post['key'],$row['secret']);
if ( $valid !== true ) {
	error_log("Key / Secret fail key=".$post['key']);
    print "<pre>\n";
	print_r($valid);
    print "</pre>\n";
	die();
}

$actions = adjustData($db, $CFG->dbprefix, $row, $post);

// Put the information into the row variable
// TODO: do AES on the secret
$_SESSION['lti'] = $row;

/*
print "\n<pre>\nRaw POST Parameters:\n\n";
ksort($_POST);
foreach($_POST as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    print htmlentities($key) . "=" . htmlentities($value) . " (".mb_detect_encoding($value).")\n";
}
print "\n</pre>\n";
flush();
*/

// See if we have a custom assignment setting.
$url = $CFG->folder . '/free.php';
if ( isset($_POST['custom_assn'] ) ) {
    $url = $CFG->folder . '/'.$_POST['custom_assn'].'.php';
    $_SESSION['assn'] = $_POST['custom_assn'];
}

if ( isset($_POST['custom_due'] ) ) {
	$when = strtotime($_POST['custom_due']);
	if ( $when === false ) {
		echo("<p>Error, bad setting for custom_due=".htmlentities($_POST['custom_due'])."</p>");
		error_log("Bad custom_due=".$_POST['custom_due']);
		flush();
	} else {
		$_SESSION['due'] = $_POST['custom_due'];
	}
}

if ( isset($_POST['custom_timezone'] ) ) {
	$_SESSION['timezone'] = $_POST['timezone'];
}

if ( isset($_POST['custom_penalty_time'] ) ) {
	if ( $_POST['custom_penalty_time'] + 0 == 0 ) {
		echo("<p>Error, bad setting for custom_penalty_time=".htmlentities($_POST['custom_penalty_time'])."</p>");
		error_log("Bad custom_penalty_time=".$_POST['custom_penalty_time']);
		flush();
	} else {
		$_SESSION['penalty_time'] = $_POST['custom_penalty_time'];
	}
}

if ( isset($_POST['custom_penalty_cost'] ) ) {
	if ( $_POST['custom_penalty_cost'] + 0 == 0 ) {
		echo("<p>Error, bad setting for custom_penalty_cost=".htmlentities($_POST['custom_penalty_cost'])."</p>");
		error_log("Bad custom_penalty_cost=".$_POST['custom_penalty_cost']);
		flush();
	} else {
		$_SESSION['penalty_cost'] = $_POST['custom_penalty_cost'];
	}
}

$query = false;
if ( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0) {
	$query = true;
	$url .= '?' . $_SERVER['QUERY_STRING'];
}
if ( headers_sent() ) {
	echo('<p><a href="'.$url.'">Click to continue</a></p>');
} else { 
	$url .= $query ? '&' : '?';
	$url .= session_name() . '=' . session_id();
    header('Location: '.$url);
}
?>
