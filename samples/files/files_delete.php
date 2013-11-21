<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "files_util.php";

session_start();

// Sanity checks
requireData(array('user_id', 'link_id'));
$LTI = $_SESSION['lti'];


$fn = $_REQUEST['file'];
if ( strlen($fn) < 1 ) {
    die("File name not found");
}

$fn = fixFileName($fn);
$foldername = getFolderName($LTI);
$filename = $foldername . '/' . fixFileName($fn);

if ( isset($_POST["doDelete"]) ) {
    $foldername = getFolderName($LTI);
    $filename = $foldername . '/' . fixFileName($_POST['file']);
    if ( unlink($filename) ) { 
        $_SESSION['success'] = 'File deleted';
        header( 'Location: '.sessionize('index.php') ) ;
    } else {
        $_SESSION['err'] = 'File delete failed';
        header( 'Location: '.sessionize('index.php') ) ;
    }
    return;
}

// Switch to view / controller
headerContent();
flashMessages();

echo '<h4 style="color:red">Are you sure you want to delete: ' .$fn. "</h4>\n"; 
?>
<form name=myform enctype="multipart/form-data" method="post">
    <input type=hidden name="file" value="<?php echo $_REQUEST['file']; ?>">
<p><input type=submit name=doCancel onclick="location='<?php echo(sessionize('index.php'));?>'; return false;" value="Cancel">
<input type=submit name=doDelete value="Delete"></p>
</form>
<?php
debugLog('Folder: '.$foldername);

footerContent();
