<?php 
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

// Load up the LTI Support code
require_once 'lib/lti_util.php';
require_once 'config.php';

session_start();
header('Content-Type: text/html; charset=utf-8'); 

function lti_sha256($val) {
	return hash('sha256', $val);
}

// Extract info from $_POST applying our business rules and using our
// naming conventions
function extractPost() {
	$retval = array();
	$retval['course_title'] = isset($_POST['context_title']) ? $_POST['context_title'] : null;
	$retval['link_title'] = isset($_POST['resource_link_title']) ? $_POST['resource_link_title'] : null;
	$retval['user_email'] = isset($_POST['lis_person_contact_email_primary']) ? $_POST['lis_person_contact_email_primary'] : null;
	if ( isset($_POST['lis_person_name_full']) ) {
		$retval['user_displayname'] = $_POST['lis_person_name_full'];
	} else if ( isset($_POST['lis_person_name_given']) && isset($_POST['lis_person_name_family']) ) {
		$retval['user_displayname'] = $_POST['lis_person_name_given'].' '.$_POST['lis_person_name_family'];
	} else if ( isset($_POST['lis_person_name_given']) ) {
		$retval['user_displayname'] = $_POST['lis_person_name_given'];
	} else if ( isset($_POST['lis_person_name_family']) ) {
		$retval['user_displayname'] = $_POST['lis_person_name_given'];
	}
	$retval['role'] = 0;
	if ( isset($_POST['roles']) ) {
        $roles = strtolower($_POST['roles']);
        if ( ! ( strpos($roles,"instructor") === false ) ) $retval['role'] = 1;
        if ( ! ( strpos($roles,"administrator") === false ) ) $retval['role'] = 1;
	}
	return $retval;
}

print "<pre>\n";

$oauth_consumer_key = isset($_POST['oauth_consumer_key']) ? $_POST['oauth_consumer_key'] : false;
$context_id = isset($_POST['context_id']) ? $_POST['context_id'] : false;
$resource_link_id = isset($_POST['resource_link_id']) ? $_POST['resource_link_id'] : false;
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : false;

$lis_outcome_service_url = isset($_POST['lis_outcome_service_url']) ? $_POST['lis_outcome_service_url'] : false;
$lis_result_sourcedid = isset($_POST['lis_result_sourcedid']) ? $_POST['lis_result_sourcedid'] : false;

$post = extractPost();

// Fake this...
// $lis_result_sourcedid = false;

if ( $oauth_consumer_key && $context_id && $resource_link_id  && $user_id ) {
	// OK
} else {
	die("missing required element");
}

$p = $CFG->dbprefix;
try {
	$db = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex){
	die($ex->getMessage());
}

$sql = "SELECT k.key_id, k.secret, c.course_id, c.title AS course_title, 
	l.link_id, l.title AS link_title, 
	u.user_id, u.displayname AS user_displayname, u.email AS user_email,
	m.membership_id, m.role, p.profile_id, p.displayname AS profile_displayname";

if ( $lis_outcome_service_url && $lis_result_sourcedid ) {
	$sql .= ", s.service_id, r.result_id, r.sourcedid";
}
$sql .="
FROM {$p}_lti_key AS k 
LEFT JOIN {$p}_lti_course AS c ON k.key_id = c.key_id AND c.course_sha256 = :course 
LEFT JOIN {$p}_lti_link AS l ON c.course_id = l.course_id AND l.link_sha256 = :link
LEFT JOIN {$p}_lti_user AS u ON k.key_id = u.key_id AND u.user_sha256 = :user
LEFT JOIN {$p}_lti_membership AS m ON u.user_id = m.user_id AND c.course_id = m.course_id
LEFT JOIN {$p}_lti_profile AS p ON u.user_id = p.user_id 
";
if ( $lis_outcome_service_url && $lis_result_sourcedid ) {
	$sql .= "LEFT JOIN {$p}_lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service 
            LEFT JOIN {$p}_lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id AND 
					s.service_id = r.service_id AND r.sourcedid_sha256 = :sourcedid
            ";
}
$sql .= "WHERE k.key_sha256 = :key LIMIT 1
";

echo($sql);

$stmt = $db->prepare($sql);
$parms = array(
	':key' => lti_sha256($oauth_consumer_key),
	':course' => lti_sha256($context_id),
	':link' => lti_sha256($resource_link_id), 
	':user' => lti_sha256($user_id));

if ( $lis_outcome_service_url && $lis_result_sourcedid ) {
	$parms[':service'] = lti_sha256($lis_outcome_service_url);
	$parms[':sourcedid'] = lti_sha256($lis_result_sourcedid);
}

$stmt->execute($parms);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);

