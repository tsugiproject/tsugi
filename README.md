
Tsugi PHP Library
=================

**NOTE** As of 2016-Apr-20, the configuration in `config.php` of the staticroot 
needs to change to:

    $CFG->staticroot = 'https://www.dr-chuck.net/tsugi-static';

or

    $CFG->staticroot = 'http://localhost/tsugi-static';  /// For normal
    $CFG->staticroot = 'http://localhost:8888/tsugi-static';   // For MAMP

With tsugi-static checked out seprately.

Most `config.php` files leave this as default and depend on ConfigInfo
to set the default.  In this case we changed the default so no 
change is needed.

Testing
---------

To test:

        php phpunit-old.phar 



