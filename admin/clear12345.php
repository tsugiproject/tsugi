<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
LTIX::getConnection();

$key_sha256 = lti_sha256('12345');
$row = $PDOX->rowDie("SELECT key_id FROM 
    {$CFG->dbprefix}lti_key 
    WHERE key_sha256 = :KSH",
    array(':KSH' => $key_sha256)
);
if ( ! $row ) {
    die('Could not find key 12345');
}
$key_id = $row['key_id'];

if ( U::get($_GET,'delete') ) {
    $PDOX->queryDie("DELETE FROM 
        {$CFG->dbprefix}blob_file 
        WHERE context_id IN (
            SELECT context_id FROM 
            {$CFG->dbprefix}lti_context
            WHERE key_id = :KID)",
        array(':KID' => $key_id)
    );

    $PDOX->queryDie("DELETE FROM
        {$CFG->dbprefix}lti_context 
        WHERE key_id = :KID",
        array(':KID' => $key_id)
    );

    $PDOX->queryDie("DELETE FROM
        {$CFG->dbprefix}lti_user 
        WHERE key_id = :KID",
        array(':KID' => $key_id)
    );

}

?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<?php

echo("Scanning for files, contexts, and users associated with key 12345...<br/>\n");

$row = $PDOX->rowDie("SELECT count(*) AS count FROM 
    {$CFG->dbprefix}blob_file AS B
    JOIN {$CFG->dbprefix}lti_context AS C ON B.context_id = C.context_id
    WHERE C.key_id = :KID",
    array(':KID' => $key_id)
);

$blob_count = 0;
if ( $row ) {
    $blob_count = $row['count']+0;
}
$row = $PDOX->rowDie("SELECT count(*) AS count FROM 
    {$CFG->dbprefix}lti_context
    WHERE key_id = :KID",
    array(':KID' => $key_id)
);

$context_count = 0;
if ( $row ) {
    $context_count = $row['count']+0;
}

$row = $PDOX->rowDie("SELECT count(*) AS count FROM 
    {$CFG->dbprefix}lti_user
    WHERE key_id = :KID",
    array(':KID' => $key_id)
);

$user_count = 0;
if ( $row ) {
    $user_count = $row['count']+0;
}

echo("<p>Context=$context_count user=$user_count file=$blob_count</p>\n");

?>
<form method="get">
<input type="submit" name="delete" value="Delete Records"/>
</form>
</body>
</html>

