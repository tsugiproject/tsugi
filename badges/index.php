<?php

use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;
use \Tsugi\UI\Lessons;

require_once "../top.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

if ( ! isset($_SESSION['id']) ) {
    header('Location: ../login.php');
    return;
}

$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo('<h2>Your Badges...</h2>');
$OUTPUT->flashMessages();

// http://www.onlinebadgemaker.com/
?>
<?php
if ( ! isset($_SESSION['email']) ) return;
if ( ! isset($_SESSION['id']) ) return;
if ( ! isset($_SESSION['context_id']) ) return;

$decrypted = $_SESSION['id'].':badge_01_code:'.$_SESSION['context_id'];
$encrypted = bin2hex(AesCtr::encrypt($decrypted, $CFG->badge_encrypt_password, 256));
?>
<p>Here is the badge baked especially for for <?php echo(htmlspecialchars($_SESSION['email'])); ?> <br/>
<a href="images/<?php echo($encrypted); ?>.png" target="_blank">
<img src="images/<?php echo($encrypted); ?>.png" width="90"></a>
<p>You can download the baked badge image to your computer and display it on your 
web site or maually upload it to a badge hosting facility.
</p>
</p>
<?php
/*

$sql = 'SELECT Courses.id, code, title, description,
        Enrollments.id, cert_at
        FROM Courses LEFT OUTER JOIN Enrollments
        ON Courses.id = course_id
        AND Enrollments.user_id = '.$_SESSION['id'].'
        WHERE cert_at IS NOT NULL
        ORDER BY cert_at DESC';

$result = mysql_query($sql);
if ( $result === FALSE ) {
    echo('Fail-SQL:'.mysql_error().','.$sql);
    echo("Unable to retrieve courses...");
    return;
}

while ( $row = mysql_fetch_row($result) ) {
   print_r($row);
   $decrypted = $_SESSION['id'].':'.$row[0];
   $encrypted = bin2hex(AesCtr::encrypt($decrypted, $CFG->encrypt_password, 256));
?>
<a href="images/baker.php?id=<?php echo($encrypted); ?>.png" target="_blank">
<img src="images/baker.php?id=<?php echo($encrypted); ?>.png" width="90"></a>
<?php
}
*/
