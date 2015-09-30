Installing TSUGI
================

I have recorded a simple video describing the install/config steps
for this software on YouTube:

[Installation Video] (https://www.youtube.com/watch?v=Na_QDXp-Y7o&index=1&list=PLlRFEj9H3Oj5WZUjVjTJVBN18ozYSWMhw)

Pre-Requisites
--------------

* [Install GIT] (GITHUB.md) so that it works at the command prompt.

* Install a PHP/MySQL Environment like XAMPP / MAMP following the 
instructions at:

    http://www.php-intro.com/install.php

To install this software follow these steps:

Installation
------------

* Check the code out from GitHub and put it in a directory where 
your web server can read it

        git clone https://github.com/csev/tsugi.git

* Create a database and get authentication info for the database
 
        CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
        GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
        GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

* Copy the file config-dist.php to config.php and edit the file
to put in the appropriate values.  Make sure to change all the secrets.
If you are just getting started turn on DEVELOPER mode so you can launch
the tools easily.  Each of the fields is documented in the config-dist.php
file - here is some additional documentation on the configuration values:

    http://do1.dr-chuck.com/tsugi/phpdoc/classes/Tsugi.Config.ConfigInfo.html

* Go to the main page, and click on "Admin" to make all the database
tables - you will need the Admin password you just put into config.php
If all goes well, lots of tables should be created.  You can run upgrade.php
more than once - it will automatically detect that it has been run.

* At that point you can play with and/or develop new tools

Note: Make sure that none of the folders in the path to the tsugi
folder have any spaces in them.  You may get signature errors
if you use folders with blanks in them.

MAMP NOTES (Macintosh)
----------------------

    cd /Applications/MAMP/htdocs/
    git clone https://github.com/csev/tsugi.git
    cd tsugi
    cp config-dist.php config.php

    edit config.php using a text editor - some values

    Make sure to change $wwwroot to reflect where your server is 
    hosted or the CSS files will not be loaded.

    $wwwroot = 'http://localhost:8888/tsugi';
    $CFG->pdo = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi';
    $CFG->dbprefix  = '';
    $CFG->adminpw = ....;

    Make a database using PhpMyAdmin:

    CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

    Visit  http://localhost:8888/tsugi and go to 'Admin' and enter the
    adminpw to automatically create all necessary tables.

XAMPP NOTES (Windows)
---------------------

    cd \xampp\htdocs
    git clone https://github.com/csev/tsugi.git
    cd tsugi
    copy config-dist.php config.php

    edit config.php using a text editor - some values

    Make sure to change $wwwroot to reflect where your server is 
    hosted or the CSS files will not be loaded.

    $wwwroot = 'http://localhost/tsugi';
    $CFG->dbprefix  = '';
    $CFG->adminpw = ....;

    Make a database using PhpMyAdmin:

    CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

    Visit  http://localhost/tsugi and go to 'Admin' and enter the
    adminpw to automatically create all necessary tables.

If you are setting this up on some variation of Linux, the Macintosh 
instructions will be the most help.

/Chuck

Sun Sep 14 18:50:04 EDT 2014

