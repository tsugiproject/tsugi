
Tsugi PHP Library
=================

[![Apereo Incubating badge](https://img.shields.io/badge/apereo-incubating-blue.svg?logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAABmJLR0QA%2FwD%2FAP%2BgvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QUTEi0ybN9p9wAAAiVJREFUKM9lkstLlGEUxn%2Fv%2B31joou0GTFKyswkKrrYdaEQ4cZAy4VQUS2iqH%2BrdUSNYmK0EM3IkjaChnmZKR0dHS0vpN%2FMe97TIqfMDpzN4XkeDg8%2Fw45R1XNAu%2Fe%2BGTgAqLX2KzAQRVGytLR0jN2jqo9FZFRVvfded66KehH5oKr3dpueiMiK915FRBeXcjo9k9K5zLz%2B3Nz8EyAqX51zdwGMqp738NSonlxf36Cn7zX9b4eYX8gSBAE1Bw9wpLaW%2BL5KWluukYjH31tr71vv%2FU0LJ5xzdL3q5dmLJK7gON5wjEQizsTkFMmeXkbHxtHfD14WkbYQaFZVMzk1zfDHERrPnqGz4wZ1tYfJ5%2FPMLOYYW16ltrqKRDyOMcYATXa7PRayixSc4%2FKFRhrqjxKGIWVlZVQkqpg1pYyvR%2BTFF2s5FFprVVXBAAqq%2F7a9uPKd1NomeTX4HXfrvZ8D2F9dTSwWMjwywueJLxQKBdLfZunue0Mqt8qPyMHf0HRorR0ArtbX1Zkrly7yPNnN1EyafZUVZLJZxjNLlHc%2BIlOxly0RyktC770fDIGX3vuOMAxOt19vJQxD%2BgeHmE6liMVKuNPawlZ9DWu2hG8bW1Tuib0LgqCrCMBDEckWAVjKLetMOq2ZhQV1zulGVFAnohv5wrSq3tpNzwMR%2BSQi%2FyEnIl5Ehpxzt4t6s9McRdGpIChpM8Y3ATXbkKdEZDAIgqQxZrKo%2FQUk5F9Xr20TrQAAAABJRU5ErkJggg%3D%3D)](https://www.apereo.org/content/projects-currently-incubation) [![Build Status](https://travis-ci.org/tsugiproject/tsugi-php.svg?branch=master)](https://travis-ci.org/tsugiproject/tsugi-php)

This is part of the Tsugi PHP Project and contains the run-time objects and scripts that support PHP 
Tsugi applications and modules.  

* [PHP Tsugi](https://github.com/tsugiproject/tsugi)

Here is some documentation for the APIs that are provided by this library:

* [API Documentation](http://do1.dr-chuck.com/tsugi/phpdoc/)


In addition to being used as part of the base Tsugi installs, Tsugi standalone
application or modules will generally pull this in as a 
[Packagist](https://packagist.org/packages/tsugi/lib) dependency
using [Composer](http://getcomposer.org/).  

For samples of how to use this code in a standalone library or an application, 
please see the following repositories:

* [Sample Tsugi Module](https://github.com/tsugiproject/tsugi-php-module) - Copy
this if you want to start a fresh Tsugi Module from scratch.  If you are building
a new tool from scratch, you should build it as a "Tsugi Module" following all
of the Tsugi style guidance, using the Tsugi browser environment, and making
full use of the Tsugi framework. This repository contains a basic
"Tsugi Module" you can use as a starting point.

* [Sample Tsugi-Enabled Application](https://github.com/tsugiproject/tsugi-php-standalone) - You
can also use Tsugi as a library and  add it to a few places in an existing application.
This repository contains sample code showing how to use Tsugi as a library in an existing
application.

Unit Testing
------------

To download PHPUnit (and any other development dependencies):

    composer install

To test:

    vendor/bin/phpunit

To run one test:

    vendor/bin/phpunit --filter {EntryTest}

Releasing
---------

This is stored in Packagist.

    https://packagist.org/packages/tsugi/lib

Making PHPDoc
-------------

Read this:

    https://github.com/FriendsOfPHP/Sami

Curl this:

    curl -O http://get.sensiolabs.org/sami.phar

Run this:

    rm -r /tmp/tsugi/
    php sami.phar update sami-config-dist.php
    mv /tmp/tsugi/sami.js /tmp/tsugi/s.js
    sed 's/".html"/"index.html"/' < /tmp/tsugi/s.js > /tmp/tsugi/sami.js
    rm /tmp/tsugi/s.js
    open /tmp/tsugi/index.html

