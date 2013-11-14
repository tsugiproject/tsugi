<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";

session_start();

// Sanity checks
if ( !isset($_SESSION['lti']) ) {
	die('This tool need to be launched using LTI');
}
$LTI = $_SESSION['lti'];
if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
	die('A user_id and link_id are required for this tool to function.');
}
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

// Model 
$p = $CFG->dbprefix;
$stmt = $db->prepare("SELECT code FROM {$p}attend_code WHERE link_id = :ID");
$stmt->execute(array(":ID" => $LTI['link_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$old_code = "";
if ( $row !== false ) $old_code = $row['code'];

if ( isset($_POST['code']) && $instructor ) {
    $sql = "INSERT INTO {$p}attend_code 
			(link_id, code) VALUES ( :ID, :CO ) 
			ON DUPLICATE KEY UPDATE code = :CO";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':CO' => $_POST['code'],
        ':ID' => $LTI['link_id']));
    $_SESSION['success'] = 'Code updated';
    header( 'Location: '.sessionize('index.php') ) ;
    return;
} else if ( isset($_POST['code']) ) { // Student
	if ( $old_code == $_POST['code'] ) {
		$sql = "INSERT INTO {$p}attend 
			(link_id, user_id, ipaddr, attend, updated_at) 
			VALUES ( :LI, :UI, :IP, NOW(), NOW() ) 
			ON DUPLICATE KEY UPDATE updated_at = NOW()";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':LI' => $LTI['link_id'],
			':UI' => $LTI['user_id'],
			':IP' => $_SERVER["REMOTE_ADDR"]));
	    $_SESSION['success'] = 'Attendance Recorded...';
	} else {
	    $_SESSION['error'] = 'Code incorrect';
	}
	header( 'Location: '.sessionize('index.php') ) ;
	return;
}

// View 
?>
<html><head><title>Attendance tool</title>
</head>
<body style="background-color:orange;">
<?php
if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
if ( isset($_SESSION['success']) ) {
    echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
    unset($_SESSION['success']);
}

// A nice welcome...
echo("<p>Welcome");
if ( isset($LTI['user_displayname']) ) {
	echo(" ");
    echo(htmlent_utf8($LTI['user_displayname']));
}
if ( isset($LTI['context_title']) ) {
	echo(" from ");
    echo(htmlent_utf8($LTI['context_title']));
}

if ( $instructor ) {
	echo(" (Instructor)");
}
echo("</p>\n");

echo('<form method="post">');
echo("Enter code:\n");
if ( $instructor ) {
echo('<input type="text" name="code" value="'.htmlent_utf8($old_code).'"> ');
echo('<input type="submit" name="send" value="Update code"><br/>');
} else {
echo('<input type="text" name="code" value=""> ');
echo('<input type="submit" name="send" value="Record attendance"><br/>');
}
echo("\n</form>\n");

if ( $instructor ) {
	$stmt = $db->prepare("SELECT user_id,attend,ipaddr FROM {$p}attend 
			WHERE link_id = :LI ORDER BY attend DESC, user_id");
	$stmt->execute(array(':LI' => $LTI['link_id']));
	echo('<table border="1">'."\n");
	echo("<tr><th>User</th><th>Attendance</th><th>IP Address</th></tr>\n");
	while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
		echo "<tr><td>";
		echo($row['user_id']);
		echo("</td><td>");
		echo($row['attend']);
		echo("</td><td>");
		echo(htmlentities($row['ipaddr']));
		echo("</td></tr>\n");
	}
	echo("</table>\n");
}

echo("<p>Here is the session information:\n<pre>\n");
var_dump($_SESSION);
echo("\n</pre>\n");
