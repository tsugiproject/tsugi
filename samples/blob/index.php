<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\BlobUtil;

// Sanity checks
$LTI = LTIX::requireData(array(LTIX::CONTEXT, LTIX::LINK));

if ( ! $USER->instructor ) die("Must be instructor");

// Model
$p = $CFG->dbprefix;
if( isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 1) {
    $_SESSION['error'] = 'Error: Maximum size of '.BlobUtil::maxUpload().'MB exceeded.';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if( isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 0)
{
    $filename = basename($_FILES['uploaded_file']['name']);
    if ( strpos($filename, '.php') !== false ) {
        $_SESSION['error'] = 'Error: Wrong file type.';
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    $fp = fopen($_FILES['uploaded_file']['tmp_name'], "rb");
    $stmt = $PDOX->prepare("INSERT INTO {$p}blob_file
        (context_id, file_name, contenttype, content, created_at)
        VALUES (?, ?, ?, ?, NOW())");

    $stmt->bindParam(1, $CONTEXT->id);
    $stmt->bindParam(2, $filename);
    $stmt->bindParam(3, $_FILES['uploaded_file']['type']);
    $stmt->bindParam(4, $fp, PDO::PARAM_LOB);
    $PDOX->beginTransaction();
    $stmt->execute();
    $PDOX->commit();

    $_SESSION['success'] = 'File uploaded';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Sometimes, if the maxUpload_SIZE is exceeded, it deletes all of $_POST
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $_SESSION['error'] = 'Error: Maximum size of '.BlobUtil::maxUpload().'MB exceeded.';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

$stmt = $PDOX->prepare("SELECT file_id, file_name FROM {$p}blob_file
        WHERE context_id = :CI");
$stmt->execute(array(":CI" => $CONTEXT->id));

$count = 0;
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $id = $row['file_id'];
    $fn = $row['file_name'];
    echo '<li><a href="blob_serve.php?id='.$id.'" target="_new">'.htmlent_utf8($fn).'</a>';
    if ( $USER->instructor ) {
        echo ' (<a href="blob_delete.php?id='.$id.'">Delete</a>)';
    }
    echo '</li>';
    $count++;
}

if ( $count == 0 ) echo "<p>No Files Found</p>\n";

echo("</ul>\n");

if ( $USER->instructor ) { ?>
<h4>Upload file (max <?php echo(BlobUtil::maxUpload());?>MB)</h4>
<form name="myform" enctype="multipart/form-data" method="post" action="<?php addSession('index.php');?>">
<p>Upload File: <input name="uploaded_file" type="file">
   <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(BlobUtil::maxUpload());?>000000" />
   <input type="submit" name="submit" value="Upload"></p>
</form>
<?php
}

$OUTPUT->footer();
