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
            WHERE key_id = :KID)
        ORDER BY created_at LIMIT 100",
        array(':KID' => $key_id)
    );

    $PDOX->queryDie("DELETE FROM
        {$CFG->dbprefix}lti_context
        WHERE key_id = :KID
        ORDER BY created_at LIMIT 100",
        array(':KID' => $key_id)
    );

    $PDOX->queryDie("DELETE FROM
        {$CFG->dbprefix}lti_user
        WHERE key_id = :KID
        ORDER BY created_at LIMIT 100",
        array(':KID' => $key_id)
    );

}

$OUTPUT->header();
$OUTPUT->bodyStart();
// No Nav - this is in a frame

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

if ( $context_count > 100 || $user_count > 100 || $blob_count > 100 ) {
?>
<p>This screen limits the records of each type deleted to be 100
to prevent runaway processes and limit the scope of the deletes
and reduce the probability of long-lasting transactions.
So you might need to run this process more than once.
</p>
<?php }
if ( $context_count > 0 || $user_count > 0 || $blob_count > 0 ) {
?>
<p>
<form method="get">
<input type="submit" onclick="$('#myspinner').show();return true;" name="delete" value="Delete Records"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</form>
</p>
<?php
} else {
    echo("<p>No records to delete</p>\n");
}
$OUTPUT->footer();

