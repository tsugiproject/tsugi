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
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

// Model 
$p = $CFG->dbprefix;

$ext="";
if((!empty($_FILES["uploaded_file"])) && ($_FILES['uploaded_file']['error'] == 0))
{
   $filename =strtolower(basename($_FILES['uploaded_file']['name']));

   $filename = fixFileName($filename);

   if ($_FILES["uploaded_file"]["size"] < 6000000)
   {
       $foldername = getFolderName($LTI);
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
    } else {
        $_SESSION['err'] = 'Error: This only accepts files 5GB or smaller.';
		header( 'Location: '.sessionize('index.php') ) ;
    }
    return;
}

// View 
headerContent();
?>
</head>
<body>
<?php
flashMessages();
welcomeUserCourse($LTI);

$foldername = getFolderName($LTI);
debugLog($foldername);
if ( !file_exists($foldername) ) mkdir ($foldername);

$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
$count = 0;
foreach (glob($foldername."/*") as $filename) {
    $fn = substr($filename,strlen($foldername)+1);
    echo '<li><a href="files_serve.php?file='.$fn.'" target="_new">'.$fn.'</a>';
    if ( isInstructor($LTI) ) {
    	echo ' (<a href="files_delete.php?file='.$fn.'">Delete</a>)';
    }
    echo '</li>';
    $count = $count + 1;
    debugLog($filename . " " . finfo_file($finfo, $filename));
}
if ( $count == 0 ) echo "<p>No Files Found</p>\n";

echo("</ul>\n");
finfo_close($finfo);

if ( isInstructor($LTI) ) { ?>
<h4>Upload new file</h4>
<form name="myform" enctype="multipart/form-data" method="post" action="index.php">
<p>Upload File: <input name="uploaded_file" type="file"> 
   <input type="submit" name="submit" value="Upload"></p>
</form>
<?php
}

footerContent();
