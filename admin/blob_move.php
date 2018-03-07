<?php
use \Tsugi\Util\U;
use \Tsugi\Blob\BlobUtil;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

$todisk = isset($CFG->dataroot) && strlen($CFG->dataroot) > 0;

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
<?php if ( $todisk ) { ?>
<p>Blobs are being stored at <?= htmlentities($CFG->dataroot) ?>.
</p>
<p>This tool migrates blobs from the blob_file (multi-instance) and/or
blob_blob (single-instance) tables to disk.
</p>
<?php } else { ?>
<p>This tool migrates blobs from the blob_file table (multi-instance) to the
blob_blob table (single-instance).
</p>
<?php } ?>
<?php
$where = "path IS NULL AND blob_id IS NULL"; // Leave disk blobs alone
if ( $todisk ) $where = "path IS NULL";

if ( U::get($_POST,'migrate') ) {
    $start = time();
    $files = $PDOX->allRowsDie("SELECT file_id, context_id FROM {$CFG->dbprefix}blob_file WHERE $where LIMIT 100");
    echo("<pre>\n");
    foreach ( $files as $row ) {
        $id = $row['file_id'];
        $context_id = $row['context_id'];
        if ( ! $id ) continue;
        $test_key = BlobUtil::isTestKey($context_id);
        $retval = BlobUtil::migrate($id, $test_key);
        if ( is_string($retval) ) {
            echo("Could not Migrate file_id=$id ".htmlentities($retval)."\n");
            break;
        }
        if ( $retval == true ) {
            echo("File migrated file_id=$id\n");
            error_log("File migrated file_id=$id");
        } else {
            echo("File did not migrate file_id=$id\n");
            error_log("File did not migrate file_id=$id");
        }
        $delta = time() - $start;
        if ( $delta > 10 ) {
            echo("Migration stopped at 10 seconds ellapsed time\n");
            break;
        }
    }
    echo("</pre>\n");
}

$row = $PDOX->rowDie("SELECT count(file_id) AS count FROM {$CFG->dbprefix}blob_file WHERE $where");
$file_count = $row ? $row['count'] : 0;

?>
<p>Blobs to migrate: <?= $file_count ?></p>
</p>
<?php if ( $file_count > 0 ) { ?>
<p>
<form method="post">
<input type="submit" onclick="$('#myspinner').show();return true;" name="migrate" value="Migrate Blobs"/>
<input type="submit" onclick="$('#myspinner').show();return true;" name="reset" value="Clear Results"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</form>
</p>
<?php
}

if ( ! U::get($_POST,'migrate') && $file_count > 100 ) {
?>
<p>
Migrating a large number of blobs is a resource intensive task
so we only do a limited number of migrations each time this tool
is executed.  If you want to migrate a large number of files
all at once, you can log into the server
and do the following:
<pre>
cd tsugi/admin/blob
php migrate.php
</pre>
This <b>migrate.php</b> tool has a dry run mode and must be
run under the same account (UID) as the web server process so
that the web server can read and write the
resulting files.
</p>
<?php
}

$OUTPUT->footer();
