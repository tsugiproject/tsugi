<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Core\LTIX;
LTIX::getConnection();


?>
<html>
<head>
</head>
<body>
<?php

$dry_run = ! isset($_GET['delete']);
if ( $dry_run ) {
    echo("<p>This is a dry run, check to see if the proposed changes make sense and\n");
    echo("scroll to the bottom to click a link to actually delete the duplicate entries.</p>\n");
}
$checkSQL = "SELECT profile_id, email, created_at FROM {$CFG->dbprefix}profile WHERE email IN (SELECT T.E FROM (select profile_id AS I, email AS E,COUNT(profile_sha256) as C FROM {$CFG->dbprefix}profile GROUP BY email ORDER BY C DESC) AS T WHERE T.C > 1) ORDER BY email DESC, created_at DESC;";

echo($checkSQL);
echo("<br/>\n");

$stmt = $PDOX->queryReturnError($checkSQL);
if ( ! $stmt->success ) {
    echo("Fail checking duplicate profile entries:<br/>\n");
    echo($checkSQL);
    echo("Error: ".$stmt->errorImplode."<br/>\n");
} else {
    $lastEmail = false;
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
	if ( $lastEmail == $row['email'] ) {
            echo("DELETE: ");
	    if ( ! $dry_run ) {
                $q = $PDOX->queryDie(
                   "DELETE FROM {$CFG->dbprefix}profile
                   WHERE profile_id = :PID",
                   array( ':PID' => $row['profile_id'])
                );
             }
        } else {
	    echo("<br/>\n");
            echo("Keep:   ");
        }
        echo($row['profile_id'].', '.htmlentities($row['email']).', '.$row['created_at']."<br/>\n");
	$lastEmail = $row['email'];
    }
}
if ( $dry_run ) {
    echo('<p><a href="patch_profile.php?delete=true">*** DELETE DATA ****</a></p>'."\n");
}
?>
<p><a href="upgrade.php">Go Back to Database Upgrade</a></p>

</body>
</html>

