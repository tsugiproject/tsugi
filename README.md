TSUGI - A Framework for Building PHP-Based Learning Tools
=========================================================

This project is in early days.  Its goal is to build a scalable
multi-tenant "tool" hosting environment based on the emerging IMS
standards.  A project in Java with a very similar approach is called
the [LTI Starter Kit](https://github.com/azeckoski/lti_starter).

If you want to see this code actually working, you can play online:

* https://lti-tools.dr-chuck.com/tsugi/

Here are several other pages describing this project:

* [About The Project](docs/ABOUT.md)
* [TSUGI API Documentation](http://do1.dr-chuck.com/tsugi/phpdoc/)
* [Coding Standards](docs/CODING.md)
* [Internationalizing and Localizing](docs/I18N.md)
* [Generating API Documentation](docs/PHPDOC.md)

If you have been given a key/secret to use a system, here
is some rudimentatry LTI 1.0 documentation:

* [Configuring LTI 1.0 Launches](docs/LAUNCHING.md)

I have recorded a simple video describing the install/config steps
for this software on

* http://www.youtube.com/watch?v=YNl1kJ1Z154

To install this software follow these steps:

* Pre-requisites - git installed and working at the command prompt
and a PHP/MySql environment installed

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

    $wwwroot = 'http://localhost:8888/tsugi';
    $CFG->pdo = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi';
    $CFG->dbprefix  = '';
    $CFG->adminpw = '....';

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

    $wwwroot = 'http://localhost/tsugi';
    $CFG->dbprefix  = '';
    $CFG->adminpw = '....';

    Make a database using PhpMyAdmin:

    CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
    GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
    GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

    Visit  http://localhost/tsugi and go to 'Admin' and enter the
    adminpw to automatically create all necessary tables.

/Chuck

Sun Mar 16 08:18:19 EDT 2014

