<?php
use \Tsugi\Util\U;
use \Tsugi\Blob\Access;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Core\LTIX;
LTIX::getConnection();

// https://stackoverflow.com/questions/3133209/how-to-flush-output-after-each-echo-call
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);

$OUTPUT->header();
?>
</head>
<body>
<h1>Blob cleanup</h1>
<p>This tool removes unused blobs from the <b>blob_blob</b> table.
</p>
<?php if ( isset($CFG->dataroot) && strlen($CFG->dataroot) > 0 ) { ?>
<p>
To remove unused files from the on-disk store, you must log in and
run <b>admin\blob\filecheck.php</b> since it is a long running task.
</p>
<?php } ?>
<?php
if ( U::get($_POST,'migrate') ) {
    $start = time();
    $files = $PDOX->allRowsDie("SELECT BB.blob_id, BB.blob_sha256
        FROM {$CFG->dbprefix}blob_blob AS BB 
        LEFT JOIN {$CFG->dbprefix}blob_file AS BF 
            ON BB.blob_sha256 = BF.file_sha256 AND BB.blob_id = BF.blob_id
        WHERE BF.blob_id IS NULL LIMIT 100");

    echo("<pre>\n");
    foreach ( $files as $row ) {
        $blob_id = $row['blob_id'];
        $blob_sha256 = $row['blob_sha256'];
        if ( ! $blob_id ) continue;

        $stmt = $PDOX->prepare("DELETE FROM {$CFG->dbprefix}blob_blob
            WHERE blob_id = :ID");
        $stmt->execute(array(':ID' => $blob_id));

        $note = "Unreferenced blob removed blob_id=$blob_id sha=$blob_sha256";
        error_log($note);
        echo("$note\n");

        $delta = time() - $start;
        if ( $delta > 10 ) {
            echo("Migration stopped at 10 seconds ellapsed time\n");
            break;
        }
    }
    echo("</pre>\n");
}

$row = $PDOX->rowDie("SELECT count(BB.blob_id) AS count
    FROM {$CFG->dbprefix}blob_blob AS BB
    LEFT JOIN {$CFG->dbprefix}blob_file AS BF
        ON BB.blob_sha256 = BF.file_sha256 AND BB.blob_id = BF.blob_id
    WHERE BF.blob_id IS NULL");
$file_count = $row ? $row['count'] : 0;

?>
<p>Un-referenced blobs to delete: <?= $file_count ?></p>
</p>
<?php if ( $file_count > 0 ) { ?>
<p>
<form method="post">
<input type="submit" onclick="$('#myspinner').show();return true;" name="migrate" value="Delete Blobs"/>
<input type="submit" onclick="$('#myspinner').show();return true;" name="reset" value="Clear Results"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</form>
</p>
<?php
}

if ( ! U::get($_POST,'migrate') && $file_count > 100 ) {
?>
<p>
Removing a large number of blobs is a resource intensive task
so we only do a limited number of removals each time this tool
is executed.  If you want to remove a large number of files
all at once, you can log into the server
and do the following:
<pre>
cd tsugi/admin/blob
php blobcheck.php
</pre>
The <b>blobcheck.php</b> tool has a dry run mode and must be
run under the same account (UID) as the web server process so
that the web server can read and write the
resulting files.
</p>
<?php
}
