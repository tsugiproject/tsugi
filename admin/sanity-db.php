<?php

use \Tsugi\Core\LTIX;

// Lets check to see if we have a database or not and give a decent error message
try {
   if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
    $PDOX = LTIX::getConnection();
} catch(\PDOException $ex){
    $msg = $ex->getMessage();
    error_log("DB connection: ".$msg);
    echo('<div class="alert alert-danger" style="margin: 10px;">'."\n");
  if ( strpos($msg, 'Unknown database') !== false ||
       strpos($msg, 'Access denied for user') !== false ) {
    echo("<p>An error has occurred.  Either your database has 
not yet been created or you cannot connect to the database.
<p><b>Creating a Database</b></p>
<p>If you have full access to your MySql instance (i.e. like
MAMP or XAMPP, you may need to run commands like this:</p>
<pre>
    CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
    CREATE USER 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost';
    CREATE USER 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1';
</pre>
<p>Note: MySQL 8.0 may require different commands.</p>
<p>Make sure to choose appropriate passwords when setting this up.</p>
<p>If you are running in a hosted environment and are using an admin tool like
CPanel (or equivalent).  You must user this interface to create a database,
user, and password.</p>
<p>
In some systems, a database adminstrator will create the database,
user, and password and simply give them to you.
<p>
Once you have the database, account and password you must update your
<code>tsugi/config.php</code> with this information.</p>");
echo('
<p><b>Database Users</b></p>
<p>
The user and password for the database connection are setup using either a
SQL <code>GRANT</code> command or created in an adminstration tool like CPanel.
Or perhaps a system administrator created the database and gave you the
account and password to access the database.</p>
<p>Make sure to check the values in your <code>tsugi/config.php</code> for
<pre>
    $CFG->dbuser    = \'ltiuser\';
    $CFG->dbpass    = \'ltipassword\';
</pre>
To make sure they match the account and password assigned to your database.
</p>
');
    } else if ( strpos($msg, 'Can\'t connect to MySQL server') !== false ||
        strpos($msg, 'Connection refused') !== false) {
        echo('<p>It appears that you cannot connect to your MySQL server at
all.  The most likely problem is the wrong host or port in this option
in your <code>tsugi/config.php</code> file:
<pre>
$CFG->pdo       = \'mysql:host=127.0.0.1;dbname=tsugi\';
# $CFG->pdo       = \'mysql:host=127.0.0.1;port=8889;dbname=tsugi\'; // MAMP
</pre>
The host may be incorrect - you might try switching from \'127.0.0.1\' to
\'localhost\'.   Or if you are on a hosted system with an ISP the name of the
database host might be given to you like \'db4263.mysql.1and1.com\' and you
need to put that host name in the PDO string.</p>
<p>
Most systems are configured to use the default MySQL port of 3306 and if you
omit "port=" in the PDO string it assumes 3306.  If you are using MAMP
this is usually moved to port 8889.  If neither 3306 nor 8889 works you
probably have a bad host name.  Or talk to your system administrator.
</p>
<p>Note: Tsugi works best with MySQL 5.x.   Some of the setup and commands may need
to be different for MySQL 8.0.
</p>
');
} else {
echo("<p>There is a problem with your database connection.</p>\n");
echo("<p>Tsugi works best with MySQL 5.x.</p>\n");
}

    echo("<p>Database error detail: ".$msg."</p>\n");
    echo("<p>Once you have fixed the problem, come back to this page and refresh
to see if this message goes away.</p>");
    echo('<p>Installation instructions are avaiable at <a href="http://www.tsugi.org/"
target="_blank">tsugi.org</a>');

    echo("\n</div>\n");
    die_with_error_log("Database error ".$msg);
}

// Now check the plugins table to see if it exists
$p = $CFG->dbprefix;
$plugins = "{$p}lms_plugins";
$table_fields = $PDOX->metadata($plugins);
if ( $table_fields === false ) {
    echo('<div class="alert alert-danger" style="margin: 10px;">'."\n");
    echo("<p>It appears that your database connection is working properly
but you have no tables in your database.  There are two ways to create these tables:
<ul>
<li><p>The simplest way is to navigate the
<a href=\"".$CFG->wwwroot."/admin\">'Administration'</a> console, 
enter the administrator master password as specified in <code>\$CFG->adminpw</code>
and select
'Upgrade Database'.
</p></li>
</ul>
<p>Another way to create the tables (or upgrade them) from the command line:
<pre>
cd ... /tsugi/admin
php upgrade.php
</pre>
Make sure to be in the <code>admin</code> folder before running 
the <code>upgrade.php</code> script.
</p>
");
    echo("\n</div>\n");
// Now check to see if a database upgrade might be necessary
} else {
    $row = $PDOX->rowDie("SELECT MAX(version) AS version FROM {$plugins}");
    $actualdbversion = $row['version'];
    if ( $actualdbversion < $CFG->dbversion ) {
        echo('<div class="alert alert-danger" style="margin: 10px;">'."\n");
        echo("<p>Warning: Database version=$actualdbversion should be
        software version=$CFG->dbversion - please run\n");
        echo("'Upgrade Database in the <a href=\"".$CFG->wwwroot.'/admin/">'."Administration console</a></p>\n");
        echo("\n</div>\n");
        error_log("Warning: DB current version=$actualdbversion expected version=$CFG->dbversion");
    }
}

