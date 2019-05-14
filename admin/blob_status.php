<?php
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

?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<h1>Blob Status</h1>
<p>Blobs are being stored
<?php
if ( ! isset($CFG->dataroot) || ! $CFG->dataroot ) {
    echo('in the database (should only be used for small sites)');
} else {
    echo(' on disk at ');
    echo(htmlentities($CFG->dataroot));
}
?>
</p>
<ul>
<li>Total blobs in the system
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_file");
$file_count = $row ? $row['count'] : 0;
echo( $file_count );
?>
</li>
<li>Blobs on disk
<?php
$row = $PDOX->rowDie("SELECT COUNT(DISTINCT(file_sha256)) AS count FROM {$CFG->dbprefix}blob_file WHERE path IS NOT NULL");
$blob_disk = $row ? $row['count'] : 0;
echo( $blob_disk );
?>
</li>
<li>Blobs in single instance database table (blob_blob)
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_blob");
$blob_single = $row ? $row['count'] : 0;
echo( $blob_single );
?>
</li>
<li>Unused blobs in the single instance database table (blob_blob)
<?php
$row = $PDOX->rowDie("SELECT count(BB.blob_id) AS count
    FROM {$CFG->dbprefix}blob_blob AS BB
    LEFT JOIN {$CFG->dbprefix}blob_file AS BF
        ON BB.blob_sha256 = BF.file_sha256 AND BB.blob_id = BF.blob_id
    WHERE BF.blob_id IS NULL");
$blob_wasted = $row ? $row['count'] : 0;
echo($blob_wasted);
?>
</li>
<li>Legacy blobs in multi instance database table (blob_file)
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_file WHERE path IS NULL AND blob_id IS NULL");
$blob_multi = $row ? $row['count'] : 0;
echo( $blob_multi );
?>
</li>
<li>The number of reused blobs <?= $file_count - ($blob_disk + $blob_single + $blob_multi) + $blob_wasted ?>
</li>
</ul>
<?php
if ( isset($CFG->migrateblobs) && $CFG->migrateblobs ) {
    echo("<p>Blobs are being automatically migrated as they are used</p>\n");
}
?>
<p>Note that even when blobs are going to disk, the 12345 blobs will be
stored in <b>blob_blob</b> unless you override the <b>$CFG->testblobs</b> value.
</p>
</body>
</html>
