<?php 
require_once 'setup.php';
require_once 'config.php';
require_once 'lti_db.php';

$post = extractPost();
if ( $post === false ) {
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
	die($ex->getMessage());
}

// $row = checkKey($db, $CFG->dbprefix, "sample_profile", $post);
$row = checkKey($db, $CFG->dbprefix, false, $post);

$valid = verifyKeyAndSecret($post['key'],$row['secret']);
if ( $valid === false ) {
	print_r($valid);
	die();
}

$actions = adjustData($db, $CFG->dbprefix, $row, $post);

// Put the information into the row variable
// TODO: do AES on the secret
$_SESSION['lti'] = $row;

/*
print "\nRaw POST Parameters:\n\n";
ksort($_POST);
foreach($_POST as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    print htmlentities($key) . "=" . htmlentities($value) . " (".mb_detect_encoding($value).")\n";
}
*/

$url = "grade/assn01.php";
$query = false;
if ( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0) {
	$query = true;
	$url .= '?' . $_SERVER['QUERY_STRING'];
}
if ( headers_sent() ) {
	echo('<a href="'.$url.'">Click to continue</a>');
} else { 
	$url .= $query ? '&' : '?';
	$url .= session_name() . '=' . session_id();
    header('Location: '.$url);
}
?>
