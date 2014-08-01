<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "blob_util.php";

// Sanity checks
$LTI = \Tsugi\LTIX::requireData(array('context_id', 'link_id'));

$id = $_REQUEST['id'];
if ( strlen($id) < 1 ) {
    die("File not found");
}

$p = $CFG->dbprefix;
$stmt = $PDOX->prepare("SELECT file_name FROM {$p}sample_blob 
            WHERE file_id = :ID AND context_id = :CID");
$stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$fn = $row['file_name'];

if ( $row === false ) {
    die("File not found");
}

if ( isset($_POST["doDelete"]) ) {
    $stmt = $PDOX->prepare("DELETE FROM {$p}sample_blob
            WHERE file_id = :ID AND context_id = :CID");
    $stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
    $_SESSION['success'] = 'File deleted';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Switch to view / controller
$OUTPUT->header();
$OUTPUT->flashMessages();

echo '<h4 style="color:red">Are you sure you want to delete: ' .htmlent_utf8($fn). "</h4>\n"; 
?>
<form name=myform enctype="multipart/form-data" method="post">
<input type=hidden name="id" value="<?php echo $_REQUEST['id']; ?>">
<p><input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel">
<input type=submit name=doDelete value="Delete"></p>
</form>
<?php

$OUTPUT->footer();