echo("===============\n");

if ( $row['key_id'] === null ) die('key not found');

// Start to insert the missing bits...
if ( $row['course_id'] === null) {
	$sql = "INSERT INTO {$p}_lti_course 
		( course_key, course_sha256, title, key_id, created_at, updated_at ) VALUES
		( :course_key, :course_sha256, :title, :key_id, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':course_key' => $context_id,
		':course_sha256' => lti_sha256($context_id),
		':title' => $post['course_title'],
		':key_id' => $row['key_id']));
	$row['course_id'] = $db->lastInsertId();
	$row['course_title'] = $post['course_title'];
	echo("=== Inserted course id=".$row['course_id']." ".$row['course_title']."\n");
}

if ( $row['link_id'] === null && isset($_POST['resource_link_id']) ) {
	$sql = "INSERT INTO {$p}_lti_link 
		( link_key, link_sha256, title, course_id, created_at, updated_at ) VALUES
		( :link_key, :link_sha256, :title, :course_id, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':link_key' => $_POST['resource_link_id'],
		':link_sha256' => lti_sha256($_POST['resource_link_id']),
		':title' => $post['link_title'],
		':course_id' => $row['course_id']));
	$row['link_id'] = $db->lastInsertId();
	$row['link_title'] = $post['link_title'];
	echo("=== Inserted link id=".$row['link_id']." ".$row['link_title']."\n");
}

if ( $row['user_id'] === null && isset($_POST['user_id']) ) {
	$sql = "INSERT INTO {$p}_lti_user 
		( user_key, user_sha256, displayname, email, key_id, created_at, updated_at ) VALUES
		( :user_key, :user_sha256, :displayname, :email, :key_id, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':user_key' => $_POST['user_id'],
		':user_sha256' => lti_sha256($_POST['user_id']),
		':displayname' => $post['user_displayname'],
		':email' => $post['user_email'],
		':key_id' => $row['key_id']));
	$row['user_id'] = $db->lastInsertId();
	$row['user_email'] = $post['user_email'];
	$row['user_displayname'] = $post['user_displayname'];
	echo("=== Inserted user id=".$row['user_id']." ".$row['user_email']."\n");
}

// Leave profile creation for later...

if ( $row['membership_id'] === null && $row['course_id'] !== null && $row['user_id'] !== null ) {
	$sql = "INSERT INTO {$p}_lti_membership 
		( course_id, user_id, role, created_at, updated_at ) VALUES
		( :course_id, :user_id, :role, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':course_id' => $row['course_id'],
		':user_id' => $row['user_id'],
		':role' => $post['role']));
	$row['membership_id'] = $db->lastInsertId();
	$row['role'] = $post['role'];
	echo("=== Inserted membership id=".$row['membership_id']." role=".$row['role'].
		" user=".$row['user_id']." course=".$row['course_id']."\n");
}

if ( $row['service_id'] === null && $lis_outcome_service_url && $lis_result_sourcedid ) {
	$sql = "INSERT INTO {$p}_lti_service 
		( service_key, service_sha256, key_id, created_at, updated_at ) VALUES
		( :service_key, :service_sha256, :key_id, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':service_key' => $lis_outcome_service_url,
		':service_sha256' => lti_sha256($lis_outcome_service_url),
		':key_id' => $row['key_id']));
	$row['service_id'] = $db->lastInsertId();
	echo("=== Inserted service id=".$row['service_id']." ".$lis_outcome_service_url."\n");
}

if ( $row['result_id'] === null && $row['service_id'] !== null && $lis_outcome_service_url && $lis_result_sourcedid ) {
	$sql = "INSERT INTO {$p}_lti_result 
		( sourcedid, sourcedid_sha256, service_id, link_id, user_id, created_at, updated_at ) VALUES
		( :sourcedid, :sourcedid_sha256, :service_id, :link_id, :user_id, NOW(), NOW() )";
	$stmt = $db->prepare($sql);
    $stmt->execute(array(
		':sourcedid' => $lis_result_sourcedid,
		':sourcedid_sha256' => lti_sha256($lis_result_sourcedid),
		':service_id' => $row['service_id'],
		':link_id' => $row['link_id'],
		':user_id' => $row['user_id']));
	$row['result_id'] = $db->lastInsertId();
	echo("=== Inserted result id=".$row['result_id']." service=".$row['service_id']." ".$lis_result_sourcedid."\n");
}

echo("===============\n");
var_dump($row);

print "\nRaw POST Parameters:\n\n";
ksort($_POST);
foreach($_POST as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    print htmlentities($key) . "=" . htmlentities($value) . " (".mb_detect_encoding($value).")\n";
}

?>
