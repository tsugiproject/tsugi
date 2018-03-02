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
<li>Blobs in single instance database table (blob_blob)
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_blob");
$blob_single = $row ? $row['count'] : 0;
echo( $blob_single );
?>
</li>
<li>Blobs in multi instance database table (blob_file)
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}blob_file WHERE path IS NULL AND blob_id IS NULL");
$blob_multi = $row ? $row['count'] : 0;
echo( $blob_multi );
?>
</li>
<li>Blobs on disk
<?php
$row = $PDOX->rowDie("SELECT COUNT(DISTINCT(file_sha256)) AS count FROM {$CFG->dbprefix}blob_file WHERE path IS NOT NULL");
$blob_disk = $row ? $row['count'] : 0;
echo( $blob_disk );
?>
</li>
<li>The number of reused blobs <?= $file_count - ($blob_disk + $blob_single + $blob_multi) ?>
</li>
</ul>
</body>
</html>
