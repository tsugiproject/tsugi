<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Core\LTIX;
LTIX::getConnection();

?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<?php

$p = $CFG->dbprefix;
echo("Checking nonce table...<br/>\n");

$row = $PDOX->rowDie("SELECT count(*) AS count FROM {$CFG->dbprefix}lti_nonce");
if ( $row === false ) {
    echo("Unable to load the nonce counts<br/>\n");
} else {
    echo("Nonce count=".$row['count']."<br/>\n");
    if ( $row['count'] > 0 ) {
        $row = $PDOX->rowDie("SELECT created_at FROM {$CFG->dbprefix}lti_nonce ORDER BY created_at ASC LIMIT 1");
        if ( $row === false ) {
            echo("Unable to load the earliest nonce nonce<br/>\n");
        } else {
            echo("Earliest nonce=".$row['created_at']."<br/>\n");
        }
    }
}
?>
</body>
</html>

