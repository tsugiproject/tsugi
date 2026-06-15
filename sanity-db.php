<?php

use \Tsugi\Core\LTIX;

/**
 * Database name for install error messages from $CFG->pdo when available.
 */
function sanity_db_name($CFG = null) {
    $dbname = 'tsugi';
    if ( isset($CFG) && is_object($CFG) && ! empty($CFG->pdo) && is_string($CFG->pdo) ) {
        if ( preg_match('/(?:^|[;])dbname=([^;]+)/i', $CFG->pdo, $m) ) {
            $dbname = $m[1];
        }
    }
    return $dbname;
}

// Lets check to see if we have a database or not and give a decent error message
try {
   if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
    $PDOX = LTIX::getConnection();
} catch(\PDOException $ex){
    $msg = $ex->getMessage();
    error_log("DB connection: ".$msg);
    $dbname = sanity_db_name($CFG ?? null);
    $sql_db = '`'.str_replace('`', '``', $dbname).'`';
    $pdo_dbname = htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8');
    echo('<div class="alert alert-danger" style="margin: 10px;">'."\n");
    echo("<p>Database error detail: ".htmlspecialchars($msg)."</p>\n");
  if ( strpos($msg, 'Unknown database') !== false ||
       strpos($msg, 'Access denied for user') !== false ) {
    echo("<p>An error has occurred.  Either your database has 
not yet been created or you cannot connect to the database.
<p><b>Creating a Database</b></p>
<p>If you have full access to your MySql instance (i.e. like
MAMP or XAMPP, you may need to run commands like this:</p>
<pre>
    USE mysql;
    CREATE DATABASE IF NOT EXISTS ".$sql_db." DEFAULT CHARACTER SET utf8mb4;
    CREATE USER IF NOT EXISTS 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    CREATE USER IF NOT EXISTS 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';
    FLUSH PRIVILEGES;
    USE ".$sql_db.";
    GRANT ALL ON ".$sql_db.".* TO 'ltiuser'@'localhost';
    GRANT ALL ON ".$sql_db.".* TO 'ltiuser'@'127.0.0.1';
</pre>
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
$CFG->pdo       = \'mysql:host=127.0.0.1;dbname='.$pdo_dbname.'\';
# $CFG->pdo       = \'mysql:host=127.0.0.1;port=8889;dbname='.$pdo_dbname.'\'; // MAMP
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
<p>Note: Tsugi works best with MySQL 8.x.
</p>
');
} else {
echo("<p>There is a problem with your database connection.</p>\n");
echo("<p>Tsugi works best with MySQL 8.x.</p>\n");
}

    echo("<p>Once you have fixed the problem, come back to this page and refresh
to see if this message goes away.</p>");
    echo('<p>Installation instructions are avaiable at <a href="http://www.tsugi.org/"
target="_blank">tsugi.org</a>');

    echo("\n</div>\n");
    die_with_error_log("Database error ".$msg);
}

// Cheapest table-exists probe: one row at most, no aggregation
$p = $CFG->dbprefix;
$plugins = "{$p}lms_plugins";
$stmt = $PDOX->queryReturnError("SELECT 1 FROM {$plugins} LIMIT 1", false, false);
if ( ! $stmt->success ) {
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
    if ( ! defined('SANITY_DB_ALLOW_NO_TABLES') ) {
        die_with_error_log('Database has no Tsugi tables');
    }
} else {
    $stmt->closeCursor();
}
