<?php 
require_once 'config.php';
require_once 'db.php';
require_once 'lib/lti_db.php';

// Pull LTI data out of the incoming $_POST and map into the same
// keys that we use in our database (i.e. like $row)
$post = extractPost();
if ( $post === false ) {
	error_log("Missing post data");
	die("Missing data");
}

// We make up a Session ID Key because we don't want a new one
// each time the same user launches the same link.
$session_id = getCompositeKey($post, $CFG->sessionsalt);
session_id($session_id);
session_start();
header('Content-Type: text/html; charset=utf-8'); 

// Since we might reuse session IDs, clean it out
foreach($_SESSION as $k => $v ) {
	unset($_SESSION[$k]);
}

// Read all of the data from the database with a very long
// LEFT JOIN and get all the dat we have back in the $row variable
$row = loadAllData($db, $CFG->dbprefix, false, $post);

// Add a LEFT JOIN on the profile table
// $row = checkKey($db, $CFG->dbprefix, "sample_profile", $post);

// Use returned data to check the OAuth signature on the
// incoming data
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

// See if we have a custom assignment setting.
$url = 'mod/php-intro/custom.php';
if ( isset($_POST['custom_assn'] ) ) {
    $url = $_POST['custom_assn'];
	if ( strpos($url, 'mod/') === false ) $url = 'mod/php-intro/'.$url.'.php';  // Compatiblity
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
	$_SESSION['timezone'] = $_POST['custom_timezone'];
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
