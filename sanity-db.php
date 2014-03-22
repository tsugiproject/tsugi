<?php

// Lets check to see if we have a database or not and give a decent error message
try {
    define('PDO_WILL_CATCH', true);
    require_once("pdo.php");
} catch(PDOException $ex){
    $msg = $ex->getMessage();
    error_log("DB connection: "+$msg);
    echo('<div class="alert alert-danger" style="margin-top: 10px;">'."\n");
    if ( strpos($msg, 'Unknown database') !== false ) {
        echo("<p>It does not appear as though your database exists.</p>
<p> If you have full access to your MySql instance (i.e. like 
MAMP or XAMPP, you may need to run commands like this:</p>
<pre>
    CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';
</pre>
<p>Make sure to choose appropriate passwords when setting this up.</p>
<p>If you are running in a hosted environment and are using an admin tool like
CPanel (or equivalent).  You must user this interface to create a database, 
user, and password.</p>
<p>
Once you have the database, account and password you must update your
<b>config.php</b> with this information.</p>
");
    } else {
        echo("<p>There is a problem with your database connection.</p>\n");
        echo("<p>Database error: ".$msg."</p>\n");
    }

    echo("<p>Once you have fixed the problem, come back to this page and refresh
to see if this message goes away.</p>");
    echo('<p>Installation instructions are avaiable at <a href="http://www.tsugi.org/"
target="_blank">tsugi.org</a>');

    echo("\n</div>\n");
    die();
}   
