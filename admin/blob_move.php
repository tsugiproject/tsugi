<?php
use \Tsugi\Util\U;
use \Tsugi\Blob\Access;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( !isset($CFG->dataroot) || strlen($CFG->dataroot) < 1 ) die('Must set $CFG->dataroot');

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
<h1>Blob Migration</h1>
<p>This tool migrates blobs from the blob_file (multi-instance) and
blob_blob (single-instance) tables to disk.
</p>
<p>Blobs are being stored at <?= htmlentities($CFG->dataroot) ?>.
</p>
<?php
if ( U::get($_GET,'migrate') ) {
    $start = time();
    $files = $PDOX->allRowsDie("SELECT file_id FROM {$CFG->dbprefix}blob_file WHERE path IS NULL LIMIT 100");
    echo("<pre>\n");
    foreach ( $files as $row ) {
        $id = $row['file_id'];
        if ( ! $id ) continue;
        $retval = Access::blob2file($id);
        if ( is_string($retval) ) {
            echo("Could not Migrate file_id=$id ".htmlentities($retval)."\n");
            break;
        }
        echo("File migrated file_id=$id\n");
        $delta = time() - $start;
        if ( $delta > 10 ) {
            echo("Migration stopped at 10 seconds ellapsed time\n");
            break;
        }
    }
    echo("</pre>\n");
}

$row = $PDOX->rowDie("SELECT count(file_id) AS count FROM {$CFG->dbprefix}blob_file WHERE path IS NULL");
$file_count = $row ? $row['count'] : 0;

?>
<p>Blobs in the database: <?= $file_count ?></p>
</p>
<?php if ( $file_count > 0 ) { ?>
<p>
<form method="get">
<input type="submit" onclick="$('#myspinner').show();return true;" name="migrate" value="Migrate Blobs"/>
<input type="submit" onclick="$('#myspinner').show();return true;" name="reset" value="Clear Results"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</form>
</p>
<?php
}

if ( ! U::get($_GET,'migrate') && $file_count > 100 ) {
?>
<p>
Migrating a large number of blobs is a resource intensive task
so we only do a limited number of migrations each time this tool
is executed.  If you want to migrate a large number of files
all at once, you can log into the server
and do the following:
<pre>
cd tsugi/admin/blob
php blob2file.php
</pre>
This <b>blob2file.php</b> tool has a dry run mode and must be
run under the same account (UID) as the web server process so
that the web server can read and write the
resulting files.
</p>
<?php
}

$OUTPUT->footer();
