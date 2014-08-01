<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "blob_util.php";

// Sanity checks
$LTI = \Tsugi\LTIX::requireData(array('context_id', 'role'));

// Model 
$p = $CFG->dbprefix;
if( isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 1) {
    $_SESSION['error'] = 'Error: Maximum size of '.maxUpload().'MB exceeded.';
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
    $stmt = $PDOX->prepare("INSERT INTO {$p}sample_blob 
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
    $_SESSION['error'] = 'Error: Maximum size of '.maxUpload().'MB exceeded.';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// View 
$OUTPUT->header();
?>
</head>
<body>
<?php
$OUTPUT->flashMessages();
welcomeUserCourse();

$foldername = getFolderName();
if ( !file_exists($foldername) ) mkdir ($foldername);

$stmt = $PDOX->prepare("SELECT file_id, file_name FROM {$p}sample_blob
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
<h4>Upload file (max <?php echo(maxUpload());?>MB)</h4>
<form name="myform" enctype="multipart/form-data" method="post" action="<?php addSession('index.php');?>">
<p>Upload File: <input name="uploaded_file" type="file"> 
   <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(maxUpload());?>000000" /> 
   <input type="submit" name="submit" value="Upload"></p>
</form>
<?php
}

$OUTPUT->footer();
