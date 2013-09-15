<?php 
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// Load up the LTI Support code
require_once 'config.php';
require_once 'lti_db.php';

session_start();
header('Content-Type: text/html; charset=utf-8'); 

print "<pre>\n";

$post = extractPost();
if ( $post === false ) {
	die("missing required element");
}

try {
	$db = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex){
	die($ex->getMessage());
}

echo("==   CHECKKEY   ===\n");
// $row = checkKey($db, $CFG->dbprefix, "{$CFG->dbprefix}lti_profile", $post);
$row = checkKey($db, $CFG->dbprefix, false, $post);
echo("==   BACK   ===\n");
var_dump($row);
$valid = verifyKeyAndSecret($post['key'],$row['secret']);
if ( $valid === true ) {
	// OK
} else {
	print_r($valid);
	die();
}
echo("===============\n");
$actions = insertNew($row, $db, $CFG->dbprefix, $post);
print_r($actions);
echo("==   BACK   ===\n");
var_dump($row);

print "\nRaw POST Parameters:\n\n";
ksort($_POST);
foreach($_POST as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    print htmlentities($key) . "=" . htmlentities($value) . " (".mb_detect_encoding($value).")\n";
}

?>
