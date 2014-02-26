TSUGI - A Simple Framework for Building PHP-Based Learning Tools
================================================================

**For now this is simply prototype code that I am putting together - 
it is not ready to use at all.  This is under construction.**   

If you want to see this code actually working, you can play online:

* https://lti-tools.dr-chuck.com/tsugi/

To install this software follow these steps:

* Check the code out from GitHub

* Create a database and get authentication info for the database

* Copy the file config-dist.php to config.php and edit the file
to put in the appropriate values.  Make sure to change all the secrets.
If you are just getting started turn on DEVELOPER mode so you can launch 
the tools easily

* Go to the main page, and click on "Admin" to make all the database
tables - you will need the Admin password you just put into config.php
If all goes well, lots of table should be created.  You can run upgrade.php
more than once - it will automatically detect that it has been run.

* At that point you can play with and/or develop new tools

MAMP NOTES (Macintosh)
----------------------

    cd /Applications/MAMP/htdocs/
    git clone https://github.com/csev/tsugi.git
    cd tsugi
    cp config-dist.php config.php
    
    edit config.php - some values
    $CFG->wwwroot = 'http://localhost:8888/tsugi';
    $CFG->dbprefix  = '';

    Make a database using PhpMyAdmin:

    CREATE DATABASE tsugi;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

    Visit  http://localhost:8888/tsugi and go to 'Admin' to create tables.

XAMPP NOTES (Windows)
---------------------

    cd \xampp\htdocs
    git clone https://github.com/csev/tsugi.git
    cd tsugi
    copy config-dist.php config.php
    
    edit config.php - some values
    $CFG->wwwroot = 'http://localhost/tsugi';
    $CFG->dbprefix  = '';

    Make a database using PhpMyAdmin:

    CREATE DATABASE tsugi;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

    Visit  http://localhost/tsugi and go to 'Admin' to create tables.

/Chuck

Thu Feb 13 10:08:00 EST 2014

