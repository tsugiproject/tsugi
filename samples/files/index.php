<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "files_util.php";

// Sanity checks
$LTI = ltiRequireData(array('user_id', 'context_id'));

// Model 
$p = $CFG->dbprefix;

if( isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 1) {
    $_SESSION['error'] = 'Error: Maximum size of '.maxUpload().'MB exceeded.';
    header( 'Location: '.sessionize('index.php') ) ;
    return;
}

if( isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 0)
{
   $filename =strtolower(basename($_FILES['uploaded_file']['name']));
   $filename = fixFileName($filename);

   $foldername = getFolderName();
   $ext=".".$ext;
   $newname = $foldername.'/'.$filename;
   if ((move_uploaded_file($_FILES['uploaded_file']['tmp_name'],$newname)))
   {
        $_SESSION['success'] = 'File uploaded';
        header( 'Location: '.sessionize('index.php') ) ;
   }
   else
    {
        $_SESSION['err'] = 'File upload failed';
        header( 'Location: '.sessionize('index.php') ) ;
    }
    return;
}

// Sometimes, if the maxUpload_SIZE is exceeded, it deletes all of $_POST
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $_SESSION['error'] = 'Error: Maximum size of '.maxUpload().'MB exceeded.';
    header( 'Location: '.sessionize('index.php') ) ;
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
debug_log($foldername);
if ( !file_exists($foldername) ) mkdir ($foldername);

$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
$count = 0;
foreach (glob($foldername."/*") as $filename) {
    $fn = substr($filename,strlen($foldername)+1);
    echo '<li><a href="files_serve.php?file='.$fn.'" target="_new">'.$fn.'</a>';
    if ( $USER->instructor ) {
        echo ' (<a href="files_delete.php?file='.$fn.'">Delete</a>)';
    }
    echo '</li>';
    $count = $count + 1;
    debug_log($filename . " " . finfo_file($finfo, $filename));
}
if ( $count == 0 ) echo "<p>No Files Found</p>\n";

echo("</ul>\n");
finfo_close($finfo);

if ( $USER->instructor ) { ?>
<h4>Upload file (max <?php echo(maxUpload());?>MB)</h4>
<form name="myform" enctype="multipart/form-data" method="post" action="<?php sessionize('index.php');?>">
<p>Upload File: <input name="uploaded_file" type="file"> 
   <input type="submit" name="submit" value="Upload"></p>
   <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo(maxUpload());?>000000" />
</form>
<?php
}

$OUTPUT->footer();
