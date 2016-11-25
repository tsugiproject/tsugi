
Tsugi PHP Library
=================

This is part of the Tsugi PHP Project and contains the run-time objects and scripts that support PHP 
Tsugi applications and modules.  

* [PHP Tsugi](https://github.com/csev/tsugi)

Here is some documentation for the APIs that are provided by this library:

* [API Documentation](http://do1.dr-chuck.com/tsugi/phpdoc/)


In addition to being used as part of the base Tsugi installs, Tsugi standalone
application or modules will generally pull this in as a 
[Packagist](https://packagist.org/packages/tsugi/lib) dependency
using [Composer](http://getcomposer.org/).  

For samples of how to use this code in a standalone library or an application, 
please see the following repositories:

* [Sample Tsugi Module](https://github.com/csev/tsugi-php-module) - Copy
this if you want to start a fresh Tsugi Module from scratch.  If you are building
a new tool from scratch, you should build it as a "Tsugi Module" following all
of the Tsugi style guidance, using the Tsugi browser environment, and making
full use of the Tsugi framework. This repository contains a basic
"Tsugi Module" you can use as a starting point.

* [Sample Tsugi-Enabled Application](https://github.com/csev/tsugi-php-standalone) - You
can also use Tsugi as a library and  add it to a few places in an existing application.
This repository contains sample code showing how to use Tsugi as a library in an existing
application.

Unit Testing
------------

To test:

        php phpunit-old.phar 

Releasing
---------

This is stored in Packagist.

    https://packagist.org/packages/tsugi/lib

