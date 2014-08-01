<?php
/*
This is a sample database.php file.  When this file is present,
it is called from the admin/upgrade.php script to create and maintain
the database tables across tsugi.  This script defines two variables.

$DATABASE_UNINSTALL is the sql statement to remove the data for this module.

$DATABASE_INSTALL is an array of arrays.  For each table needed by the
application you put an entry into the outer array.  Each per-table entry
is an array with two entries, the first being the name of the table and
second being the SQL statement to create the table.

During testing, you can edit this file, run admin/upgrade.php to create
the table, then drop the table by hand, edit this file and re-run
admin/upgrade.php until the table is created the way you want it.
*/

if ( ! isset($CFG) ) {
    die("This file is not supposed to be accessed directly.  It is activated using
        the 'Admin' feature from the main page of the application.");
}

/*
$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}solution_wiscrowd";

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}solution_wiscrowd",
"create table {$CFG->dbprefix}solution_wiscrowd (

    -- Insert your fields here.

) ENGINE = InnoDB DEFAULT CHARSET=utf8"));
*/

